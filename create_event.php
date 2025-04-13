<?php
session_start();

// --- PHP Prerequisites & Logic ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php';

// --- Constants ---
define('UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/cm/uploads/event_posters/');
define('UPLOAD_URL_PATH', '/cm/uploads/event_posters/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// --- Authentication & Authorization ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) { 
    /* ... login redirect ... */
    $_SESSION['error_message'] = "You must be logged in to create an event.";
    header('Location: login.php'); exit;
     exit; }
$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

if ($userRole === 'student') {
     /* ... student redirect ... */
        $_SESSION['error_message'] = "Students cannot create events.";
        header('Location: events.php'); exit;
    exit; }


// --- Fetch Clubs User Can Use ---
$creatableClubs = []; $pageError = null; $leaderHasOnlyOneClub = false;
$leaderSingleClubId = null; $leaderSingleClubName = null;
try { /* ... Same club fetching logic as before ... */
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }
    if ($userRole === 'admin') { $stmtClubs = $pdo->prepare("SELECT id, name FROM clubs WHERE status = 'active' ORDER BY name ASC"); $stmtClubs->execute(); $creatableClubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC); if (empty($creatableClubs)) { $pageError = "No active clubs found."; } } elseif ($userRole === 'club_leader') { $stmtClubs = $pdo->prepare("SELECT c.id, c.name FROM clubs c JOIN club_members cm ON c.id = cm.club_id WHERE cm.user_id = :user_id AND cm.role = 'leader' AND c.status = 'active' ORDER BY c.name ASC"); $stmtClubs->bindParam(':user_id', $userId, PDO::PARAM_INT); $stmtClubs->execute(); $creatableClubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC); if (empty($creatableClubs)) { $pageError = "You lead no active clubs."; } elseif (count($creatableClubs) === 1) { $leaderHasOnlyOneClub = true; $leaderSingleClubId = $creatableClubs[0]['id']; $leaderSingleClubName = $creatableClubs[0]['name']; } }
} catch (Exception $e) { error_log("Error fetching clubs for event: " . $e->getMessage()); $pageError = "Could not load club data."; }


// --- Initialize Variables ---
$eventName = ''; $eventDesc = ''; $eventStartDateTimeInput = ''; $eventEndDateTimeInput = ''; // Added End Input
$eventLocation = '';
$selectedClubId = $leaderHasOnlyOneClub ? $leaderSingleClubId : '';
$posterPathForDb = null;
$formError = null; $formSuccess = null;

