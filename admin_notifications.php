<?php
session_start();

// --- PHP Prerequisites & Admin Check ---
require_once 'config/database.php'; // Provides $pdo
// Include functions IF they exist and DO NOT output HTML
// include_once 'functions.php'; // Should contain sendNotification() if used

// **** CRITICAL: Authorization Check ****
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error_message'] = "Access Denied: Admin privileges required.";
    $role = $_SESSION['user']['role'] ?? null;
    $redirect_page = ($role === 'club_leader') ? 'dashboard.php' : 'home.php';
    header('Location: ' . $redirect_page);
    exit;
}
$adminUserId = $_SESSION['user']['id'];

// --- Fetch Data for Form ---
$allActiveClubs = [];
$pageError = null;
try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }
    // Get all active clubs for the dropdown
    $stmtClubs = $pdo->prepare("SELECT id, name FROM clubs WHERE status = 'active' ORDER BY name ASC");
    $stmtClubs->execute();
    $allActiveClubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Admin Notifications - Fetch Clubs Error: " . $e->getMessage());
    $pageError = "Could not load club list.";
}

// --- Session Flash Messages ---
$formMessage = $_SESSION['admin_notify_message'] ?? null;
unset($_SESSION['admin_notify_message']);

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_notification'])) {

    $targetGroup = $_POST['target_group'] ?? 'all_users'; // 'all_users', 'all_clubs', 'specific_club'
    $specificClubId = filter_input(INPUT_POST, 'specific_club_id', FILTER_VALIDATE_INT);
    $title = trim($_POST['notification_title'] ?? '');
    $message = trim($_POST['notification_message'] ?? '');
    $formError = null;
    $sentCount = 0;

    // Validation
    if (empty($title) || empty($message)) {
        $formError = "Notification Title and Message are required.";
    } elseif ($targetGroup === 'specific_club' && !$specificClubId) {
        $formError = "Please select a specific club when targeting a single club.";
    }

    if (empty($formError)) {
        try {
            if (!isset($pdo)) { throw new Exception("DB connection lost during send."); }

            $targetUserIds = [];

            // Determine Target User IDs based on selection
            if ($targetGroup === 'all_users') {
                // Get all non-admin users (or all users except self if preferred)
                $stmtTarget = $pdo->prepare("SELECT id FROM users WHERE role != 'admin'"); // Exclude admins
                // $stmtTarget = $pdo->prepare("SELECT id FROM users WHERE id != :admin_id"); // Exclude self
                // $stmtTarget->bindParam(':admin_id', $adminUserId, PDO::PARAM_INT);
                $stmtTarget->execute();
                $targetUserIds = $stmtTarget->fetchAll(PDO::FETCH_COLUMN, 0); // Get only the IDs

            } elseif ($targetGroup === 'all_clubs') {
                // Get all unique user IDs who are members of ANY active club
                $stmtTarget = $pdo->prepare("SELECT DISTINCT cm.user_id FROM club_members cm JOIN clubs c ON cm.club_id = c.id WHERE c.status = 'active'");
                $stmtTarget->execute();
                $targetUserIds = $stmtTarget->fetchAll(PDO::FETCH_COLUMN, 0);

            } elseif ($targetGroup === 'specific_club' && $specificClubId) {
                // Get all members of the selected club
                $stmtTarget = $pdo->prepare("SELECT user_id FROM club_members WHERE club_id = :club_id");
                $stmtTarget->bindParam(':club_id', $specificClubId, PDO::PARAM_INT);
                $stmtTarget->execute();
                $targetUserIds = $stmtTarget->fetchAll(PDO::FETCH_COLUMN, 0);
            }

            // Send Notifications (using a function preferably)
            if (count($targetUserIds) > 0) {
                if (function_exists('sendNotification')) {
                    foreach ($targetUserIds as $recipientId) {
                        // Optional: Skip sending to self if admin is also a member?
                         // if ($recipientId == $adminUserId && $targetGroup !== 'all_users') continue;

                        if (sendNotification($recipientId, $title, $message)) {
                            $sentCount++;
                        } else {
                             error_log("Admin Notification: Failed to send to user ID {$recipientId}");
                             // Decide if you want to stop or continue on partial failure
                        }
                    }
                    $_SESSION['admin_notify_message'] = ['type' => 'success', 'text' => "Notification sent to {$sentCount} user(s)."];
                } else {
                    $formError = "Notification sending function is unavailable.";
                    error_log("Function sendNotification not found for admin broadcast.");
                }
            } else {
                 $formError = "No target users found for the selected group.";
            }

        } catch (Exception $e) {
            $formError = "An error occurred while sending notifications.";
            error_log("Admin Notification Exception: " . $e->getMessage());
        }
    } // End if validation passed

    // Store error in session if it occurred during POST handling
    if ($formError) {
        $_SESSION['admin_notify_message'] = ['type' => 'error', 'text' => $formError];
    }
    // Redirect back to the form page
    header('Location: admin_notifications.php');
    exit;

} // End POST handling


