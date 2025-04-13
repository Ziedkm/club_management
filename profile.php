<?php
session_start(); // MUST be first or very early

// --- PHP Prerequisites & Logic ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php'; // Include function files if separate

// --- Authentication Check ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $redirectUrl = urlencode('profile.php'); // Use profile.php or index.php?page=profile
    header('Location: login.php?redirect=' . $redirectUrl);
    exit;
}
$userId = $_SESSION['user']['id'];

// --- Helper Function (Definition only - no output) ---
function format_time_ago($datetime, $full = false) { /* ... same as before ... */ try { $now = new DateTime; $timestamp = strtotime($datetime); if ($timestamp === false) throw new Exception("Invalid datetime"); $ago = new DateTime('@' . $timestamp); $diff = $now->diff($ago); $string = ['y' => 'year','m' => 'month','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second']; foreach ($string as $k => &$v) { if (property_exists($diff, $k)) { if ($diff->$k) $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); else unset($string[$k]); } else unset($string[$k]); } if (!$full) $string = array_slice($string, 0, 1); return $string ? implode(', ', $string) . ' ago' : 'just now'; } catch (Exception $e) { error_log("Error formatting time ('{$datetime}'): " . $e->getMessage()); $timestamp = strtotime($datetime); return $timestamp ? date('M j, Y g:i a', $timestamp) : 'Invalid date'; } }

// --- Fetch Current User Data (Needed early for username/email in form) ---
$stmtUser = $pdo->prepare("SELECT username, email, role, password FROM users WHERE id = :id");
$stmtUser->bindParam(':id', $userId, PDO::PARAM_INT); $stmtUser->execute();
$currentUserData = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$currentUserData) { session_destroy(); header('Location: login.php?error=user_not_found'); exit; }
$username = $currentUserData['username']; $email = $currentUserData['email']; $userRole = $currentUserData['role']; $currentPasswordHash = $currentUserData['password'];

// --- Initialize Messages & Errors ---
$updateError = $_SESSION['profile_error'] ?? null;
$updateSuccess = $_SESSION['profile_success'] ?? null;
$notificationMsg = $_SESSION['notification_message'] ?? null; // For delete success/error
unset($_SESSION['profile_error'], $_SESSION['profile_success'], $_SESSION['notification_message']);
$pageError = null; // For general page load errors

// --- Handle POST Actions (PRIORITY: Must run before fetching data shown in lists) ---

// --- Handle DELETE Notification ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_notification') {
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);

    if ($notificationId) {
        try {
            // SECURITY: Ensure the notification belongs to the current user before deleting
            $sql = "DELETE FROM notifications WHERE id = :notification_id AND user_id = :user_id";
            $stmtDelete = $pdo->prepare($sql);
            $stmtDelete->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
            $stmtDelete->bindParam(':user_id', $userId, PDO::PARAM_INT);

            if ($stmtDelete->execute()) {
                if ($stmtDelete->rowCount() > 0) {
                    $_SESSION['notification_message'] = ['type' => 'success', 'text' => 'Notification deleted.'];
                } else {
                    // Notification ID didn't exist or didn't belong to the user
                    $_SESSION['notification_message'] = ['type' => 'error', 'text' => 'Could not delete notification (not found or unauthorized).'];
                }
            } else {
                $_SESSION['notification_message'] = ['type' => 'error', 'text' => 'Failed to delete notification.'];
                 error_log("Notification delete failed for user {$userId}, notif {$notificationId}. PDO Error: " . implode(", ", $stmtDelete->errorInfo()));
            }
        } catch (Exception $e) {
            $_SESSION['notification_message'] = ['type' => 'error', 'text' => 'Error deleting notification.'];
            error_log("Notification delete Exception for user {$userId}, notif {$notificationId}. Error: " . $e->getMessage());
        }
    } else {
         $_SESSION['notification_message'] = ['type' => 'error', 'text' => 'Invalid notification ID for deletion.'];
    }
    // Redirect back to profile to show message and updated list
    header('Location: profile.php'); // Or index.php?page=profile
    exit;
}

