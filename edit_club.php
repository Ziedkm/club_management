<?php
session_start();

// --- PHP Prerequisites & Admin Check ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php';

// **** CRITICAL: Authorization Check ****
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error_message'] = "Access Denied: Admin privileges required.";
    header('Location: home.php'); // Redirect non-admins
    exit;
}
$adminUserId = $_SESSION['user']['id'];

// --- Input: Get Target Club ID ---
$targetClubId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$targetClubId) {
    $_SESSION['admin_action_message'] = ['type' => 'error', 'text' => 'Invalid or missing club ID.'];
    header('Location: admin.php#admin-clubs'); // Redirect back to admin panel club list tab
    exit;
}

// --- Fetch Target Club Data ---
$clubToEdit = null;
$pageError = null;
try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }
    // Fetch club details, including proposer username if available
    $stmt = $pdo->prepare("SELECT c.*, u.username as proposer_username
                           FROM clubs c
                           LEFT JOIN users u ON c.proposed_by_user_id = u.id
                           WHERE c.id = :id");
    $stmt->bindParam(':id', $targetClubId, PDO::PARAM_INT);
    $stmt->execute();
    $clubToEdit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$clubToEdit) {
        throw new Exception("Club with ID {$targetClubId} not found.");
    }
} catch (Exception $e) {
    error_log("Edit Club Fetch Error: " . $e->getMessage());
    $pageError = "Could not load club data.";
    $clubToEdit = false; // Prevent form display
}

// --- Define available categories ---
// (Should match the list in create_club.php)
$availableCategories = ['Academic', 'Arts & Culture', 'Community Service', 'Recreation', 'Sports', 'Technology', 'Social', 'Other'];
// --- Define available statuses (Admin might change status here too) ---
$availableStatuses = ['pending', 'active', 'rejected'];


// --- Session Flash Messages ---
$editMsg = $_SESSION['edit_club_message'] ?? null;
unset($_SESSION['edit_club_message']);

// --- Initialize Form/Page Variables ---
$formError = null; // **** INITIALIZE HERE ****
// Keep existing pageError initialization
$pageError = $pageError ?? null; // Ensure pageError exists from fetch block


// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $clubToEdit) { // Only process if club data was loaded

    // Retrieve and sanitize input
    $newClubName = trim($_POST['club_name'] ?? '');
    $newClubDesc = trim($_POST['club_description'] ?? '');
    $newClubCat = trim($_POST['club_category'] ?? '');
    $newClubSched = trim($_POST['club_schedule'] ?? '');
    $newClubStatus = trim($_POST['club_status'] ?? ''); // Get new status
    $formError = null;

    // --- Validation ---
    if (empty($newClubName) || empty($newClubDesc) || empty($newClubCat) || empty($newClubStatus)) {
        $formError = "Club Name, Description, Category, and Status are required.";
    } elseif (!in_array($newClubCat, $availableCategories)) {
        $formError = "Invalid category selected.";
    } elseif (!in_array($newClubStatus, $availableStatuses)) {
        $formError = "Invalid status selected.";
    } elseif (strlen($newClubName) > 100) { $formError = "Club Name too long."; }
    elseif (strlen($newClubCat) > 50) { $formError = "Category too long."; }
    // Add other length validations if necessary

    // Check name uniqueness (if changed)
    if (!$formError && $newClubName !== $clubToEdit['name']) {
        $stmtCheck = $pdo->prepare("SELECT id FROM clubs WHERE name = :name AND id != :id");
        $stmtCheck->execute([':name' => $newClubName, ':id' => $targetClubId]);
        if ($stmtCheck->fetch()) {
            $formError = 'Another club with this name already exists.';
        }
        if (!empty($formError)) {
            $_SESSION['edit_club_message'] = ['type' => 'error', 'text' => $formError];
            header("Location: edit_club.php?id=" . $targetClubId); // Redirect back
            exit;
        }
    }

    // --- Perform Update if No Errors ---
    if (empty($formError)) {
        try {
            $sql = "UPDATE clubs SET
                        name = :name,
                        description = :description,
                        category = :category,
                        meeting_schedule = :schedule,
                        status = :status
                    WHERE id = :id";

            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->bindParam(':name', $newClubName);
            $stmtUpdate->bindParam(':description', $newClubDesc);
            $stmtUpdate->bindParam(':category', $newClubCat);
            $stmtUpdate->bindParam(':schedule', $newClubSched);
            $stmtUpdate->bindParam(':status', $newClubStatus);
            $stmtUpdate->bindParam(':id', $targetClubId, PDO::PARAM_INT);

            if ($stmtUpdate->execute()) {
                // --- Handle Post-Approval Actions ---
                // If status was changed *to* 'active' from 'pending' AND proposer exists, make leader
                if ($newClubStatus === 'active' && $clubToEdit['status'] === 'pending' && $clubToEdit['proposed_by_user_id']) {
                    $pdo->beginTransaction(); // Start transaction for safety
                    try {
                        $stmtLeader = $pdo->prepare("INSERT IGNORE INTO club_members (user_id, club_id, role) VALUES (:user_id, :club_id, 'leader')");
                        $stmtLeader->bindParam(':user_id', $clubToEdit['proposed_by_user_id'], PDO::PARAM_INT);
                        $stmtLeader->bindParam(':club_id', $targetClubId, PDO::PARAM_INT);
                        $stmtLeader->execute();
                        $pdo->commit();
                         $_SESSION['admin_action_message'] = ['type' => 'success', 'text' => 'Club updated and activated. Proposer made leader.'];
                    } catch (Exception $leaderEx) {
                        $pdo->rollBack();
                        error_log("Failed to make proposer leader after approval (Club ID {$targetClubId}): ".$leaderEx->getMessage());
                         $_SESSION['admin_action_message'] = ['type' => 'warning', 'text' => 'Club updated and activated, but failed to assign leader automatically. Please assign manually.'];
                    }
                } else {
                     $_SESSION['admin_action_message'] = ['type' => 'success', 'text' => 'Club details updated successfully.'];
                }
                 header('Location: admin.php#admin-clubs'); // Redirect back to club list tab
                 exit;
            } else {
                $formError = 'Failed to update club details in database.';
                error_log("Club update failed for ID {$targetClubId}. PDO Error: " . implode(", ", $stmtUpdate->errorInfo()));
            }
        } catch (Exception $e) {
             $formError = 'An unexpected error occurred during update.';
             error_log("Club update exception for ID {$targetClubId}. Error: " . $e->getMessage());
        }
    }

     // If errors occurred, store in session flash for display after redirect
     if (!empty($formError)) {
         $_SESSION['edit_club_message'] = ['type' => 'error', 'text' => $formError];
         header("Location: edit_club.php?id=" . $targetClubId); // Redirect back to edit form
         exit;
     }
} // End POST handling


