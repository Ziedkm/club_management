<?php
session_start(); // Ensure session is started

// Include database connection and functions
require_once 'config/database.php'; // Adjust path if needed
// Assuming functions are defined here or in database.php
 // Adjust path if needed

// --- Dashboard Logic ---

// 1. Authentication Check
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=auth'); // Redirect to login/auth page
    exit;
}

// 2. Get Current User Data
$userId = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'] ?? 'User'; // Default if not set
$userEmail = $_SESSION['user']['email'] ?? '';
$userRole = $_SESSION['user']['role'] ?? 'student'; // Default role

// 3. Get User's Clubs
$userClubs = [];
try {
    // Ensure $pdo is available from config/database.php
    if (!isset($pdo)) {
        throw new Exception("Database connection (\$pdo) not available.");
    }
    $query = "SELECT c.*, cm.role as member_role
              FROM clubs c
              JOIN club_members cm ON c.id = cm.club_id
              WHERE cm.user_id = :user_id
              ORDER BY c.name ASC"; // Added ordering
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $userClubs = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
} catch (Exception $e) {
    error_log("Error fetching user clubs: " . $e->getMessage());
    // Optionally set an error message for the user
    $pageError = "Could not load your club information.";
}

// 4. Get Notifications
$notifications = [];
if (function_exists('getUserNotifications')) {
    $notifications = getUserNotifications($userId);
} else {
    error_log("Function getUserNotifications does not exist.");
    // $pageError = "Could not load notifications."; // Optionally inform user
}