// --- Handle Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // (Keep the same POST handling logic for profile update as before)
    // Ensure it sets session messages and redirects on success/error
    // Example snippet (replace with full logic from previous answer):
    $newUsername = trim($_POST['username'] ?? ''); $newEmail = trim($_POST['email'] ?? ''); /* ... other vars ... */ $updateError = '';
    /* ... All validation logic ... */
    if(empty($updateError)) { /* ... Database update logic ... */
        if ($stmtUpdate->execute($params)) { $_SESSION['user']['username'] = $newUsername; $_SESSION['user']['email'] = $newEmail; $_SESSION['profile_success'] = 'Profile updated.'; header('Location: profile.php'); exit; } else { $updateError = 'Update failed.'; /* Log error */ }
    }
    if(!empty($updateError)) { $_SESSION['profile_error'] = $updateError; header('Location: profile.php'); exit; } // Redirect even on error
}

// --- Fetch Associated Data (Clubs, Events, Notifications) AFTER potential deletion ---
// (Keep the same fetching logic as before)
$userClubs = []; $userEvents = []; $allNotifications = [];
try { /* ... Same data fetching try-catch block as previous answer ... */
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }
    if ($userRole !== 'admin') { $queryClubs = "SELECT c.*, cm.role as member_role FROM clubs c JOIN club_members cm ON c.id = cm.club_id WHERE cm.user_id = :uid1 ORDER BY c.name ASC"; $stmtClubs = $pdo->prepare($queryClubs); $stmtClubs->bindParam(':uid1', $userId, PDO::PARAM_INT); $stmtClubs->execute(); $userClubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC); }
    if ($userRole !== 'admin') { $queryEvents = "SELECT e.id, e.name, e.event_date, e.location, c.name as club_name FROM events e JOIN event_attendees ea ON e.id = ea.event_id JOIN clubs c ON e.club_id = c.id WHERE ea.user_id = :uid2 ORDER BY e.event_date DESC"; $stmtEvents = $pdo->prepare($queryEvents); $stmtEvents->bindParam(':uid2', $userId, PDO::PARAM_INT); $stmtEvents->execute(); $userEvents = $stmtEvents->fetchAll(PDO::FETCH_ASSOC); }
    $queryNotif = "SELECT * FROM notifications WHERE user_id = :uid3 ORDER BY created_at DESC"; $stmtNotif = $pdo->prepare($queryNotif); $stmtNotif->bindParam(':uid3', $userId, PDO::PARAM_INT); $stmtNotif->execute(); $allNotifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { if (strpos($e->getMessage(), 'event_attendees') !== false || strpos($e->getMessage(), 'events') !== false) { error_log("Events tables might not exist: " . $e->getMessage()); } else { error_log("Profile Data Fetch Error: " . $e->getMessage()); $pageError = "Could not load profile data."; }} catch (Exception $e) { error_log("Profile Page Error: " . $e->getMessage()); $pageError = "An error occurred loading the page."; }


// --- NOW START HTML OUTPUT ---
include_once 'header.php'; // Includes <!DOCTYPE>, <head>, opening <body>, <header>
?>

