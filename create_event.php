<?php
session_start();

// --- PHP Prerequisites & Logic ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php'; // If you have separate functions

// --- Constants ---
define('UPLOAD_DIR', 'uploads/event_posters/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// --- Authentication & Authorization ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) { /* ... login redirect ... */ exit; }
$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

if ($userRole === 'student') { /* ... student redirect ... */ exit; }

// --- Fetch Clubs the User Can Create Events For ---
$creatableClubs = [];
$pageError = null;
$leaderHasOnlyOneClub = false; // Flag to track if leader only leads one club
$leaderSingleClubId = null;    // ID of the single club if applicable
$leaderSingleClubName = null;  // Name of the single club

try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }

    if ($userRole === 'admin') {
        // Admin gets ALL active clubs
        $stmtClubs = $pdo->prepare("SELECT id, name FROM clubs WHERE status = 'active' ORDER BY name ASC");
        $stmtClubs->execute();
        $creatableClubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);
        if (empty($creatableClubs)) {
             $pageError = "There are no active clubs in the system to associate an event with.";
        }
    } elseif ($userRole === 'club_leader') {
        // Leader gets ONLY clubs they lead AND are active
        $stmtClubs = $pdo->prepare("SELECT c.id, c.name FROM clubs c JOIN club_members cm ON c.id = cm.club_id WHERE cm.user_id = :user_id AND cm.role = 'leader' AND c.status = 'active' ORDER BY c.name ASC");
        $stmtClubs->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtClubs->execute();
        $creatableClubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);

        if (empty($creatableClubs)) {
            $pageError = "You do not lead any active clubs, so you cannot create an event.";
        } elseif (count($creatableClubs) === 1) {
            // Leader leads exactly one active club
            $leaderHasOnlyOneClub = true;
            $leaderSingleClubId = $creatableClubs[0]['id']; // Store the ID
            $leaderSingleClubName = $creatableClubs[0]['name']; // Store the name
        }
        // If count > 1, they will see the dropdown populated with $creatableClubs
    }
} catch (Exception $e) { /* ... error handling ... */ }

