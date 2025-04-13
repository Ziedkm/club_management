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

// --- Input: Get Target User ID ---
$targetUserId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$targetUserId) {
    $_SESSION['admin_action_message'] = ['type' => 'error', 'text' => 'Invalid or missing user ID.'];
    header('Location: admin.php'); // Redirect back to admin panel user list
    exit;
}

// --- Prevent Self-Edit / Editing Other Admins ---
if ($targetUserId === $adminUserId) {
    $_SESSION['admin_action_message'] = ['type' => 'error', 'text' => 'Administrators should edit their own profile via the My Profile page.'];
    header('Location: admin.php');
    exit;
}


// --- Fetch Target User Data ---
$userToEdit = null;
$pageError = null;
try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }
    $stmt = $pdo->prepare("SELECT id, username, email, role, is_banned, ban_reason FROM users WHERE id = :id");
    $stmt->bindParam(':id', $targetUserId, PDO::PARAM_INT);
    $stmt->execute();
    $userToEdit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userToEdit) {
        throw new Exception("User with ID {$targetUserId} not found.");
    }

    // Prevent editing other admins
    if ($userToEdit['role'] === 'admin') {
         $_SESSION['admin_action_message'] = ['type' => 'error', 'text' => 'Cannot edit other administrator accounts via this form.'];
         header('Location: admin.php');
         exit;
    }

} catch (Exception $e) {
    error_log("Edit User Fetch Error: " . $e->getMessage());
    $pageError = "Could not load user data.";
    // Set userToEdit to false to hide the form later
    $userToEdit = false;
}

// --- Allowed Roles (Admin cannot promote others to Admin here) ---
$allowedRoles = ['student', 'club_leader'];

// --- Session Flash Messages ---
$editMsg = $_SESSION['edit_user_message'] ?? null;
unset($_SESSION['edit_user_message']);