<!-- Main Content Area -->
<main class="main-content profile-page-container">
    <div class="profile-content-wrapper">

        <!-- Page Header -->
        <div class="profile-header">
             <h1>My Profile</h1>
             <p>Manage your account details, view your activity, and update your password.</p>
        </div>

         <!-- General Page Load Error -->
         <?php if ($pageError): ?> <div class="message error-message" role="alert"><strong>Error:</strong> <?php echo htmlspecialchars($pageError); ?></div><?php endif; ?>
         <!-- Profile Update Messages -->
         <?php if ($updateError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($updateError); ?></div><?php endif; ?>
         <?php if ($updateSuccess): ?> <div class="message success-message" role="alert"><?php echo htmlspecialchars($updateSuccess); ?></div><?php endif; ?>
         <!-- Notification Delete Message -->
         <?php if ($notificationMsg): ?>
            <div class="message <?php echo $notificationMsg['type'] === 'success' ? 'success-message' : 'error-message'; ?>" role="alert">
                <?php echo htmlspecialchars($notificationMsg['text']); ?>
            </div>
         <?php endif; ?>

        <!-- ==== MOVED: Profile Grid Layout (Form + Summary) ==== -->
        <div class="profile-grid">
            <!-- Left Column: Update Form -->
            <div class="profile-form-column card">
                 <!-- Form content remains the same as previous answer -->
                 <h2>Account Information</h2> <p class="card-description">Update your details. Password fields only needed if changing password.</p>
                 <form method="POST" action="profile.php" class="profile-form">
                     <div class="form-group"> <label for="username">Username</label> <div class="input-wrapper icon-input"> <i class="input-icon fas fa-user"></i> <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" class="form-input" required> </div> </div>
                     <div class="form-group"> <label for="email">Email Address</label> <div class="input-wrapper icon-input"> <i class="input-icon fas fa-at"></i> <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="form-input" required> </div> </div>
                     <hr class="form-divider"> <p class="form-section-info">Password Change (Optional)</p>
                     <div class="form-group"> <label for="current_password">Current Password</label> <div class="input-wrapper icon-input password-input-group"> <i class="input-icon fas fa-lock"></i> <input type="password" id="current_password" name="current_password" class="form-input" placeholder="Required to set new password"> <button type="button" class="password-toggle" data-target="current_password" aria-label="Toggle visibility"><i class="fas fa-eye eye-icon"></i><i class="fas fa-eye-slash eye-off-icon"></i></button> </div> </div>
                     <div class="form-group"> <label for="new_password">New Password</label> <div class="input-wrapper icon-input password-input-group"> <i class="input-icon fas fa-lock"></i> <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Leave blank to keep current"> <button type="button" class="password-toggle" data-target="new_password" aria-label="Toggle visibility"><i class="fas fa-eye eye-icon"></i><i class="fas fa-eye-slash eye-off-icon"></i></button> </div> <small class="input-hint">Minimum 6 characters</small> </div>
                     <div class="form-group"> <label for="confirm_password">Confirm New Password</label> <div class="input-wrapper icon-input password-input-group"> <i class="input-icon fas fa-lock"></i> <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Re-enter new password"> <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Toggle visibility"><i class="fas fa-eye eye-icon"></i><i class="fas fa-eye-slash eye-off-icon"></i></button> </div> </div>
                     <div class="form-actions"> <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button> </div>
                 </form>
            </div> <!-- End Form Column -->

            <!-- Right Column: Account Summary -->
            <div class="profile-summary-column card">
                 <!-- Summary content remains the same as previous answer -->
                 <h2>Account Summary</h2> <p class="card-description">Your current role and status.</p> <div class="profile-avatar-section"> <div class="summary-avatar"><?php echo strtoupper(substr(htmlspecialchars($username), 0, 1)); ?></div> <h3><?php echo htmlspecialchars($username); ?></h3> <p class="summary-email"><?php echo htmlspecialchars($email); ?></p> </div> <div class="profile-role-info"> <h4>Role: <span class="role-badge"><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($userRole))); ?></span></h4> <p class="role-description"><?php switch ($userRole) { case 'admin': echo 'Full access.'; break; case 'club_leader': echo 'Manage your clubs.'; break; default: echo 'Join clubs & events.'; } ?></p> </div> <div class="account-actions"> <a href="actions/logout.php" class="btn btn-outline btn-danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a> </div>
            </div> <!-- End Summary Column -->
        </div> <!-- End Profile Grid -->

        <!-- ==== START Tabbed Activity Section ==== -->
        <div class="profile-activity-section">

            <!-- Tab Navigation -->
            <nav class="profile-tabs" aria-label="Profile Activity Sections">
                <?php if ($userRole !== 'admin'): ?>
                <button type="button" class="profile-tab-button active" data-tab-target="#profile-clubs" role="tab" aria-selected="true" aria-controls="profile-clubs">My Clubs</button>
                <button type="button" class="profile-tab-button" data-tab-target="#profile-events" role="tab" aria-selected="false" aria-controls="profile-events">Events</button>
                <?php endif; ?>
                <button type="button" class="profile-tab-button <?php echo ($userRole === 'admin') ? 'active' : ''; ?>" data-tab-target="#profile-notifications" role="tab" aria-selected="<?php echo ($userRole === 'admin') ? 'true' : 'false';?>" aria-controls="profile-notifications">Notifications</button>
            </nav>

            <!-- Tab Content Panels -->
            <div class="profile-tab-content">

                 <?php if ($userRole !== 'admin'): ?>
                <!-- My Clubs Panel -->
                <div id="profile-clubs" class="profile-tab-panel active" role="tabpanel">
                    <div class="card"><h2>My Clubs</h2> <?php /* ... Club list loop from previous answer ... */ if (count($userClubs) > 0): ?><div class="list-container"><?php foreach ($userClubs as $club): ?><div class="list-item club-list-item"> <div class="item-main"> <strong class="item-title"><?php echo htmlspecialchars($club['name']); ?></strong> <?php if (($club['member_role'] ?? '') === 'leader'): ?><span class="role-badge leader-badge">Leader</span><?php else: ?><span class="role-badge member-badge">Member</span><?php endif; ?> <p class="item-description"><?php $desc=$club['description']??''; echo htmlspecialchars(substr($desc,0,120)).(strlen($desc)>120?'...':''); ?></p> </div> <a href="club-detail.php?page=club-detail&id=<?php echo $club['id'] ?? ''; ?>" class="btn btn-secondary btn-sm">View Club</a> </div><?php endforeach; ?></div><?php else: ?> <p class="empty-list-message">No clubs joined. <a href="index.php?page=clubs" class="text-link">Explore!</a></p> <?php endif; ?></div>
                </div>

                <!-- My Events Panel -->
                <div id="profile-events" class="profile-tab-panel" role="tabpanel">
                     <div class="card"><h2>My Registered Events</h2> <?php /* ... Events list loop from previous answer ... */ if (!isset($stmtEvents)): ?><p class="empty-list-message error-text">Event info unavailable.</p><?php elseif (count($userEvents) > 0): ?><div class="list-container"><?php foreach ($userEvents as $event): ?><div class="list-item event-list-item"> <div class="item-main"> <strong class="item-title"><?php echo htmlspecialchars($event['name']); ?></strong> <span class="item-meta"><i class="fas fa-calendar-alt"></i> <?php echo date('D, M j @ g:i A', strtotime($event['event_date'])); ?></span> <span class="item-meta"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location'] ?? 'TBD'); ?></span> <span class="item-meta"><i class="fas fa-users"></i> Club: <?php echo htmlspecialchars($event['club_name'] ?? 'N/A'); ?></span> </div> <a href="index.php?page=event-detail&id=<?php echo $event['id']; ?>" class="btn btn-secondary btn-sm">View Event</a> </div><?php endforeach; ?></div><?php else: ?> <p class="empty-list-message">No events registered. <a href="index.php?page=events" class="text-link">Find events!</a></p> <?php endif; ?></div>
                </div>
                <?php endif; // End check for admin role ?>

                <!-- Notifications Panel -->
                <div id="profile-notifications" class="profile-tab-panel <?php echo ($userRole === 'admin') ? 'active' : ''; ?>" role="tabpanel">
                     <div class="card">
                         <h2>Notifications</h2>
                         <?php if (count($allNotifications) > 0): ?>
                            <div class="list-container notifications-list">
                                <?php foreach ($allNotifications as $notification): ?>
                                    <div class="list-item notification-list-item <?php echo !($notification['is_read'] ?? false) ? 'unread' : ''; ?>">
                                        <div class="item-main"> <!-- Takes most space -->
                                            <strong class="item-title notification-title"><?php echo htmlspecialchars($notification['title'] ?? ''); ?></strong>
                                            <p class="item-description notification-message"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                                        </div>
                                        <div class="notification-meta-action"> <!-- Groups time and delete button -->
                                            <span class="item-meta notification-time">
                                                <i class="fas fa-clock"></i> <?php echo format_time_ago($notification['created_at'] ?? ''); ?>
                                            </span>
                                            <!-- Delete Form/Button -->
                                            <form method="POST" action="profile.php" class="notification-delete-form" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                                <input type="hidden" name="action" value="delete_notification">
                                                <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars($notification['id']); ?>">
                                                <button type="submit" class="btn-delete" title="Delete Notification" aria-label="Delete Notification">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                         <?php else: ?> <p class="empty-list-message">You have no notifications.</p> <?php endif; ?>
                    </div>
                </div>

            </div> <!-- End Tab Content -->
        </div> <!-- ==== END Tabbed Activity Section ==== -->

    </div> <!-- End Wrapper -->
