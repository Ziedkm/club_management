<?php
session_start(); // Ensure session is started

// Include database connection and functions
require_once 'config/database.php'; // Provides $pdo
// Assuming functions like getUserNotifications, sendNotification might be here or included within test2.php
// Provides Layout/Navbar etc. - MUST output AFTER potential headers

// --- Dashboard Logic ---

// 1. Authentication Check
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: login.php'); // Redirect non-logged-in users
    exit;
}

// --- Get Current User Data ---
$userId = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'] ?? 'User';
$userEmail = $_SESSION['user']['email'] ?? '';
$userRole = $_SESSION['user']['role'] ?? 'student';

//  Redirect if not a club leader (if this page is ONLY for leaders)

if ($userRole !== 'club_leader' && $userRole !== 'admin') { // Allow admin access too?
    $_SESSION['error_message'] = "Access denied. Leader dashboard requires club leader privileges.";
    header('Location: home.php'); // Or profile.php
    exit;
}

function format_time_ago($datetime, $full = false) {
    // Make sure this is the corrected version without the 'w' property issue
    try {
        $now = new DateTime;
        $timestamp = strtotime($datetime);
        if ($timestamp === false) throw new Exception("Invalid datetime");
        $ago = new DateTime('@'.$timestamp);
        $diff = $now->diff($ago);
        $string = ['y'=>'year','m'=>'month','d'=>'day','h'=>'hour','i'=>'minute','s'=>'second'];
        foreach ($string as $k=>&$v){
            if(property_exists($diff, $k)){
                if($diff->$k) $v=$diff->$k.' '.$v.($diff->$k>1?'s':'');
                else unset($string[$k]);
            } else unset($string[$k]);
        }
        if(!$full)$string=array_slice($string,0,1);
        return $string?implode(', ',$string).' ago':'just now';
    } catch(Exception $e){
        error_log("Time format err ('{$datetime}'): ".$e->getMessage());
        $ts=strtotime($datetime);
        return $ts?date('M j, Y g:i a',$ts):'Invalid date';
    }
}

// --- Initialize Variables ---
$userClubs = [];
$notifications = [];
$pageError = null;
$notificationSuccess = null;
$notificationError = null;

// --- Fetch User's Clubs ---
try {
    if (!isset($pdo)) { throw new Exception("Database connection not available."); }

    // Fetch all clubs the user is part of, getting their role in each
    $queryClubs = "SELECT c.id, c.name, c.description, c.category, c.meeting_schedule, c.status, cm.role as member_role
                   FROM clubs c
                   JOIN club_members cm ON c.id = cm.club_id
                   WHERE cm.user_id = :user_id
                   ORDER BY c.name ASC";
    $stmtClubs = $pdo->prepare($queryClubs);
    $stmtClubs->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmtClubs->execute();
    $userClubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching user clubs for dashboard: " . $e->getMessage());
    $pageError = "Could not load your club information.";
}

// --- Fetch Notifications ---
// IMPORTANT: Ensure getUserNotifications function is defined elsewhere (e.g., functions.php)
if (function_exists('getUserNotifications')) {
    $notifications = getUserNotifications($userId);
}

// --- Process Send Notification Form (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $title = trim($_POST['notification_title'] ?? '');
    $message = trim($_POST['notification_message'] ?? '');
    $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);

    // Validate inputs
    if (empty($title) || empty($message) || $clubId === false) {
        $notificationError = 'Title, Message, and a valid Club selection are required.';
    } else {
        // Check if user is authorized (is a leader of the selected club)
        $isAuthorized = false;
        $clubName = 'Club'; // Default
        foreach ($userClubs as $club) { // Check against already fetched clubs
            if ($club['id'] == $clubId && $club['member_role'] === 'leader') {
                $isAuthorized = true;
                $clubName = $club['name'];
                break;
            }
        }

        if ($isAuthorized) {
            try {
                // Get all members of the club
                $membersQuery = "SELECT user_id FROM club_members WHERE club_id = :club_id";
                $stmtMembers = $pdo->prepare($membersQuery);
                $stmtMembers->bindParam(':club_id', $clubId, PDO::PARAM_INT);
                $stmtMembers->execute();
                $clubMembers = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

                // Send notification to all members
                $sentCount = 0;
                // IMPORTANT: Ensure sendNotification function is defined elsewhere
                if (function_exists('sendNotification')) {
                     $fullTitle = "[{$clubName}] {$title}"; // Add club name context
                    foreach ($clubMembers as $member) {
                        // Optional: Don't send to self?
                        // if ($member['user_id'] != $userId) {
                            if (sendNotification($member['user_id'], $fullTitle, $message)) {
                                $sentCount++;
                            } else {
                                 error_log("Failed to send notification to user {$member['user_id']} for club {$clubId}");
                            }
                        // }
                    }
                     $notificationSuccess = "Notification sent to {$sentCount} member(s) of {$clubName}.";
                } else {
                     $notificationError = 'Notification function is unavailable.';
                     error_log("Function sendNotification does not exist.");
                }

            } catch (Exception $e) {
                $notificationError = "An error occurred while sending notifications.";
                error_log("Error sending notifications for club $clubId: " . $e->getMessage());
            }
        } else {
            $notificationError = 'You are not authorized to send notifications for this club.';
        }
    }
    // Note: No redirect here, message shown on same page load
}