// --- Initialize Variables ---
$eventName = ''; $eventDesc = ''; $eventDateTime = ''; $eventLocation = '';
// Pre-select club ID if leader has only one
$selectedClubId = $leaderHasOnlyOneClub ? $leaderSingleClubId : '';
$posterPath = null;
$formError = null; $formSuccess = null;

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError) {

    // Retrieve club ID - Handle the case where it might be hidden for single-club leaders
    if ($leaderHasOnlyOneClub) {
        $selectedClubId = $leaderSingleClubId; // Use the stored ID
    } else {
        $selectedClubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
    }

    // Retrieve other inputs (same as before)
    $eventName = trim($_POST['event_name'] ?? '');
    $eventDesc = trim($_POST['event_description'] ?? '');
    $eventDateTimeInput = trim($_POST['event_date_time'] ?? '');
    $eventLocation = trim($_POST['event_location'] ?? '');
    $posterFile = $_FILES['poster_image'] ?? null;

    // --- Validation ---
    $formError = '';
    if (empty($eventName) || empty($eventDesc) || empty($eventDateTimeInput) || $selectedClubId === false || $selectedClubId === null) {
        $formError = "Event Name, Description, Date/Time, and Associated Club are required.";
    }
    // Date/Time validation (same as before)
    // ...
    // Club Selection Validation (crucial for security)
    if (!$formError) {
        $isClubAllowed = false;
        // Need to check against the *originally fetched* allowed clubs
        // Re-fetch allowed clubs just before validation OR trust the initial fetch logic
        // For simplicity here, let's assume the initial fetch holds the correct list
        $tempCreatableClubs = [];
        if($userRole === 'admin') { // Refetch all active for admin validation
             $stmtTemp = $pdo->prepare("SELECT id FROM clubs WHERE status = 'active'"); $stmtTemp->execute(); $tempCreatableClubs = $stmtTemp->fetchAll(PDO::FETCH_ASSOC);
        } else { // Refetch leader's active clubs
             $stmtTemp = $pdo->prepare("SELECT c.id FROM clubs c JOIN club_members cm ON c.id = cm.club_id WHERE cm.user_id = :uid AND cm.role = 'leader' AND c.status = 'active'"); $stmtTemp->bindParam(':uid', $userId, PDO::PARAM_INT); $stmtTemp->execute(); $tempCreatableClubs = $stmtTemp->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach ($tempCreatableClubs as $allowedClub) {
            if ($allowedClub['id'] == $selectedClubId) {
                $isClubAllowed = true;
                break;
            }
        }
        if (!$isClubAllowed) {
            $formError = "Invalid associated club selected or you do not have permission for it.";
        }
    }
    // Image Upload validation (same as before)
    // ...

    // --- Process if No Errors ---
    if (empty($formError)) {
        try {
            // Transaction, status determination, INSERT logic (same as before)
            // ... (Ensure you bind $selectedClubId correctly) ...
            // Redirect logic (same as before)
             $pdo->beginTransaction();
             $eventStatus = ($userRole === 'admin') ? 'active' : 'pending';
             // Handle file upload first inside transaction? Or outside? Outside is safer for rollback.
              $posterPath = null; // Reset before potential upload processing
                if ($posterFile && $posterFile['error'] === UPLOAD_ERR_OK /* && validation passed earlier */) {
                    // Generate unique filename and attempt move (logic from previous answer)
                    $fileExtension = strtolower(pathinfo($posterFile['name'], PATHINFO_EXTENSION));
                    $uniqueFilename = uniqid('event_', true) . '.' . $fileExtension;
                    $targetPath = UPLOAD_DIR . $uniqueFilename;
                    if (!file_exists(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0775, true); }
                    if (move_uploaded_file($posterFile['tmp_name'], $targetPath)) {
                        $posterPath = $targetPath; // Store relative path for DB
                    } else {
                        // Set error *before* attempting DB insert if move fails
                        throw new Exception("Failed to move uploaded file. Check permissions.");
                    }
                }

             $sql = "INSERT INTO events (club_id, name, description, event_date, location, status, created_by, poster_image_path) VALUES (:club_id, :name, :description, :event_date, :location, :status, :user_id, :poster)";
             $stmtInsert = $pdo->prepare($sql); /* Bind all params */
              $stmtInsert->bindParam(':club_id', $selectedClubId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':name', $eventName);
                $stmtInsert->bindParam(':description', $eventDesc);
                $stmtInsert->bindParam(':event_date', $eventDateTime); // Assumes $eventDateTime is correctly formatted string
                $stmtInsert->bindParam(':location', $eventLocation);
                $stmtInsert->bindParam(':status', $eventStatus);
                $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':poster', $posterPath);

             if ($stmtInsert->execute()) { /* Commit, set session success, redirect */
                $pdo->commit();
                $successMessage = ($eventStatus === 'active') ? "Event created successfully!" : "Event proposed for review.";
                $_SESSION['success_message'] = $successMessage;
                 header('Location: events.php'); exit;
             } else { /* Rollback, set error */
                 $pdo->rollBack(); $formError = "DB Error."; if ($posterPath && file_exists($posterPath)) unlink($posterPath);
             }
        } catch (Exception $e) { /* Rollback, set error, unlink file */
             if ($pdo->inTransaction()) $pdo->rollBack(); $formError = "Submission error."; if ($posterPath && file_exists($posterPath)) unlink($posterPath); error_log($e->getMessage());
        }
    }
}


// --- Get Session Flash Messages ---
// (Same as before)
if (isset($_SESSION['error_message'])) { $pageError = $_SESSION['error_message']; unset($_SESSION['error_message']); }
if (isset($_SESSION['success_message'])) { $formSuccess = $_SESSION['success_message']; unset($_SESSION['success_message']); }

// --- NOW START HTML OUTPUT ---
include_once 'test2.php';
?>