</main>

<!-- Add JS for Tabs and Password Toggle -->
<script> /* ... Same JS from previous answer ... */ document.addEventListener('DOMContentLoaded',function(){const t=document.querySelectorAll('.password-toggle');t.forEach(t=>{t.addEventListener('click',function(){const e=this.getAttribute('data-target'),i=document.getElementById(e);if(i){const t=this.querySelector('.eye-icon'),s=this.querySelector('.eye-off-icon');'password'===i.type?(i.type='text',t&&(t.style.display='none'),s&&(s.style.display='inline'),this.setAttribute('aria-label','Hide')):(i.type='password',t&&(t.style.display='inline'),s&&(s.style.display='none'),this.setAttribute('aria-label','Show'))}});const e=t.getAttribute('data-target'),i=document.getElementById(e),s=t.querySelector('.eye-icon'),o=t.querySelector('.eye-off-icon');i&&s&&o&&('password'===i.type?(s.style.display='inline',o.style.display='none'):(s.style.display='none',o.style.display='inline'))});const e=document.querySelector('.profile-tabs'),i=document.querySelectorAll('.profile-tab-button'),s=document.querySelectorAll('.profile-tab-panel');e&&e.addEventListener('click',t=>{const e=t.target.closest('.profile-tab-button');if(e){const t=e.getAttribute('data-tab-target'),o=document.querySelector(t);o&&(i.forEach(t=>{t.classList.remove('active'),t.setAttribute('aria-selected','false')}),s.forEach(t=>{t.classList.remove('active')}),e.classList.add('active'),e.setAttribute('aria-selected','true'),o.classList.add('active'))}})}); </script>

