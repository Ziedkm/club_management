<?php
session_start();

// --- PHP Prerequisites & Authorization Check ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php';

// **** CRITICAL: Authorization Check ****
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['error_message'] = "Please log in to view event details.";
    header('Location: login.php');
    exit;
}

$currentUserId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// --- Input: Get Target Event ID ---
$targetEventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$targetEventId) {
    $_SESSION['admin_action_message'] = ['type' => 'error', 'text' => 'Invalid or missing event ID.'];
    // Redirect back to events list, or admin panel if admin
    $redirect_page = ($userRole === 'admin') ? 'admin.php#admin-events' : 'events.php';
    header('Location: ' . $redirect_page);
    exit;
}

// --- Fetch Target Event Data & Related Info ---
$eventToEdit = null;
$activeClubs = []; // Admins need this for club reassignment
$isLeaderOfThisClub = false; // Flag for leader permissions
$isAllowedToEdit = false; // Flag for general edit permission
$pageError = null;

try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }

    // Fetch event details including club ID and status
    $stmtEvent = $pdo->prepare("SELECT e.*, c.name as club_name, c.id as club_id
                                FROM events e
                                JOIN clubs c ON e.club_id = c.id
                                WHERE e.id = :id");
    $stmtEvent->bindParam(':id', $targetEventId, PDO::PARAM_INT);
    $stmtEvent->execute();
    $eventToEdit = $stmtEvent->fetch(PDO::FETCH_ASSOC);

    if (!$eventToEdit) {
        throw new Exception("Event with ID {$targetEventId} not found.");
    }

    // ** Determine Edit Permission **
    if ($userRole === 'admin') {
        $isAllowedToEdit = true; // Admins can edit any event
    } elseif ($userRole === 'club_leader') {
        // Check if the current user is a leader of the event's club
        $stmtCheckLeader = $pdo->prepare("SELECT 1 FROM club_members WHERE user_id = :user_id AND club_id = :club_id AND role = 'leader'");
        $stmtCheckLeader->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
        $stmtCheckLeader->bindParam(':club_id', $eventToEdit['club_id'], PDO::PARAM_INT);
        $stmtCheckLeader->execute();
        if ($stmtCheckLeader->fetch()) {
            $isLeaderOfThisClub = true;
        }
        // Leaders can only edit events of their clubs if status is 'pending' or 'active'
        if ($isLeaderOfThisClub && ($eventToEdit['status'] === 'pending' || $eventToEdit['status'] === 'active')) {
            $isAllowedToEdit = true;
        }
    }

    // If user is not allowed to edit, redirect them away
    if (!$isAllowedToEdit) {
        $_SESSION['error_message'] = "You do not have permission to edit this event.";
        // Redirect based on role
        $redirect_page = ($userRole === 'admin') ? 'admin.php#admin-events' : 'events.php';
        header('Location: ' . $redirect_page);
        exit;
    }

    // ** Fetch Active Clubs for Reassignment Dropdown (ONLY for admins) **
    if ($userRole === 'admin') {
         $stmtClubs = $pdo->prepare("SELECT id, name FROM clubs WHERE status = 'active' ORDER BY name ASC");
         $stmtClubs->execute();
         $activeClubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    error_log("Edit Event Fetch Error: " . $e->getMessage());
    $pageError = "Could not load event data.";
    $eventToEdit = false; // Prevent form display
}

// --- Define available statuses ---
// Leaders cannot change status to Rejected, only Pending/Active
$availableStatuses = ($userRole === 'admin') ? ['pending', 'active', 'rejected'] : ['pending', 'active'];

// --- Session Flash Messages ---
$editMsg = $_SESSION['edit_event_message'] ?? null;
unset($_SESSION['edit_event_message']);

// --- Constants for Upload ---
define('UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/cm/uploads/event_posters/');
define('UPLOAD_URL_PATH', '/cm/uploads/event_posters/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// --- Set Default Timezone ---
date_default_timezone_set('UTC'); // Example: Use your actual timezone

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $eventToEdit && $isAllowedToEdit) { // Only process if user is allowed

    // Retrieve and sanitize input
    $newClubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
    $newEventName = trim($_POST['event_name'] ?? '');
    $newEventDesc = trim($_POST['event_description'] ?? '');
    $newStartDateTimeInput = trim($_POST['event_start_date_time'] ?? '');
    $newEndDateTimeInput = trim($_POST['event_end_date_time'] ?? '');
    $newEventLocation = trim($_POST['event_location'] ?? '');
    $newStatus = trim($_POST['event_status'] ?? '');
    $posterFile = $_FILES['poster_image'] ?? null;
    $deletePoster = isset($_POST['delete_poster']);

    $formError = null;
    $newStartDateTime = null; $newEndDateTime = null;
    $newPosterPathForDb = $eventToEdit['poster_image_path']; // Keep old path by default
    $oldPosterPath = $eventToEdit['poster_image_path'] ? UPLOAD_DIR . basename($eventToEdit['poster_image_path']) : null; // Full path

    // --- Validation ---
    // ... (Basic validation, Date validation, Club selection validation - ADJUSTED BELOW) ...
    if (empty($newEventName) || empty($newEventDesc) || empty($newStartDateTimeInput) || !$newClubId || empty($newStatus)) { $formError = "Required fields missing."; }
    // Validate Status
    elseif (!in_array($newStatus, $availableStatuses)) { $formError = "Invalid status selected."; }
    // Validate Club ID (Admin can reassign, Leader cannot)
    elseif ($userRole === 'club_leader' && $newClubId !== $eventToEdit['club_id']) {
         $formError = "Club leaders cannot reassign an event to a different club.";
    }
    // Validate Start Date/Time
    if (!$formError) { try { $dt = DateTime::createFromFormat('Y-m-d\TH:i', $newStartDateTimeInput, new DateTimeZone(date_default_timezone_get())); if ($dt===false || $dt->format('Y-m-d\TH:i')!==$newStartDateTimeInput) throw new Exception(); $newStartDateTime = $dt->format('Y-m-d H:i:s'); } catch(Exception $e){ $formError = "Invalid Start Date/Time format."; } }
    // Validate End Date/Time
    if (!$formError && !empty($newEndDateTimeInput)) { /* ... End date validation ... */ try { $dtStart=DateTime::createFromFormat('Y-m-d H:i:s',$newStartDateTime); $dtEnd=DateTime::createFromFormat('Y-m-d\TH:i',$newEndDateTimeInput); if($dtEnd===false || $dtEnd->format('Y-m-d\TH:i')!==$newEndDateTimeInput) throw new Exception("Invalid format."); if($dtEnd <= $dtStart) throw new Exception("End must be after start."); $newEndDateTime = $dtEnd->format('Y-m-d H:i:s'); } catch(Exception $e){ $formError = "Invalid End Date/Time: " . $e->getMessage(); } } elseif (!$formError && empty($newEndDateTimeInput)) { $newEndDateTime = null; }
    // Validate Image Upload/Delete
    if (!$formError) { /* ... File upload/delete validation and processing ... */ }


    // --- Perform Update if No Errors ---
    if (empty($formError)) {
        $uploadSuccess = false; // Track if new upload succeeded
        // Handle file upload/delete BEFORE DB update (so we have final path)
        // ... (move_uploaded_file logic and setting $newPosterPathForDb) ...
        if ($posterFile && $posterFile['error'] === UPLOAD_ERR_OK && !$deletePoster) { /* ... Upload logic setting $uploadedFilePath, $newPosterPathForDb ... */ }
        if ($deletePoster) { $newPosterPathForDb = null; } // Set DB path to null if delete checked

        // Ensure $newPosterPathForDb is correctly set after checks
        if($posterFile && $posterFile['error'] !== UPLOAD_ERR_OK && !$deletePoster) { /* Handle upload errors */}

        try {
            $sql = "UPDATE events SET
                        club_id = :club_id,
                        name = :name,
                        description = :description,
                        event_date = :event_date,
                        event_end_date = :event_end_date,
                        location = :location,
                        status = :status,
                        poster_image_path = :poster
                    WHERE id = :id";
            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->bindParam(':club_id', $newClubId, PDO::PARAM_INT);
            $stmtUpdate->bindParam(':name', $newEventName);
            $stmtUpdate->bindParam(':description', $newEventDesc);
            $stmtUpdate->bindParam(':event_date', $newStartDateTime);
            $stmtUpdate->bindParam(':event_end_date', $newEndDateTime);
            $stmtUpdate->bindParam(':location', $newEventLocation);
            $stmtUpdate->bindParam(':status', $newStatus);
            $stmtUpdate->bindParam(':poster', $newPosterPathForDb);
            $stmtUpdate->bindParam(':id', $targetEventId, PDO::PARAM_INT);

            if ($stmtUpdate->execute()) {
                // --- File Cleanup ---
                if ($deletePoster && $oldPosterPath && file_exists($oldPosterPath)) {
                    unlink($oldPosterPath); // Delete file after DB update
                } elseif ($uploadedFilePath && $oldPosterPath && file_exists($oldPosterPath)) {
                     // If a new file replaced the old one, delete the old one
                      unlink($oldPosterPath);
                }

                $_SESSION['admin_action_message'] = ['type' => 'success', 'text' => 'Event updated successfully.'];
                 // Redirect based on role
                $redirect_page = ($userRole === 'admin') ? 'admin.php#admin-events' : 'dashboard.php'; // Leaders redirect to their dashboard
                header('Location: ' . $redirect_page);
                exit;
            } else {
                $formError = 'Failed to update event in database.';
                // If upload succeeded but DB failed, delete the *newly* uploaded file
                if ($uploadedFilePath && file_exists($uploadedFilePath)) unlink($uploadedFilePath);
                error_log("Event update failed for ID {$targetEventId}. PDO Error: " . implode(", ", $stmtUpdate->errorInfo()));
            }
        } catch (Exception $e) {
             $formError = 'An unexpected error occurred during update.';
             error_log("Event update exception for ID {$targetEventId}. Error: " . $e->getMessage());
             if ($uploadedFilePath && file_exists($uploadedFilePath)) unlink($uploadedFilePath); // Cleanup file
        }
    }
     // If errors occurred, store in session flash for display after redirect
     if (!empty($formError)) {
         $_SESSION['edit_event_message'] = ['type' => 'error', 'text' => $formError];
         header("Location: edit_event.php?id=" . $targetEventId); exit;
     }
} // End POST handling


// --- NOW START HTML OUTPUT ---
include_once 'header.php'; 
?>

<!-- Main Content Area -->
<main class="main-content edit-event-container">
    <div class="edit-event-wrapper card">

        <!-- Page Header -->
        <div class="section-header">
             <h1 class="section-title">Edit Event</h1>
             <?php if ($eventToEdit): ?> <p class="section-subtitle">Modifying: <strong><?php echo htmlspecialchars($eventToEdit['name']); ?></strong></p> <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if ($pageError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?> <a href="events.php" class="text-link">Back</a></div><?php endif; ?>
        <?php if ($editMsg): ?> <div class="message <?php echo $editMsg['type'] === 'success' ? 'success-message' : ($editMsg['type'] === 'warning' ? 'info-message' : 'error-message'); ?>" role="alert"><?php echo htmlspecialchars($editMsg['text']); ?></div><?php endif; ?>

        <!-- Edit Event Form -->
        <?php if ($eventToEdit && !$pageError): ?>
            <form method="POST" action="edit_event.php?id=<?php echo $targetEventId; ?>" class="edit-form" enctype="multipart/form-data">
                <input type="hidden" name="target_id" value="<?php echo $targetEventId; ?>">

                <!-- Event Name -->
                <div class="form-group"><label for="event_name">Event Name <span class="required">*</span></label><input type="text" id="event_name" name="event_name" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($eventToEdit['name'] ?? ''); ?>" maxlength="150" required></div>

                <!-- Associated Club -->
                <div class="form-group">
                    <label for="club_id">Associated Club <span class="required">*</span></label>
                    <?php if ($userRole === 'admin'): ?>
                        <select id="club_id" name="club_id" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required <?php echo empty($activeClubs) ? 'disabled' : ''; ?>>
                            <option value="" disabled>-- Select Club --</option>
                            <?php foreach ($activeClubs as $club): ?>
                                <option value="<?php echo htmlspecialchars($club['id']); ?>" <?php echo ($eventToEdit['club_id'] == $club['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($club['name']); ?>
                                </option>
                            <?php endforeach; ?>
                             <?php if(empty($activeClubs)): ?> <option value="" disabled selected>No Active Clubs Available</option> <?php endif; ?>
                        </select>
                    <?php else: // Leaders see static text ?>
                         <input type="hidden" name="club_id" value="<?php echo htmlspecialchars($eventToEdit['club_id']); ?>">
                         <p class="form-static-text"><?php echo htmlspecialchars($eventToEdit['club_name']); ?></p>
                         <small class="input-hint">Cannot change club association.</small>
                    <?php endif; ?>
                </div>

                 <!-- Start Date/Time -->
                <div class="form-group"><label for="event_start_date_time">Start Date and Time <span class="required">*</span></label><input type="datetime-local" id="event_start_date_time" name="event_start_date_time" class="form-input" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($eventToEdit['event_date'] ?? 'now'))); ?>" required min="<?php echo date('Y-m-d\TH:i'); ?>"></div>

                <!-- End Date/Time -->
                <div class="form-group"><label for="event_end_date_time">End Date and Time (Optional)</label><input type="datetime-local" id="event_end_date_time" name="event_end_date_time" class="form-input" value="<?php echo !empty($eventToEdit['event_end_date']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($eventToEdit['event_end_date']))) : ''; ?>" min="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($eventToEdit['event_date'] ?? 'now'))); ?>"> <small class="input-hint">Leave blank if single point in time.</small></div>

                <!-- Location -->
                <div class="form-group"><label for="event_location">Location (Optional)</label><input type="text" id="event_location" name="event_location" class="form-input" value="<?php echo htmlspecialchars($eventToEdit['location'] ?? ''); ?>" maxlength="255" placeholder="e.g., Room 101, Online"></div>
                <!-- Description -->
                <div class="form-group"><label for="event_description">Description <span class="required">*</span></label><textarea id="event_description" name="event_description" class="form-textarea" rows="6" placeholder="Details..." required><?php echo htmlspecialchars($eventToEdit['description'] ?? ''); ?></textarea></div>
                <!-- Status -->
                <div class="form-group">
                    <label for="event_status">Event Status <span class="required">*</span></label>
                    <select id="event_status" name="event_status" class="form-input" required>
                        <?php foreach ($availableStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($eventToEdit['status'] === $status) ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($status)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Poster Image Upload -->
                <div class="form-group">
                    <label for="poster_image">Event Poster/Image</label>
                    <?php if (!empty($eventToEdit['poster_image_path']) && file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $eventToEdit['poster_image_path'])): ?>
                        <div class="current-poster">
                             <p><strong>Current Poster:</strong></p>
                             <img src="<?php echo htmlspecialchars($eventToEdit['poster_image_path']); ?>" alt="Current Poster" width="150">
                             <label class="checkbox-label delete-poster-label">
                                 <input type="checkbox" name="delete_poster" value="1" class="form-checkbox">
                                 <span>Delete current poster</span>
                             </label>
                        </div>
                        <label for="poster_image" style="margin-top: 10px; display: block;">Upload New to Replace (Optional):</label>
                    <?php else: ?>
                         <p class="input-hint">No current poster.</p>
                         <label for="poster_image">Upload Poster (Optional):</label>
                    <?php endif; ?>
                    <input type="file" id="poster_image" name="poster_image" class="form-input file-input" accept=".jpg, .jpeg, .png, .gif, .webp">
                    <small class="input-hint">Max: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?> MB. Types: JPG, PNG, GIF, WEBP.</small>
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Event</button>
                    <a href="<?php echo ($userRole === 'admin') ? 'admin.php#admin-events' : 'dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>

    </div> <!-- End Wrapper -->
    <?php include_once 'footer.php'; ?>
</main>

<!-- Add specific CSS -->
<style>
    /* Reuse styles from edit_user/edit_club */
    .edit-event-container .edit-event-wrapper { max-width: 700px; margin: 2rem auto; padding: 2rem; }
    .edit-event-container .section-header { margin-bottom: 1.5rem; text-align: center; max-width: none; }
    /* ... other common styles ... */
    .form-textarea { min-height: 120px; } /* Ensure decent height for description */
    .current-poster { margin-bottom: 1rem; padding: 1rem; border: 1px dashed var(--border-color); border-radius: 4px; background-color: rgba(0,0,0,0.02);}
    body.dark .current-poster { border-color: #444; background-color: rgba(255,255,255,0.04);}
    .current-poster img { max-width: 150px; height: auto; display: block; margin-top: 0.5rem; margin-bottom: 1rem; border-radius: 4px;}
    .delete-poster-label { margin-top: 0.5rem; font-size: 0.9em;}
    .checkbox-label { display: flex; align-items: center; cursor: pointer; font-size: 0.9em; color: #555;} body.dark .checkbox-label { color: #ccc; }
    .form-checkbox { width: 1rem; height: 1rem; margin-right: 0.5rem; accent-color: var(--primary-color); }
</style>

</body>
</html>