<!-- Main Content Area -->
<main class="main-content create-event-container">
    <div class="create-event-wrapper card">

        <!-- Page Header -->
        <!-- (Same as before) -->

        <!-- Error/Success Messages -->
        <!-- (Same as before) -->

        <!-- Event Creation Form -->
        <?php if (!$pageError): ?>
            <form method="POST" action="create_event.php" class="create-form" enctype="multipart/form-data">

                <!-- Associated Club -->
                <div class="form-group">
                    <label for="club_id">Associated Club <span class="required">*</span></label>
                    <?php if ($leaderHasOnlyOneClub): ?>
                        <!-- Display as text, include hidden input -->
                        <input type="hidden" name="club_id" value="<?php echo htmlspecialchars($leaderSingleClubId); ?>">
                        <p class="form-static-text"><?php echo htmlspecialchars($leaderSingleClubName); ?></p>
                        <small class="input-hint">Event will be created for your club.</small>
                    <?php else: ?>
                        <!-- Show dropdown for admins or multi-club leaders -->
                        <select id="club_id" name="club_id" class="form-input" required style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;">
                             <option value="" disabled <?php echo empty($selectedClubId) ? 'selected' : ''; ?>>
                                -- <?php echo ($userRole === 'admin') ? 'Select any Active Club' : 'Select Club You Lead'; ?> --
                            </option>
                            <?php foreach ($creatableClubs as $club): ?>
                                <option value="<?php echo htmlspecialchars($club['id']); ?>" <?php echo ($selectedClubId == $club['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($club['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($creatableClubs) && $userRole === 'admin') : ?>
                             <p class="error-text input-hint">No active clubs found.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Event Name -->
                <div class="form-group">
                     <label for="event_name">Event Name <span class="required">*</span></label>
                     <input type="text" id="event_name" name="event_name" class="form-input" value="<?php echo htmlspecialchars($eventName); ?>" maxlength="150" required>
                 </div>

                 <!-- Date and Time -->
                <div class="form-group">
                     <label for="event_date_time">Date and Time <span class="required">*</span></label>
                     <input type="datetime-local" id="event_date_time" name="event_date_time" class="form-input" value="<?php echo htmlspecialchars($eventDateTimeInput); ?>" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                 </div>

                 <!-- Location -->
                 <div class="form-group">
                     <label for="event_location">Location (Optional)</label>
                     <input type="text" id="event_location" name="event_location" class="form-input" value="<?php echo htmlspecialchars($eventLocation); ?>" maxlength="255" placeholder="e.g., University Hall Room 101, Online">
                 </div>

                <!-- Description -->
                <div class="form-group">
                     <label for="event_description">Description <span class="required">*</span></label>
                     <textarea id="event_description" name="event_description" class="form-textarea" rows="6" placeholder="Details about the event..." required><?php echo htmlspecialchars($eventDesc); ?></textarea>
                 </div>

                 <!-- Poster Image Upload -->
                 <div class="form-group">
                     <label for="poster_image">Event Poster/Image (Optional)</label>
                     <input type="file" id="poster_image" name="poster_image" class="form-input file-input" accept=".jpg, .jpeg, .png, .gif, .webp">
                      <small class="input-hint">Max: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?> MB. Types: JPG, PNG, GIF, WEBP.</small>
                 </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo ($userRole === 'admin') ? 'Create Active Event' : 'Submit for Review'; ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>

    </div> <!-- End Wrapper -->
</main>

<!-- Styles and Scripts remain the same -->
<style>
    /* Paste styles from create_club, maybe add .form-static-text style */
    .form-static-text {
        padding: 0.75rem 1rem;
        border: 1px solid transparent; /* Match input border visually */
        background-color: #f8f9fa; /* Slightly different background */
        border-radius: 0.375rem;
        color: #495057;
    }
    body.dark .form-static-text {
         background-color: #444488;
         color: #e0e0e0;
    }
    /* ... other styles ... */
</style>
<script> /* ... JS if any ... */ </script>

</body>
</html>