// --- Get Session Flash Messages ---
if (isset($_SESSION['error_message'])) { $pageError = $pageError ?? $_SESSION['error_message']; unset($_SESSION['error_message']); }
if (isset($_SESSION['success_message'])) { $formSuccess = $_SESSION['success_message']; unset($_SESSION['success_message']); }

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError) {

    // Retrieve club ID
    $selectedClubId = $leaderHasOnlyOneClub ? $leaderSingleClubId : filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);

    // Retrieve other inputs
    $eventName = trim($_POST['event_name'] ?? '');
    $eventDesc = trim($_POST['event_description'] ?? '');
    $eventStartDateTimeInput = trim($_POST['event_start_date_time'] ?? ''); // Renamed input
    $eventEndDateTimeInput = trim($_POST['event_end_date_time'] ?? '');   // New input
    $eventLocation = trim($_POST['event_location'] ?? '');
    $posterFile = $_FILES['poster_image'] ?? null;

    // --- Validation ---
    $formError = '';
    $eventStartDateTime = null; // Formatted start date/time for DB
    $eventEndDateTime = null;   // Formatted end date/time for DB (can stay null)
    $targetPath = null;
    $uniqueFilename = null;

    // Basic required field check
    if (empty($eventName) || empty($eventDesc) || empty($eventStartDateTimeInput) || $selectedClubId === false || $selectedClubId === null) {
        $formError = "Event Name, Description, Start Date/Time, and Club are required.";
    }

    // Validate Start Date/Time
    $eventStartDateTimeObj = null; // Initialize object
    if (!$formError) {
        try {
            $eventStartDateTimeObj = DateTime::createFromFormat('Y-m-d\TH:i', $eventStartDateTimeInput, new DateTimeZone(date_default_timezone_get()));
            if ($eventStartDateTimeObj === false || $eventStartDateTimeObj->format('Y-m-d\TH:i') !== $eventStartDateTimeInput) {
                $formError = "Invalid Start Date/Time format.";
            } else {
                $now = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
                if ($eventStartDateTimeObj < $now) {
                    $formError = "Event start date/time cannot be in the past.";
                } else {
                    // Valid start date, format for DB
                    $eventStartDateTime = $eventStartDateTimeObj->format('Y-m-d H:i:s');
                }
            }
        } catch (Exception $e) { $formError = "Error processing start date."; error_log("Start date error: ".$e->getMessage()); }
    }

    // Validate Optional End Date/Time (only if provided and start date is valid)
    if (!$formError && !empty($eventEndDateTimeInput) && $eventStartDateTimeObj) {
         try {
             $eventEndDateTimeObj = DateTime::createFromFormat('Y-m-d\TH:i', $eventEndDateTimeInput, new DateTimeZone(date_default_timezone_get()));
             if ($eventEndDateTimeObj === false || $eventEndDateTimeObj->format('Y-m-d\TH:i') !== $eventEndDateTimeInput) {
                 $formError = "Invalid End Date/Time format.";
             } elseif ($eventEndDateTimeObj <= $eventStartDateTimeObj) { // End must be AFTER start
                 $formError = "Event end date/time must be after the start date/time.";
             } else {
                  // Valid end date, format for DB
                  $eventEndDateTime = $eventEndDateTimeObj->format('Y-m-d H:i:s');
             }
         } catch (Exception $e) { $formError = "Error processing end date."; error_log("End date error: ".$e->getMessage()); }
    }

    // Validate Club Selection (Security Check - Same as before)
    if (!$formError) { /* ... Club permission validation ... */
        $isClubAllowed = false; $tempCreatableClubs = [];
        if($userRole === 'admin') { $stmtTemp = $pdo->prepare("SELECT id FROM clubs WHERE status = 'active'"); $stmtTemp->execute(); $tempCreatableClubs = $stmtTemp->fetchAll(PDO::FETCH_ASSOC); } else { $stmtTemp = $pdo->prepare("SELECT c.id FROM clubs c JOIN club_members cm ON c.id = cm.club_id WHERE cm.user_id = :uid AND cm.role = 'leader' AND c.status = 'active'"); $stmtTemp->bindParam(':uid', $userId, PDO::PARAM_INT); $stmtTemp->execute(); $tempCreatableClubs = $stmtTemp->fetchAll(PDO::FETCH_ASSOC); }
        foreach ($tempCreatableClubs as $allowedClub) { if ($allowedClub['id'] == $selectedClubId) { $isClubAllowed = true; break; } }
        if (!$isClubAllowed) { $formError = "Invalid club selected or lack permission."; }
    }

    // Validate Image Upload (Same as before)
    $posterPathForDb = null; // Reset before upload processing
    if (!$formError && $posterFile && $posterFile['error'] !== UPLOAD_ERR_NO_FILE) {
        // ... (validation for error code, size, MIME type) ...
        if (/* validation passes */ !($posterFile['error'] !== UPLOAD_ERR_OK || $posterFile['size'] > MAX_FILE_SIZE || !in_array(mime_content_type($posterFile['tmp_name']), ALLOWED_MIME_TYPES))) {
            $fileExtension = strtolower(pathinfo($posterFile['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                 $formError = "Invalid poster file extension.";
            } else {
                 $uniqueFilename = uniqid('event_', true) . '.' . $fileExtension;
                 $targetPath = UPLOAD_DIR . $uniqueFilename;
                 if (!file_exists(UPLOAD_DIR)) { if (!mkdir(UPLOAD_DIR, 0775, true)) { $formError = "Failed to create upload dir."; $targetPath = null; } }
                 elseif (!is_writable(UPLOAD_DIR)) { $formError = "Upload dir not writable."; $targetPath = null; }

                 if ($targetPath) { // Only set DB path if target path is valid
                      $posterPathForDb = UPLOAD_URL_PATH . $uniqueFilename; // Or just filename
                 }
            }
        } else {
            // Set specific error based on failed validation
            if ($posterFile['error'] !== UPLOAD_ERR_OK) $formError="Upload error (Code {$posterFile['error']}).";
            elseif ($posterFile['size'] > MAX_FILE_SIZE) $formError="Poster too large.";
            else $formError="Invalid poster file type.";
        }
    }


    // --- Process if No Errors ---
    if (empty($formError)) {
        $uploadSuccess = false;
        // Attempt file move only if path was determined and no other errors exist yet
        if ($targetPath !== null && $posterFile && $posterFile['error'] === UPLOAD_ERR_OK) {
            if (move_uploaded_file($posterFile['tmp_name'], $targetPath)) {
                $uploadSuccess = true;
            } else {
                $formError = "Failed to save uploaded poster image.";
                error_log("File upload move failed: {$posterFile['tmp_name']} to {$targetPath}");
                $posterPathForDb = null; // Don't save path if move failed
            }
        } elseif ($posterFile && $posterFile['error'] === UPLOAD_ERR_OK && $targetPath === null) {
             $formError = $formError ?: "Upload directory issue prevented saving image.";
             $posterPathForDb = null;
        }

        // Proceed with DB insert only if still no critical errors
        if (empty($formError)) {
            try {
                $pdo->beginTransaction();
                $eventStatus = ($userRole === 'admin') ? 'active' : 'pending';

                // ** UPDATED SQL INSERT **
                $sql = "INSERT INTO events (club_id, name, description, event_date, event_end_date, location, status, created_by, poster_image_path)
                        VALUES (:club_id, :name, :description, :event_date, :event_end_date, :location, :status, :user_id, :poster)";
                $stmtInsert = $pdo->prepare($sql);
                // Bind Parameters (including new end date)
                $stmtInsert->bindParam(':club_id', $selectedClubId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':name', $eventName);
                $stmtInsert->bindParam(':description', $eventDesc);
                $stmtInsert->bindParam(':event_date', $eventStartDateTime); // Use formatted start date
                $stmtInsert->bindParam(':event_end_date', $eventEndDateTime); // Bind formatted end date (can be null)
                $stmtInsert->bindParam(':location', $eventLocation);
                $stmtInsert->bindParam(':status', $eventStatus);
                $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':poster', $posterPathForDb);

                if ($stmtInsert->execute()) {
                    $pdo->commit();
                    $successMessage = ($eventStatus === 'active') ? "Event created!" : "Event proposed.";
                    $_SESSION['success_message'] = $successMessage;
                    header('Location: events.php'); exit;
                } else { /* Rollback, set error, unlink */
                    $pdo->rollBack(); $formError = "DB Error."; if ($uploadSuccess && $targetPath && file_exists($targetPath)) unlink($targetPath); error_log("Event insert failed: ".implode(", ", $stmtInsert->errorInfo()));
                }

            } catch (Exception $e) { /* Rollback, set error, unlink */
                 if ($pdo->inTransaction()) $pdo->rollBack(); $formError = "Submission error."; if ($uploadSuccess && $targetPath && file_exists($targetPath)) unlink($targetPath); error_log("Event creation exception: ".$e->getMessage());
            }
        } // End: if(empty($formError)) after upload check
    } // End: if(empty($formError)) before upload check

     // Redirect back on error
     if (!empty($formError)) {
        $_SESSION['error_message'] = $formError;
        header('Location: create_event.php');
        exit;
     }

} // End POST handling