// --- NOW START HTML OUTPUT ---
// Assuming test2.php provides the basic layout and admin sidebar link
// You might need to create a specific admin layout include later
include_once 'header.php';
?>

<!-- Main Content Area -->
<main class="main-content admin-panel-container"> <!-- Reuse admin container style -->
    <div class="admin-content-wrapper"> <!-- Reuse admin wrapper style -->

        <!-- Page Header -->
        <div class="admin-header">
            <h1>Send Broadcast Notification</h1>
            <p>Send messages to all users, club members, or a specific club.</p>
        </div>

        <!-- Messages Display -->
        <?php if ($pageError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?></div><?php endif; ?>
        <?php if ($formMessage): ?>
            <div class="message <?php echo $formMessage['type'] === 'success' ? 'success-message' : 'error-message'; ?>" role="alert">
                <?php echo htmlspecialchars($formMessage['text']); ?>
            </div>
        <?php endif; ?>


        <!-- Notification Form -->
        <div class="card"> <!-- Wrap form in a card -->
             <form method="POST" action="admin_notifications.php" class="edit-form"> <!-- Reuse form style -->

                <!-- Target Group Selection -->
                <div class="form-group">
                    <label for="target_group">Send To <span class="required">*</span></label>
                    <select id="target_group" name="target_group" class="form-input" required onchange="toggleSpecificClubSelect(this.value)">
                        <option value="all_users">All Users (excluding Admins)</option>
                        <option value="all_clubs">All Active Club Members</option>
                        <option value="specific_club">Members of a Specific Club</option>
                    </select>
                </div>

                <!-- Specific Club Selection (Initially Hidden) -->
                 <div class="form-group" id="specific_club_group" style="display: none;">
                    <label for="specific_club_id">Select Specific Club <span class="required">*</span></label>
                     <?php if (empty($allActiveClubs)): ?>
                         <p class="error-text input-hint">No active clubs available to select.</p>
                         <input type="hidden" name="specific_club_id" value=""> <!-- Send empty if none available -->
                     <?php else: ?>
                         <select id="specific_club_id" name="specific_club_id" class="form-input">
                             <option value="">-- Select Club --</option>
                             <?php foreach ($allActiveClubs as $club): ?>
                                 <option value="<?php echo htmlspecialchars($club['id']); ?>">
                                     <?php echo htmlspecialchars($club['name']); ?>
                                 </option>
                             <?php endforeach; ?>
                         </select>
                    <?php endif; ?>
                </div>


                <!-- Notification Title -->
                 <div class="form-group">
                    <label for="notification_title">Notification Title <span class="required">*</span></label>
                    <input type="text" id="notification_title" name="notification_title" class="form-input"
                           placeholder="Subject of the notification" maxlength="100" required>
                </div>

                 <!-- Notification Message -->
                 <div class="form-group">
                    <label for="notification_message">Message <span class="required">*</span></label>
                    <textarea id="notification_message" name="notification_message" class="form-textarea"
                              rows="6" placeholder="Enter the full notification content..."
                              required></textarea>
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" name="send_admin_notification" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Notification
                    </button>
                    <a href="admin.php" class="btn btn-secondary" style="margin-left: 10px;">Back to Admin Panel</a>
                </div>

            </form>
        </div> <!-- End card -->

    </div> <!-- End Wrapper -->
    <?php include_once 'footer.php'; ?>
</main>

<!-- JavaScript for conditional select display -->
<script>
    function toggleSpecificClubSelect(selectedValue) {
        const specificClubGroup = document.getElementById('specific_club_group');
        const specificClubSelect = document.getElementById('specific_club_id');
        if (selectedValue === 'specific_club') {
            specificClubGroup.style.display = 'block';
            if(specificClubSelect) specificClubSelect.required = true; // Make required only when visible
        } else {
            specificClubGroup.style.display = 'none';
             if(specificClubSelect) specificClubSelect.required = false; // Make not required when hidden
             if(specificClubSelect) specificClubSelect.value = ''; // Clear selection when hidden
        }
    }

    // Initialize on page load in case of errors/repopulation
    document.addEventListener('DOMContentLoaded', function() {
        const initialTargetGroup = document.getElementById('target_group');
        if (initialTargetGroup) {
            toggleSpecificClubSelect(initialTargetGroup.value);
        }
    });
</script>

<!-- Add specific CSS (can reuse styles from other admin/form pages) -->
<style>
    .admin-content-wrapper{
        /* center the content in the middle of the page */

        align-items: center;
        justify-content: center;

        min-height: 100vh; /* Full height for the page */
        margin: 0 auto; /* Center the content horizontally */

        
        font-family: Arial, sans-serif; /* Use a clean font for readability */
        font-size: 1rem; /* Base font size */
        line-height: 1.5; /* Line height for readability */

    }
    /* Reuse styles from edit_user.php / create_club.php / admin.php */
    .admin-panel-container .admin-content-wrapper { max-width: 800px; } /* Adjust width */
    /* Add any specific styles if needed */
    .admin-header { margin-bottom: 2rem;
        text-align: center; /* Center the header text */
        font-size: 1.5rem; /* Larger font size for header */
        color: var(--text-color); /* Darker color for better contrast */
        font-weight: bold; /* Bold font for emphasis */
        padding: 1rem; /* Padding around the header */
 /* Light background for header */
        margin-top: 20px;
        border-radius: 0.5rem; /* Rounded corners for header */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
        margin-bottom: 2rem; /* Space between header and form */
        border: 1px solid var(--border-color); /* Border around header */
     } /* Space between header and form */
     .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.375rem; border-left-width: 4px; font-weight: 500; } .error-message { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; } .success-message { background-color: #d4edda; color: #155724; border-color: #c3e6cb; } .info-message { background-color: #e2e3e5; color: #383d41; border-color: #d6d8db; } body.dark .error-message { background-color: #58151c; color: #f8d7da; border-color: #a02b37; } body.dark .success-message { background-color: #0c3b19; color: #d4edda; border-color: #1c6c30; } body.dark .info-message { background-color: #343a40; color: #e2e3e5; border-color: #545b62; }
     .card { padding: 1.5rem; background-color: var(--background-color); border: 1px solid var(--border-color); border-radius: 0.5rem; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.06); } body.dark .card { background-color: #1a1a4a; border-color: #333366; }
     .edit-form { display: flex; flex-direction: column; gap: 1.25rem; }
     .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-color); }
     .form-input, .form-textarea, .form-select { display: block; width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); transition: border-color 0.2s; background-color: var(--background-color); color: var(--text-color); }
     select.form-input { padding-right: 2.5rem; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%23aaa%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1em 1em; } body.dark select.form-input { background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%23888%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E');}
     .form-textarea { resize: vertical; min-height: 120px; }
     .form-input:focus, .form-textarea:focus, .form-select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px hsla(210, 100%, 50%, 0.2); }
     body.dark .form-input, body.dark .form-textarea, body.dark .form-select { background-color: #333366; border-color: #444488; color: #e0e0e0; }
     body.dark .form-input:focus, body.dark .form-textarea:focus, body.dark .form-select:focus { border-color: hsl(210, 80%, 60%); box-shadow: 0 0 0 2px hsla(210, 80%, 60%, 0.3); }
     label .required { color: #dc3545; margin-left: 2px; }
     .input-hint { font-size: 0.8rem; color: #777; margin-top: 0.3rem; display: block;} body.dark .input-hint { color: #aaa;}
     .form-actions { margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; }
     .form-actions .btn { display: inline-flex; align-items: center; } .form-actions .btn i { margin-right: 0.5rem; }
     .btn-secondary { background-color: #e5e7eb; color: #374151; border: 1px solid transparent; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; text-decoration: none; transition: background-color .2s;} .btn-secondary:hover { background-color: #d1d5db; } body.dark .btn-secondary { background-color: #4b5563; color: #e5e7eb; } body.dark .btn-secondary:hover { background-color: #6b7280; }
      .error-text { color: #721c24; } body.dark .error-text { color: #f8d7da; }
</style>


</body>
</html>