<!-- Add specific CSS for profile page, tabs, and new delete button -->
<style>
    /* Paste previous profile styles here */
    /* ... (All styles from the previous answer for layout, cards, forms, summary, tabs) ... */
     .profile-page-container .profile-content-wrapper{max-width:1100px;margin:0 auto}.profile-header{margin-bottom:2rem;padding-bottom:1rem;border-bottom:1px solid var(--border-color)}body.dark .profile-header{border-bottom-color:#333366}.profile-header h1{font-size:2.25rem;font-weight:bold;margin-bottom:.25rem;color:var(--text-color)}.profile-header p{font-size:1.1rem;color:#666}body.dark .profile-header p{color:#bbb}.message{padding:1rem;margin-bottom:1.5rem;border-radius:.375rem;border-left-width:4px;font-weight:500}.error-message{background-color:#f8d7da;color:#721c24;border-color:#f5c6cb}.success-message{background-color:#d4edda;color:#155724;border-color:#c3e6cb}.profile-grid{display:grid;grid-template-columns:repeat(1,1fr);gap:2rem;margin-bottom:2.5rem} @media (min-width:768px){.profile-grid{grid-template-columns:2fr 1fr}}.card{padding:1.5rem;background-color:var(--background-color);border:1px solid var(--border-color);border-radius:.5rem;box-shadow:0 2px 5px rgba(0,0,0,.06)}body.dark .card{background-color:#1a1a4a;border-color:#333366}.card h2{font-size:1.5rem;font-weight:600;margin-bottom:.5rem;color:var(--text-color);padding-bottom:.75rem;border-bottom:1px solid var(--border-color);margin-bottom:1.5rem}body.dark .card h2{border-bottom-color:#333366}.card-description{font-size:.95rem;color:#666;margin-bottom:1.5rem}body.dark .card-description{color:#bbb}.profile-form{display:flex;flex-direction:column;gap:1.5rem}.form-group label{display:block;margin-bottom:.5rem;font-weight:500;color:var(--text-color)}.form-input{display:block;width:100%;padding:.75rem 1rem;border:1px solid var(--border-color);border-radius:.375rem;box-shadow:inset 0 1px 2px rgba(0,0,0,.05);transition:border-color .2s}.form-input:focus{outline:none;border-color:var(--primary-color);box-shadow:0 0 0 2px hsla(210,100%,50%,.2)}body.dark .form-input{background-color:#333366;border-color:#444488;color:#e0e0e0}body.dark .form-input:focus{border-color:hsl(210,80%,60%);box-shadow:0 0 0 2px hsla(210,80%,60%,.3)}.input-wrapper{position:relative}.input-icon{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#aaa;pointer-events:none;line-height:1}body.dark .input-icon{color:#888}.icon-input .form-input{padding-left:2.5rem}.password-input-group .form-input{padding-right:2.5rem}.password-toggle{position:absolute;right:.5rem;top:50%;transform:translateY(-50%);background:0 0;border:none;cursor:pointer;padding:.25rem;color:#999;line-height:1}.password-toggle:hover{color:var(--primary-color)}.password-toggle .eye-off-icon{display:none}.form-divider{border:0;height:1px;background-color:var(--border-color);margin:.5rem 0}body.dark .form-divider{background-color:#333366}.form-section-info{font-size:.9rem;color:#666;margin-bottom:-.5rem}body.dark .form-section-info{color:#bbb}.input-hint{font-size:.8rem;color:#888;margin-top:.25rem;display:block}body.dark .input-hint{color:#aaa}.form-actions{margin-top:1rem}.form-actions .btn{display:inline-flex;align-items:center}.form-actions .btn i{margin-right:.5rem}.profile-summary-column .card{display:flex;flex-direction:column}.profile-avatar-section{display:flex;flex-direction:column;align-items:center;text-align:center;margin-bottom:1.5rem}.summary-avatar{width:80px;height:80px;border-radius:50%;background-color:hsl(210,50%,85%);color:hsl(210,60%,40%);display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:2rem;text-transform:uppercase;margin-bottom:1rem;border:3px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,.1)}body.dark .summary-avatar{background-color:hsl(210,40%,40%);color:hsl(210,50%,90%);border-color:#1a1a4a}.profile-avatar-section h3{font-size:1.25rem;font-weight:600;margin-bottom:.1rem;color:var(--text-color)}.summary-email{font-size:.9rem;color:#666}body.dark .summary-email{color:#bbb}.profile-role-info{background-color:rgba(0,0,0,.03);padding:1rem;border-radius:.375rem;margin-bottom:1.5rem;border:1px solid var(--border-color)}body.dark .profile-role-info{background-color:rgba(255,255,255,.05);border-color:#333366}.profile-role-info h4{font-weight:600;margin-bottom:.25rem;color:var(--text-color)}.role-badge{display:inline-block;padding:.15rem .5rem;font-size:.8rem;border-radius:999px;font-weight:500;line-height:1.2;vertical-align:middle}.role-badge.leader-badge{background-color:hsl(45,100%,85%);color:hsl(45,80%,40%)}body.dark .role-badge.leader-badge{background-color:hsl(45,40%,40%);color:hsl(45,50%,90%)}.role-badge.member-badge{background-color:hsl(210,100%,90%);color:hsl(210,80%,50%)}body.dark .role-badge.member-badge{background-color:hsl(210,40%,40%);color:hsl(210,50%,90%)}.profile-role-info .role-description{font-size:.9rem;color:#555;line-height:1.5}body.dark .role-description{color:#ccc}.account-actions{margin-top:auto}.btn-danger{border-color:#f5c6cb;color:#721c24}.btn-danger:hover{background-color:#f8d7da}body.dark .btn-danger{border-color:#b02a37;color:#f8d7da}body.dark .btn-danger:hover{background-color:rgba(248,215,218,.1)}.btn-danger i{margin-right:.5rem}
    .profile-activity-section{margin-top:2.5rem}.profile-tabs{display:inline-flex;background-color:rgba(0,0,0,.05);padding:.3rem;border-radius:999px;margin-bottom:1.5rem;border:1px solid var(--border-color)}body.dark .profile-tabs{background-color:rgba(255,255,255,.08);border-color:#333366}.profile-tab-button{background:0 0;border:none;padding:.6rem 1.25rem;border-radius:999px;cursor:pointer;font-weight:500;color:#555;transition:background-color .2s ease,color .2s ease,box-shadow .2s ease;white-space:nowrap}body.dark .profile-tab-button{color:#bbb}.profile-tab-button:hover:not(.active){color:var(--text-color);background-color:rgba(0,0,0,.04)}body.dark .profile-tab-button:hover:not(.active){background-color:rgba(255,255,255,.07)}.profile-tab-button.active{background-color:var(--background-color);color:var(--primary-color);font-weight:600;box-shadow:0 2px 4px rgba(0,0,0,.08)}body.dark .profile-tab-button.active{background-color:#1a1a4a;box-shadow:0 2px 4px rgba(0,0,0,.2)}.profile-tab-content{}.profile-tab-panel{display:none;animation:fadeIn .3s ease-in-out}.profile-tab-panel.active{display:block}@keyframes fadeIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}
    .list-container{display:flex;flex-direction:column;gap:1rem}.list-item{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:1rem;padding:1rem;border:1px solid var(--border-color);border-radius:.375rem;background-color:var(--background-color)}body.dark .list-item{border-color:#333366;background-color:transparent}.item-main{flex-grow:1;min-width:60%}.item-title{display:block;font-weight:600;color:var(--text-color);margin-bottom:.25rem;font-size:1.05rem}.item-description{font-size:.9rem;color:#555;margin-bottom:.5rem;line-height:1.4}body.dark .item-description{color:#ccc}.item-meta{display:inline-flex;align-items:center;font-size:.8rem;color:#777;margin-right:1rem;margin-bottom:.25rem}body.dark .item-meta{color:#aaa}.item-meta i{margin-right:.4rem;opacity:.8;width:1em;text-align:center}.btn-sm{padding:.3rem .8rem;font-size:.875rem}.btn-secondary{background-color:#e5e7eb;color:#374151;border:1px solid transparent}.btn-secondary:hover{background-color:#d1d5db}body.dark .btn-secondary{background-color:#4b5563;color:#e5e7eb}body.dark .btn-secondary:hover{background-color:#6b7280}.empty-list-message{padding:1.5rem;text-align:center;color:#666;font-size:.95rem;border:1px dashed var(--border-color);border-radius:.375rem}body.dark .empty-list-message{color:#bbb;border-color:#333366}.text-link{color:var(--primary-color);text-decoration:underline;font-weight:500}.error-text{color:#721c24}body.dark .error-text{color:#f8d7da}
    .notification-list-item{padding:.75rem 1rem;flex-wrap:nowrap}.notification-list-item.unread{background-color:hsla(210,100%,50%,.05);border-left:3px solid var(--primary-color)}body.dark .notification-list-item.unread{background-color:rgba(59,130,246,.1);border-left-color:hsl(210,80%,60%)}.notification-title{font-size:.95rem;margin-bottom:.1rem}.notification-message{font-size:.9rem;color:#444;margin-bottom:0}body.dark .notification-message{color:#ddd}
    /* --- NEW: Notification Delete Button Styles --- */
    .notification-meta-action { display: flex; align-items: center; gap: 0.75rem; margin-left: auto; /* Pushes to the right */ padding-left: 1rem; flex-shrink: 0; }
    .notification-time{ margin: 0; /* Reset margin as it's inside the flex container */ padding: 0; }
    .notification-delete-form { margin: 0; padding: 0; display: inline-block; /* Keep form from breaking line */ }
    .btn-delete {
        background: none; border: none; color: #aaa; cursor: pointer; padding: 0.2rem;
        font-size: 0.9rem; line-height: 1; transition: color 0.2s ease;
    }
    .btn-delete:hover { color: #dc3545; /* Red hover */ }
    body.dark .btn-delete { color: #888; }
    body.dark .btn-delete:hover { color: #f8d7da; }

    @media (max-width:767px){.profile-tabs{display:flex;overflow-x:auto;padding:.3rem 0;border-radius:0;border-left:none;border-right:none;margin:0 -1rem 1.5rem}.profile-tab-button{flex-shrink:0}.profile-additional-sections{margin-top:2rem}.list-item{flex-direction:column;align-items:flex-start}.list-item .btn{margin-top:.75rem;align-self:flex-start} .notification-list-item{ align-items: flex-start; } .notification-meta-action { margin-left: 0; width: 100%; display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;} .notification-time{ margin: 0; } }

</style>

</body>
</html>