// --- NOW START HTML OUTPUT ---
include_once 'header.php'; 
?>

<!-- Main Content Area -->
<main class="main-content create-event-container">
    <div class="create-event-wrapper card">

        <!-- Page Header -->
        <div class="section-header">
             <h1 class="section-title"><?php echo ($userRole === 'admin') ? 'Create New Event' : 'Propose New Event'; ?></h1>
             <p class="section-subtitle"><?php echo ($userRole === 'admin') ? 'Enter details for an active event.' : 'Event proposals require admin review.'; ?></p>
        </div>

        <!-- Messages Display -->
        <?php if ($pageError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?></div><?php endif; ?>
        <?php if ($formError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($formError); ?></div><?php endif; ?>
        <?php if ($formSuccess): ?> <div class="message success-message" role="alert"><?php echo htmlspecialchars($formSuccess); ?></div><?php endif; ?>

        <!-- Event Creation Form -->
        <?php if (!$pageError): ?>
            <form method="POST" action="create_event.php" class="create-form" enctype="multipart/form-data">

                <!-- Associated Club -->
                <div class="form-group">
                     <label for="club_id">Associated Club <span class="required">*</span></label>
                     <?php if ($leaderHasOnlyOneClub): ?>
                         <input type="hidden" name="club_id" value="<?php echo htmlspecialchars($leaderSingleClubId); ?>">
                         <p class="form-static-text"><?php echo htmlspecialchars($leaderSingleClubName); ?></p>
                     <?php elseif (empty($creatableClubs)): ?>
                          <p class="error-text input-hint">No clubs available.</p>
                     <?php else: ?>
                         <select id="club_id" name="club_id" class="form-input"  style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required>
                             <option value="" disabled <?php echo empty($selectedClubId) ? 'selected' : ''; ?>>-- <?php echo ($userRole === 'admin') ? 'Select Club' : 'Select Your Club'; ?> --</option>
                             <?php foreach ($creatableClubs as $club): ?> <option value="<?php echo htmlspecialchars($club['id']); ?>" <?php echo ($selectedClubId == $club['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($club['name']); ?></option> <?php endforeach; ?>
                         </select>
                     <?php endif; ?>
                 </div>

                 <!-- Event Name -->
                 <div class="form-group"><label for="event_name">Event Name <span class="required">*</span></label><input type="text" id="event_name" name="event_name" class="form-input"  style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($eventName); ?>" maxlength="150" required></div>

                 <!-- Start Date and Time -->
                <div class="form-group">
                     <label for="event_start_date_time">Start Date and Time <span class="required">*</span></label>
                     <input type="datetime-local" id="event_start_date_time" name="event_start_date_time" class="form-input"  style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($eventStartDateTimeInput); ?>" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                 </div>

                 <!-- **** NEW: End Date and Time **** -->
                 <div class="form-group">
                     <label for="event_end_date_time">End Date and Time (Optional)</label>
                     <input type="datetime-local" id="event_end_date_time" name="event_end_date_time" class="form-input"  style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($eventEndDateTimeInput); ?>" min="<?php echo htmlspecialchars($eventStartDateTimeInput); // Set min based on start input ?>">
                     <small class="input-hint">Leave blank if it's a single point in time or duration isn't fixed.</small>
                 </div>

                 <!-- Location -->
                 <div class="form-group"><label for="event_location">Location (Optional)</label><input type="text" id="event_location"  style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" name="event_location" class="form-input" value="<?php echo htmlspecialchars($eventLocation); ?>" maxlength="255" placeholder="e.g., Room 101, Online"></div>
                <!-- Description -->
                <div class="form-group"><label for="event_description">Description <span class="required">*</span></label><textarea id="event_description"  style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" name="event_description" class="form-textarea" rows="6" placeholder="Details..." required><?php echo htmlspecialchars($eventDesc); ?></textarea></div>
                <!-- Poster Image Upload -->
                <div class="form-group"><label for="poster_image">Event Poster/Image (Optional)</label><input type="file" id="poster_image" name="poster_image" class="form-input file-input" accept=".jpg, .jpeg, .png, .gif, .webp"><small class="input-hint">Max: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?> MB. Types: JPG, PNG, GIF, WEBP.</small></div>
                <!-- Submit Button -->
                <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> <?php echo ($userRole === 'admin') ? 'Create Event' : 'Submit for Review'; ?></button></div>
            </form>
        <?php endif; // End check for pageError ?>

    </div> <!-- End Wrapper -->
</main>


</body>
</html>