// 5. Process Send Notification Form (POST Request)
$notificationSuccess = null;
$notificationError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $title = trim($_POST['notification_title'] ?? '');
    $message = trim($_POST['notification_message'] ?? '');
    $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT); // Validate club ID

    // Validate inputs
    if (empty($title) || empty($message) || $clubId === false) {
        $notificationError = 'All fields are required, and a valid club must be selected.';
    } else {
        // Check if user is authorized (is a leader of the selected club)
        $isAuthorized = false;
        foreach ($userClubs as $club) {
            if ($club['id'] == $clubId && $club['member_role'] === 'leader') {
                $isAuthorized = true;
                break;
            }
        }

        if ($isAuthorized) {
            try {
                // Get all members of the club (excluding the sender maybe?)
                $membersQuery = "SELECT user_id FROM club_members WHERE club_id = :club_id";
                $stmtMembers = $pdo->prepare($membersQuery);
                $stmtMembers->bindParam(':club_id', $clubId, PDO::PARAM_INT);
                $stmtMembers->execute();
                $clubMembers = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

                // Get club name for title prefix
                $clubName = 'Club'; // Default
                foreach($userClubs as $c) { if ($c['id'] == $clubId) {$clubName = $c['name']; break; } } // Get from already fetched data

                // Send notification to all members
                $sentCount = 0;
                if (function_exists('sendNotification')) {
                     $fullTitle = "[{$clubName}] {$title}"; // Add club name context
                    foreach ($clubMembers as $member) {
                         // Optional: Don't send to self? -> if ($member['user_id'] != $userId) { ... }
                        if (sendNotification($member['user_id'], $fullTitle, $message)) {
                            $sentCount++;
                        } else {
                             error_log("Failed to send notification to user {$member['user_id']} for club {$clubId}");
                        }
                    }
                     $notificationSuccess = "Notification sent to {$sentCount} club member(s).";
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
}
include_once 'test2.php';
?>


    <!-- Main Content Area -->
    <main class="main-content py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-10">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-1">My Dashboard</h1>
                <p class="text-lg text-gray-600">
                    Welcome back, <?php echo htmlspecialchars($username); ?>!
                </p>
            </div>

            <!-- General Page Error -->
             <?php if (isset($pageError)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow" role="alert">
                    <p class="font-bold">Error</p>
                    <p><?php echo htmlspecialchars($pageError); ?></p>
                </div>
            <?php endif; ?>

            <!-- Notification Sending Feedback -->
            <?php if ($notificationSuccess): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow" role="alert">
                    <p class="font-bold">Success</p>
                    <p><?php echo htmlspecialchars($notificationSuccess); ?></p>
                </div>
            <?php endif; ?>
            <?php if ($notificationError): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow" role="alert">
                    <p class="font-bold">Error</p>
                    <p><?php echo htmlspecialchars($notificationError); ?></p>
                </div>
            <?php endif; ?>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Main Content Column (Clubs, Send Notification) -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- My Clubs Section -->
                    <div class="bg-white p-4 sm:p-6 rounded-lg shadow border border-gray-200">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">My Clubs</h2>

                        <?php if (count($userClubs) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($userClubs as $club): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors duration-150 ease-in-out">
                                        <div class="flex flex-col md:flex-row justify-between md:items-center gap-3 md:gap-4">
                                            <!-- Club Info -->
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-lg font-medium text-gray-900 mb-1 truncate">
                                                    <?php echo htmlspecialchars($club['name'] ?? 'Unnamed Club'); ?>
                                                    <?php if (($club['member_role'] ?? '') === 'leader'): ?>
                                                        <span class="ml-2 text-xs px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded-full font-semibold align-middle">
                                                            Leader
                                                        </span>
                                                    <?php endif; ?>
                                                </h3>
                                                <p class="text-gray-600 text-sm mb-2 line-clamp-2"> <!-- Limit description lines -->
                                                    <?php echo htmlspecialchars($club['description'] ?? ''); ?>
                                                </p>
                                                <div class="flex flex-wrap gap-2 text-xs">
                                                    <?php if (!empty($club['category'])): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                        <?php echo htmlspecialchars($club['category']); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($club['meeting_schedule'])): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                        <i class="fas fa-calendar-alt mr-1 opacity-75"></i> <?php echo htmlspecialchars($club['meeting_schedule']); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <!-- Action Button -->
                                            <div class="flex-shrink-0 mt-2 md:mt-0">
                                                <a href="index.php?page=club-detail&id=<?php echo $club['id']; ?>" class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 whitespace-nowrap">
                                                    <?php echo ($club['member_role'] ?? '') === 'leader' ? 'Manage' : 'View Club'; ?> <i class="fas fa-arrow-right ml-2 text-xs"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- No Clubs Joined Message -->
                            <div class="text-center py-8 px-4 border-2 border-dashed border-gray-300 rounded-lg">
                                <div class="text-5xl text-gray-400 mb-4">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">No clubs joined yet</h3>
                                <p class="text-gray-500 mb-4">
                                    Explore the available clubs and join ones that interest you!
                                </p>
                                <a href="index.php?page=clubs" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Explore Clubs
                                </a>
                            </div>
                        <?php endif; ?>
                    </div> <!-- End My Clubs -->

                    <?php
                        // Check if user leads any clubs to show the form
                        $leadsAnyClub = false;
                        foreach ($userClubs as $club) {
                            if ($club['member_role'] === 'leader') {
                                $leadsAnyClub = true;
                                break;
                            }
                        }
                    ?>
                    <?php if ($leadsAnyClub): ?>
                        <!-- Send Notification Section -->
                        <div class="bg-white p-4 sm:p-6 rounded-lg shadow border border-gray-200">
                            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Send Notification to Club Members</h2>

                            <form method="POST" action="index.php?page=dashboard" class="space-y-4"> <!-- Post back to dashboard -->
                                <!-- Club Selection -->
                                <div>
                                    <label for="club_id" class="block text-sm font-medium text-gray-700 mb-1">Select Club <span class="text-red-500">*</span></label>
                                    <select name="club_id" id="club_id" class="form-select" required>
                                        <option value="">-- Select a Club You Lead --</option>
                                        <?php foreach ($userClubs as $club): ?>
                                            <?php if ($club['member_role'] === 'leader'): ?>
                                                <option value="<?php echo htmlspecialchars($club['id']); ?>">
                                                    <?php echo htmlspecialchars($club['name'] ?? 'Unnamed Club'); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Notification Title -->
                                <div>
                                    <label for="notification_title" class="block text-sm font-medium text-gray-700 mb-1">Notification Title <span class="text-red-500">*</span></label>
                                    <input
                                        type="text"
                                        id="notification_title"
                                        name="notification_title"
                                        class="form-input"
                                        placeholder="e.g., Upcoming Meeting Reminder"
                                        required
                                    >
                                </div>

                                <!-- Notification Message -->
                                <div>
                                    <label for="notification_message" class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                                    <textarea
                                        id="notification_message"
                                        name="notification_message"
                                        class="form-textarea"
                                        rows="4"
                                        placeholder="Enter the details of your notification here..."
                                        required
                                    ></textarea>
                                </div>

                                <!-- Submit Button -->
                                <div class="pt-2">
                                    <button type="submit" name="send_notification" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="fas fa-paper-plane mr-2"></i> Send Notification
                                    </button>
                                </div>
                            </form>
                        </div> <!-- End Send Notification -->
                    <?php endif; ?>

                </div> <!-- End Main Content Column -->

                <!-- Sidebar Column (Profile, Notifications) -->
                <div class="space-y-8">

                    <!-- User Profile Card -->
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                        <div class="flex flex-col items-center text-center">
                            <!-- Avatar -->
                            <div class="flex-shrink-0 h-20 w-20 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-3xl font-bold mb-4 ring-4 ring-white shadow">
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            </div>
                            <!-- User Info -->
                            <h3 class="text-xl font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($username); ?></h3>
                            <p class="text-sm text-gray-500 mb-4"><?php echo htmlspecialchars($userEmail); ?></p>

                            <!-- Role Display -->
                            <div class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700 mb-4 capitalize">
                                 <?php echo str_replace('_', ' ', htmlspecialchars($userRole)); // Format role nicely ?>
                            </div>

                            <!-- Profile Link -->
                            <a href="index.php?page=profile" class="inline-flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-user-edit mr-2"></i> Manage Profile
                            </a>
                        </div>
                    </div> <!-- End Profile Card -->

                    <!-- Notifications Card -->
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Recent Notifications</h2>

                        <?php if (count($notifications) > 0): ?>
                            <div class="space-y-3 max-h-96 overflow-y-auto"> <!-- Limit height and add scroll -->
                                <?php foreach ($notifications as $index => $notification): ?>
                                     <?php if ($index >= 5) break; // Limit displayed notifications initially ?>
                                    <div class="notification">
                                        <h4 class="notification-title"><?php echo htmlspecialchars($notification['title'] ?? 'Notification'); ?></h4>
                                        <p class="notification-message"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                                        <p class="notification-time">
                                            <?php echo isset($notification['created_at']) ? date('M j, Y g:i a', strtotime($notification['created_at'])) : ''; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (count($notifications) > 5): ?>
                                <div class="mt-4 pt-2 border-t border-gray-200 text-center">
                                    <a href="index.php?page=notifications" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 hover:underline">
                                        View All Notifications <i class="fas fa-angle-right ml-1 text-xs"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- No Notifications Message -->
                            <div class="text-center py-6 px-4 border-2 border-dashed border-gray-300 rounded-lg">
                                <div class="text-4xl text-gray-400 mb-3">
                                    <i class="fas fa-bell-slash"></i>
                                </div>
                                <p class="text-sm text-gray-500">You have no new notifications.</p>
                            </div>
                        <?php endif; ?>
                    </div> <!-- End Notifications Card -->

                </div> <!-- End Sidebar Column -->

            </div> <!-- End Dashboard Grid -->

        </div> <!-- End Container -->
    </main>

    <!-- Optional: Include Footer -->
    <?php // include 'footer.php'; ?>

    <!-- Add any necessary JS files here -->
    <!-- <script src="scripts.js"></script> -->

</body>
</html>