// --- Initialize Form/Page Variables ---
$formError = null; // **** INITIALIZE HERE ****
// Initialize pageError in case DB fetch fails too
$pageError = $pageError ?? null; // Keep existing pageError if set by DB fetch

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userToEdit) { // Only process if user data was loaded

    // Retrieve and sanitize input
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newRole = trim($_POST['role'] ?? '');
    $isBanned = isset($_POST['is_banned']); // Checkbox value
    $banReason = $isBanned ? trim($_POST['ban_reason'] ?? '') : null; // Only get reason if banning
    $formError = null;

    // --- Validation ---
    if (empty($newUsername) || empty($newEmail) || empty($newRole)) {
        $formError = "Username, Email, and Role cannot be empty.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $formError = "Invalid email format.";
    } elseif (!in_array($newRole, $allowedRoles)) { // Ensure valid role
        $formError = "Invalid role selected. Cannot assign 'admin' role here.";
    }

    // Check username uniqueness (if changed)
    if (!$formError && $newUsername !== $userToEdit['username']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
        $stmt->execute([':username' => $newUsername, ':id' => $targetUserId]);
        if ($stmt->fetch()) {
            $formError = 'Username already taken by another user.';
        }
    }

    // Check email uniqueness (if changed)
    if (!$formError && $newEmail !== $userToEdit['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $newEmail, ':id' => $targetUserId]);
        if ($stmt->fetch()) {
            $formError = 'Email address already in use by another user.';
        }
    }

    // --- Perform Update if No Errors ---
    if (empty($formError)) {
        try {
             $sql = "UPDATE users SET
                        username = :username,
                        email = :email,
                        role = :role,
                        is_banned = :is_banned,
                        ban_reason = :ban_reason,
                        banned_until = " . ($isBanned ? "NULL" : "NULL") . " -- Set to NULL for permanent or unban
                    WHERE id = :id";

            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->bindParam(':username', $newUsername);
            $stmtUpdate->bindParam(':email', $newEmail);
            $stmtUpdate->bindParam(':role', $newRole);
            $stmtUpdate->bindParam(':is_banned', $isBanned, PDO::PARAM_BOOL);
            $stmtUpdate->bindParam(':ban_reason', $banReason); // Will bind NULL if not banned
            $stmtUpdate->bindParam(':id', $targetUserId, PDO::PARAM_INT);

            if ($stmtUpdate->execute()) {
                $_SESSION['admin_action_message'] = ['type' => 'success', 'text' => 'User profile updated successfully.'];
                header('Location: admin.php'); // Redirect back to user list
                exit;
            } else {
                $formError = 'Failed to update user profile in database.';
                error_log("User profile update failed for ID {$targetUserId}. PDO Error: " . implode(", ", $stmtUpdate->errorInfo()));
            }
        } catch (Exception $e) {
             $formError = 'An unexpected error occurred during update.';
             error_log("User profile update exception for ID {$targetUserId}. Error: " . $e->getMessage());
        }
    }
     // If errors occurred, store in session flash for display after redirect
     if (!empty($formError)) {
        $_SESSION['edit_user_message'] = ['type' => 'error', 'text' => $formError];
        header("Location: edit_user.php?id=" . $targetUserId); // Redirect back to edit form
        exit;
     }

} // End POST handling


// --- NOW START HTML OUTPUT ---
include_once 'header.php'; // Includes <!DOCTYPE>, <head>, opening <body>, <header>
?>

<!-- Main Content Area -->
<main class="main-content edit-user-container">
    <div class="edit-user-wrapper card"> <!-- Reuse card style -->

        <!-- Page Header -->
        <div class="section-header">
             <h1 class="section-title">Edit User Profile</h1>
             <?php if ($userToEdit): ?>
                 <p class="section-subtitle">Modifying details for: <strong><?php echo htmlspecialchars($userToEdit['username']); ?></strong></p>
             <?php endif; ?>
        </div>

        <!-- General Page Load Error / Feedback Messages -->
        <?php if ($pageError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?> <a href="admin.php" class="text-link">Back to Admin Panel</a></div><?php endif; ?>
        <?php if ($editMsg): ?> <div class="message <?php echo $editMsg['type'] === 'success' ? 'success-message' : 'error-message'; ?>" role="alert"><?php echo htmlspecialchars($editMsg['text']); ?></div><?php endif; ?>
        <?php // Display $formError directly if not redirecting on error: ?>
            <?php if ($formError && $_SERVER['REQUEST_METHOD'] === 'POST'): /* Optional: Only show $formError on POST fail */ ?>
             <!-- <div class="message error-message" role="alert"><?php // echo htmlspecialchars($formError); ?></div> -->
        <?php endif; ?>
        <!-- Edit User Form - Only show if user data loaded successfully -->
        <?php if ($userToEdit && !$pageError): ?>
            <form method="POST" action="edit_user.php?id=<?php echo $targetUserId; ?>" class="edit-form">
                <input type="hidden" name="target_id" value="<?php echo $targetUserId; ?>"> <!-- Good practice -->

                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-input"
                           value="<?php echo htmlspecialchars($userToEdit['username']); ?>" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-input"
                           value="<?php echo htmlspecialchars($userToEdit['email']); ?>" required>
                </div>

                <!-- Role -->
                 <div class="form-group">
                    <label for="role">Role <span class="required">*</span></label>
                    <select id="role" name="role" class="form-input" required>
                         <option value="" disabled>-- Select Role --</option>
                        <?php foreach ($allowedRoles as $roleValue): ?>
                            <option value="<?php echo htmlspecialchars($roleValue); ?>" <?php echo ($userToEdit['role'] === $roleValue) ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($roleValue))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                     <small class="input-hint">Cannot promote to Admin via this form.</small>
                </div>

                 <hr class="form-divider">

                 <!-- Ban Status -->
                 <div class="form-group">
                    <label class="checkbox-label" for="is_banned">
                        <input type="checkbox" id="is_banned" name="is_banned" value="1" class="form-checkbox"
                               <?php echo ($userToEdit['is_banned'] ?? false) ? 'checked' : ''; ?>>
                        <span>User is Banned</span>
                    </label>
                 </div>

                 <!-- Ban Reason -->
                 <div class="form-group">
                    <label for="ban_reason">Ban Reason (if banned)</label>
                    <textarea id="ban_reason" name="ban_reason" class="form-textarea"
                              rows="3" placeholder="Enter reason for banning..."><?php echo htmlspecialchars($userToEdit['ban_reason'] ?? ''); ?></textarea>
                     <small class="input-hint">If 'User is Banned' is unchecked, this reason will be cleared.</small>
                 </div>


                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User Profile
                    </button>
                     <a href="admin.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                </div>
            </form>
        <?php endif; // End check for userToEdit ?>

    </div> <!-- End Wrapper -->
</main>

<!-- Add specific CSS for the edit user page -->
<style>
    .edit-user-container .edit-user-wrapper { max-width: 700px; margin: 2rem auto; padding: 2rem; }
    .edit-user-container .section-header { margin-bottom: 1.5rem; text-align: center; max-width: none; }
    .edit-user-container .section-title { font-size: 1.8rem; margin-bottom: 0.5rem; }
    .edit-user-container .section-subtitle { font-size: 1rem; color: #555; } body.dark .edit-user-container .section-subtitle { color: #ccc; }
    .edit-form { display: flex; flex-direction: column; gap: 1.25rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-color); }
    .form-input, .form-textarea, .form-select { /* Use existing styles */ display: block; width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 0.375rem; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); transition: border-color 0.2s; background-color: var(--background-color); color: var(--text-color); }
    select.form-input { /* Style select */ padding-right: 2.5rem; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%23aaa%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1em 1em; } body.dark select.form-input { background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%23888%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E');}
     .form-textarea { resize: vertical; min-height: 80px; }
     .form-input:focus, .form-textarea:focus, .form-select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px hsla(210, 100%, 50%, 0.2); }
     body.dark .form-input, body.dark .form-textarea, body.dark .form-select { background-color: #333366; border-color: #444488; color: #e0e0e0; }
     body.dark .form-input:focus, body.dark .form-textarea:focus, body.dark .form-select:focus { border-color: hsl(210, 80%, 60%); box-shadow: 0 0 0 2px hsla(210, 80%, 60%, 0.3); }
     label .required { color: #dc3545; margin-left: 2px; }
     .input-hint { font-size: 0.8rem; color: #777; margin-top: 0.3rem; display: block;} body.dark .input-hint { color: #aaa;}
     .form-divider { border: 0; height: 1px; background-color: var(--border-color); margin: 0.5rem 0; } body.dark .form-divider { background-color: #333366; }
     .checkbox-label { display: flex; align-items: center; cursor: pointer; }
     .form-checkbox { width: 1rem; height: 1rem; margin-right: 0.5rem; accent-color: var(--primary-color); }
     .form-actions { margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; }
     .form-actions .btn { display: inline-flex; align-items: center; } .form-actions .btn i { margin-right: 0.5rem; }
     .btn-secondary { /* Define if not defined */ background-color: #e5e7eb; color: #374151; border: 1px solid transparent; } .btn-secondary:hover { background-color: #d1d5db; } body.dark .btn-secondary { background-color: #4b5563; color: #e5e7eb; } body.dark .btn-secondary:hover { background-color: #6b7280; }

     
</style>

<!-- Password toggle JS is not needed here as there are no password fields -->


</body>
</html>