// --- NOW START HTML OUTPUT ---
include_once 'header.php'; 
?>

<!-- Main Content Area -->
<main class="main-content edit-club-container">
    <div class="edit-club-wrapper card">

        <!-- Page Header -->
        <div class="section-header">
             <h1 class="section-title">Edit Club Details</h1>
             <?php if ($clubToEdit): ?>
                 <p class="section-subtitle">Modifying: <strong><?php echo htmlspecialchars($clubToEdit['name']); ?></strong></p>
             <?php endif; ?>
        </div>

       <!-- General Page Load Error / Feedback Messages -->
       <?php if ($pageError): ?> <!-- Safe --> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?> <a href="admin.php#admin-clubs" class="text-link">Back</a></div><?php endif; ?>
        <?php if ($editMsg): ?> <!-- Safe --> <div class="message <?php echo $editMsg['type'] === 'success' ? 'success-message' : ($editMsg['type'] === 'warning' ? 'info-message' : 'error-message'); ?>" role="alert"><?php echo htmlspecialchars($editMsg['text']); ?></div><?php endif; ?>
        <?php // This check for $formError might show errors directly if not redirecting on POST failure ?>
        <?php // if ($formError && $_SERVER['REQUEST_METHOD'] === 'POST'): ?> <!-- Safe -->
             <!-- <div class="message error-message" role="alert"><?php // echo htmlspecialchars($formError); ?></div> -->
        <?php // endif; ?>

       <!-- Edit Club Form -->
       <?php if ($clubToEdit && !$pageError): // Safe ?>
            <form method="POST" action="edit_club.php?id=<?php echo $targetClubId; ?>" class="edit-form">
                <input type="hidden" name="target_id" value="<?php echo $targetClubId; ?>">

                <!-- Club Name -->
                <div class="form-group">
                    <label for="club_name">Club Name <span class="required">*</span></label>
                    <input type="text" id="club_name" name="club_name" class="form-input"
                           value="<?php echo htmlspecialchars($clubToEdit['name']); ?>" maxlength="100" required>
                </div>

                <!-- Club Category Dropdown -->
                <div class="form-group">
                    <label for="club_category">Category <span class="required">*</span></label>
                    <select id="club_category" name="club_category" class="form-input" required>
                        <option value="" disabled>-- Select Category --</option>
                        <?php foreach ($availableCategories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($clubToEdit['category'] === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                 <!-- Meeting Schedule -->
                 <div class="form-group">
                    <label for="club_schedule">Meeting Schedule (Optional)</label>
                    <input type="text" id="club_schedule" name="club_schedule" class="form-input"
                           value="<?php echo htmlspecialchars($clubToEdit['meeting_schedule'] ?? ''); ?>" maxlength="100"
                           placeholder="e.g., Tuesdays at 5 PM, Bi-weekly Fridays">
                </div>

                 <!-- Club Description -->
                 <div class="form-group">
                    <label for="club_description">Description <span class="required">*</span></label>
                    <textarea id="club_description" name="club_description" class="form-textarea"
                              rows="5" placeholder="Describe the purpose and activities..."
                              required><?php echo htmlspecialchars($clubToEdit['description'] ?? ''); ?></textarea>
                </div>

                 <hr class="form-divider">

                 <!-- Club Status -->
                  <div class="form-group">
                    <label for="club_status">Club Status <span class="required">*</span></label>
                    <select id="club_status" name="club_status" class="form-input" required>
                         <option value="" disabled>-- Select Status --</option>
                        <?php foreach ($availableStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($clubToEdit['status'] === $status) ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($status)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                     <small class="input-hint">Set to 'Active' to make the club visible and joinable. Changing from 'Pending' to 'Active' will make the proposer the leader.</small>
                </div>

                <!-- Proposer Info (Read Only) -->
                 <div class="form-group">
                    <label>Proposed By</label>
                     <p class="form-static-text"><?php echo htmlspecialchars($clubToEdit['proposer_username'] ?? 'N/A (or deleted user)'); ?></p>
                </div>


                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn btn-primary"> <!-- Consider renaming button name -->
                        <i class="fas fa-save"></i> Update Club Details
                    </button>
                     <a href="admin.php#admin-clubs" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                </div>
            </form>
        <?php endif; // End check for clubToEdit ?>

    </div> <!-- End Wrapper -->
</main>

<!-- Add specific CSS for the edit club page -->
<style>
    /* Reuse styles from create_club/edit_user */
    .edit-club-container .edit-club-wrapper { max-width: 700px; margin: 2rem auto; padding: 2rem; }
    .edit-club-container .section-header { margin-bottom: 1.5rem; text-align: center; max-width: none; }
    .edit-club-container .section-title { font-size: 1.8rem; margin-bottom: 0.5rem; }
    .edit-club-container .section-subtitle { font-size: 1rem; color: #555; } body.dark .edit-club-container .section-subtitle { color: #ccc; }

    .edit-form { display: flex; flex-direction: column; gap: 1.25rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-color); }
    .form-input, .form-textarea, .form-select { /* Base class for inputs */ display: block; width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); transition: border-color 0.2s; background-color: var(--background-color); color: var(--text-color); }
    select.form-input { /* Style select */ padding-right: 2.5rem; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%23aaa%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1em 1em; } body.dark select.form-input { background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%23888%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E');}
     .form-textarea { resize: vertical; min-height: 100px; }
     .form-input:focus, .form-textarea:focus, .form-select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px hsla(210, 100%, 50%, 0.2); }
     body.dark .form-input, body.dark .form-textarea, body.dark .form-select { background-color: #333366; border-color: #444488; color: #e0e0e0; }
     body.dark .form-input:focus, body.dark .form-textarea:focus, body.dark .form-select:focus { border-color: hsl(210, 80%, 60%); box-shadow: 0 0 0 2px hsla(210, 80%, 60%, 0.3); }
     label .required { color: #dc3545; margin-left: 2px; }
     .input-hint { font-size: 0.8rem; color: #777; margin-top: 0.3rem; display: block;} body.dark .input-hint { color: #aaa;}
     .form-divider { border: 0; height: 1px; background-color: var(--border-color); margin: 0.5rem 0; } body.dark .form-divider { background-color: #333366; }
     .form-actions { margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; }
     .form-actions .btn { display: inline-flex; align-items: center; } .form-actions .btn i { margin-right: 0.5rem; }
     .btn-secondary { /* Define if not defined */ background-color: #e5e7eb; color: #374151; border: 1px solid transparent; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; text-decoration: none; transition: background-color .2s;} .btn-secondary:hover { background-color: #d1d5db; } body.dark .btn-secondary { background-color: #4b5563; color: #e5e7eb; } body.dark .btn-secondary:hover { background-color: #6b7280; }
     .form-static-text { padding: 0.75rem 1rem; border: 1px solid transparent; background-color: #f8f9fa; border-radius: 0.375rem; color: #495057; font-style: italic; } body.dark .form-static-text { background-color: #444488; color: #e0e0e0; }

     /* Message styles */
     .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.375rem; border-left-width: 4px; font-weight: 500; } .error-message { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; } .success-message { background-color: #d4edda; color: #155724; border-color: #c3e6cb; } .info-message { background-color: #e2e3e5; color: #383d41; border-color: #d6d8db; } body.dark .error-message { background-color: #58151c; color: #f8d7da; border-color: #a02b37; } body.dark .success-message { background-color: #0c3b19; color: #d4edda; border-color: #1c6c30; } body.dark .info-message { background-color: #343a40; color: #e2e3e5; border-color: #545b62; }
</style>


</body>
</html>