// --- Prepare list of clubs the user leads for the dropdown ---
$ledClubs = array_filter($userClubs, function($club) {
    return $club['member_role'] === 'leader';
});
include_once 'test2.php'; 

?>

<!-- Main Content Area -->
<main class="main-content dashboard-container py-10"> <!-- Added specific class -->
    <div class="dashboard-wrapper max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="dashboard-header mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-1">My Dashboard</h1>
            <p class="text-lg text-gray-600">
                Welcome back, <?php echo htmlspecialchars($username); ?>! Manage your clubs and stay updated.
            </p>
        </div>

        <!-- Display Feedback Messages -->
        <?php if (isset($pageError)): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?></div><?php endif; ?>
        <?php if ($notificationSuccess): ?> <div class="message success-message" role="alert"><?php echo htmlspecialchars($notificationSuccess); ?></div><?php endif; ?>
        <?php if ($notificationError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($notificationError); ?></div><?php endif; ?>


        <!-- Dashboard Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Main Content Column (Clubs, Send Notification) -->
            <div class="lg:col-span-2 space-y-8">

                <!-- My Clubs Section -->
                <section class="card dashboard-section"> <!-- Use card style -->
                    <h2 class="section-heading" style="font-size: 30px; font-weight:500;">My Clubs</h2> <!-- Use consistent heading style -->

                    <?php if (count($userClubs) > 0): ?>
                        <div class="list-container"> <!-- Use list container style -->
                            <?php foreach ($userClubs as $club): ?>
                                <div class=" club-list-item hover:bg-gray-50" style="width: 100%; margin-bottom:10px; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;"> <!-- Use list item style -->
                                    <div class="item-main">
                                        <strong class="item-title">
                                            <?php echo htmlspecialchars($club['name'] ?? 'Unnamed Club'); ?>
                                            <?php if (($club['member_role'] ?? '') === 'leader'): ?>
                                                <span class="role-badge leader-badge">Leader</span>
                                            <?php else: ?>
                                                <span class="role-badge member-badge">Member</span>
                                            <?php endif; ?>
                                             <!-- Optional: Show Club Status -->
                                             <?php if(isset($club['status']) && $club['status'] !== 'active'): ?>
                                                <span class="role-badge status-<?php echo htmlspecialchars($club['status']); ?>"><?php echo ucfirst(htmlspecialchars($club['status'])); ?></span>
                                            <?php endif; ?>
                                        </strong>
                                        <p class="item-description">
                                            <?php $desc=$club['description']??''; echo htmlspecialchars(substr($desc,0,100)).(strlen($desc)>100?'...':''); ?>
                                        </p>
                                        <div class="item-meta-tags" style="margin-top: 3px;"> <!-- Wrapper for tags -->
                                            <?php if (!empty($club['category'])): ?>
                                                <span class="item-meta" style="background-color:var(--primary-color); color:var(--border-color); width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:3px;"><i class="fas fa-tag" ></i> <?php echo htmlspecialchars($club['category']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($club['meeting_schedule'])): ?>
                                                <span class="item-meta" style="background-color:var(--primary-color); color:var(--border-color); width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:3px;"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($club['meeting_schedule']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <!-- Action Button -->
                                    <div class="item-action">
                                         <!-- ** CORRECTED Link ** -->
                                        <a href="<?php echo ($club['member_role'] === 'leader' ? 'manage-club.php?id=' : 'club-detail.php?id='); ?><?php echo $club['id']; ?>"
                                           class="btn btn-primary"> <!-- Use consistent button styles -->
                                            <?php echo ($club['member_role'] === 'leader') ? 'Manage' : 'View Club'; ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-list-message"> <!-- Use consistent empty message style -->
                            <i class="fas fa-door-open icon-large"></i>
                            <h3>No clubs joined yet</h3>
                            <p>Explore the available clubs and join ones that interest you!</p>
                            <a href="index.php?page=clubs" class="btn btn-primary" style="margin-top: 1rem;">Explore Clubs</a>
                        </div>
                    <?php endif; ?>
                </section> <!-- End My Clubs -->

                <?php if ($userRole === 'club_leader' || $userRole === 'admin'): // Show Send Notification only to leaders/admins ?>
                    <?php if (count($ledClubs) > 0): // Only show if they actually lead clubs ?>
                        <!-- Send Notification Section -->
                        <section class="card dashboard-section">
                            <h2 class="section-heading">Send Notification</h2>
                            <form method="POST" action="dashboard.php" class="space-y-4 dashboard-form"> <!-- Post back to dashboard -->
                                <!-- Club Selection -->
                                <div class="form-group">
                                    <label for="club_id" class="form-label">Select Club <span class="required">*</span></label>
                                    <select name="club_id" id="club_id" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required>
                                        <option value="">-- Select a Club You Lead --</option>
                                        <?php foreach ($ledClubs as $club): ?>
                                            <option value="<?php echo htmlspecialchars($club['id']); ?>">
                                                <?php echo htmlspecialchars($club['name'] ?? 'Unnamed Club'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Notification Title -->
                                <div class="form-group"><label for="notification_title" class="form-label">Title <span class="required">*</span></label><input type="text" id="notification_title" name="notification_title" class="form-input" placeholder="e.g., Meeting Reminder" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required></div>
                                <!-- Notification Message -->
                                <div class="form-group"><label for="notification_message" class="form-label">Message <span class="required">*</span></label><textarea id="notification_message" name="notification_message" class="form-textarea" rows="4" placeholder="Enter notification details..." style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required></textarea></div>
                                <!-- Submit Button -->
                                <div class="form-actions"><button type="submit" name="send_notification" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Notification</button></div>
                            </form>
                        </section> <!-- End Send Notification -->
                    <?php endif; // End check if leader leads any club ?>
                <?php endif; // End check for leader/admin role ?>

            </div> <!-- End Main Content Column -->

            <!-- Sidebar Column (Profile, Notifications) -->
            <div class="space-y-8">
               

                <!-- Notifications Card -->
                <section class="card dashboard-section">
                    <h2 class="section-heading">Recent Notifications</h2>
                    <?php if (count($notifications) > 0): ?>
                        <div class="list-container notifications-list max-h-96 overflow-y-auto">
                            <?php foreach ($notifications as $index => $notification): ?>
                                <?php if ($index >= 5) break; ?>
                                <div class="list-item notification-list-item <?php echo !($notification['is_read'] ?? false) ? 'unread' : ''; ?>"> <div class="item-main"> <strong class="item-title notification-title"><?php echo htmlspecialchars($notification['title'] ?? ''); ?></strong> <p class="item-description notification-message"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p> </div> <span class="item-meta notification-time"><i class="fas fa-clock"></i> <?php echo format_time_ago($notification['created_at'] ?? ''); ?></span> </div>
                            <?php endforeach; ?>
                        </div>
                        
                    <?php else: ?>
                         <div class="empty-list-message"><i class="fas fa-bell-slash icon-large"></i><p>No notifications yet.</p></div>
                    <?php endif; ?>
                </section> <!-- End Notifications Card -->

            </div> <!-- End Sidebar Column -->

        </div> <!-- End Dashboard Grid -->

    </div> <!-- End Wrapper -->
    <?php include 'footer.php'; ?>
</main>
<style>
     /* Reuse notification styles */
     .notifications-list { /* Add scroll/height if needed */ 
        max-height: 700px; /* Adjust as needed */
        overflow-y: auto;
     }
     .notification-title { font-weight: bold; }
     .notification-list-item { /* ... */
        padding: 1rem; /* Adjust padding as needed */
        border-bottom: 1px solid #e5e7eb; /* Tailwind gray-200 */
     }
     .notification-list-item.unread { background-color: #f9fafb; } /* Tailwind gray-50 */
     .view-all-link { margin-top: 1rem; text-align: center; }
</style>

</body>
</html>