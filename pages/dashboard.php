
<?php
// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=auth');
    exit;
}

// Get current user data
$userId = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];
$userRole = $_SESSION['user']['role'];

// Get clubs the user is a member of or leads
$query = "SELECT c.*, cm.role as member_role 
          FROM clubs c 
          JOIN club_members cm ON c.id = cm.club_id 
          WHERE cm.user_id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$userClubs = $stmt->fetchAll();

// Get notifications for the user
$notifications = getUserNotifications($userId);

// Process send notification form
if (isset($_POST['send_notification'])) {
    $title = $_POST['notification_title'];
    $message = $_POST['notification_message'];
    $clubId = $_POST['club_id'];
    
    // Validate inputs
    if (empty($title) || empty($message) || empty($clubId)) {
        $notificationError = 'All fields are required.';
    } else {
        // Check if user is authorized to send notifications for this club
        $isAuthorized = false;
        foreach ($userClubs as $club) {
            if ($club['id'] == $clubId && $club['member_role'] == 'leader') {
                $isAuthorized = true;
                break;
            }
        }
        
        if ($isAuthorized) {
            // Get all members of the club
            $membersQuery = "SELECT user_id FROM club_members WHERE club_id = :club_id";
            $stmt = $pdo->prepare($membersQuery);
            $stmt->bindParam(':club_id', $clubId);
            $stmt->execute();
            $clubMembers = $stmt->fetchAll();
            
            // Get club name
            $clubQuery = "SELECT name FROM clubs WHERE id = :club_id";
            $stmt = $pdo->prepare($clubQuery);
            $stmt->bindParam(':club_id', $clubId);
            $stmt->execute();
            $clubInfo = $stmt->fetch();
            $clubName = $clubInfo['name'];
            
            // Send notification to all members
            $sentCount = 0;
            foreach ($clubMembers as $member) {
                $fullTitle = "[{$clubName}] {$title}";
                if (sendNotification($member['user_id'], $fullTitle, $message)) {
                    $sentCount++;
                }
            }
            
            $notificationSuccess = "Notification sent to {$sentCount} club members.";
        } else {
            $notificationError = 'You are not authorized to send notifications for this club.';
        }
    }
}
?>

<div class="pt-10 pb-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="mb-10 fade-up">
            <h1 class="text-4xl font-bold mb-2">My Dashboard</h1>
            <p class="text-lg text-gray-600">
                Welcome back, <?php echo $username; ?>
            </p>
        </div>
        
        <?php if (isset($notificationSuccess)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <?php echo $notificationSuccess; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($notificationError)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <?php echo $notificationError; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main content column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- My Clubs section -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up">
                    <h2 class="text-2xl font-semibold mb-4">My Clubs</h2>
                    
                    <?php if (count($userClubs) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($userClubs as $club): ?>
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                                        <div>
                                            <h3 class="text-lg font-medium mb-1">
                                                <?php echo $club['name']; ?>
                                                <?php if ($club['member_role'] === 'leader'): ?>
                                                    <span class="ml-2 text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                                        Leader
                                                    </span>
                                                <?php endif; ?>
                                            </h3>
                                            <p class="text-gray-600 text-sm mb-2">
                                                <?php echo substr($club['description'], 0, 100) . (strlen($club['description']) > 100 ? '...' : ''); ?>
                                            </p>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="club-tag club-tag-secondary">
                                                    <?php echo $club['category']; ?>
                                                </span>
                                                <span class="club-tag club-tag-secondary">
                                                    <i class="fas fa-calendar-alt mr-1"></i> <?php echo $club['meeting_schedule']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <a href="index.php?page=club-detail&id=<?php echo $club['id']; ?>" class="btn-primary py-2 px-4 block text-center">
                                                <?php echo $club['member_role'] === 'leader' ? 'Manage' : 'View'; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 border rounded-lg">
                            <div class="text-5xl text-gray-300 mb-4">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="text-xl font-semibold mb-2">No clubs joined yet</h3>
                            <p class="text-gray-600 mb-4">
                                You haven't joined any clubs yet. Explore clubs to find ones that interest you.
                            </p>
                            <a href="index.php?page=clubs" class="btn-primary py-2 px-4 inline-block">
                                Explore Clubs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count(array_filter($userClubs, function($club) { return $club['member_role'] === 'leader'; })) > 0): ?>
                    <!-- Send Notification section (only for club leaders) -->
                    <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 100ms;">
                        <h2 class="text-2xl font-semibold mb-4">Send Notification</h2>
                        
                        <form method="POST" action="" class="space-y-4">
                            <!-- Club selection -->
                            <div class="form-group">
                                <label for="club_id" class="form-label">Select Club</label>
                                <select name="club_id" id="club_id" class="form-input" required>
                                    <option value="">-- Select Club --</option>
                                    <?php foreach ($userClubs as $club): ?>
                                        <?php if ($club['member_role'] === 'leader'): ?>
                                            <option value="<?php echo $club['id']; ?>">
                                                <?php echo $club['name']; ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Notification title -->
                            <div class="form-group">
                                <label for="notification_title" class="form-label">Notification Title</label>
                                <input 
                                    type="text" 
                                    id="notification_title" 
                                    name="notification_title" 
                                    class="form-input"
                                    placeholder="Enter notification title"
                                    required
                                >
                            </div>
                            
                            <!-- Notification message -->
                            <div class="form-group">
                                <label for="notification_message" class="form-label">Message</label>
                                <textarea 
                                    id="notification_message" 
                                    name="notification_message" 
                                    class="form-input"
                                    rows="4"
                                    placeholder="Enter notification message"
                                    required
                                ></textarea>
                            </div>
                            
                            <!-- Submit button -->
                            <button type="submit" name="send_notification" class="btn-primary py-2 px-4">
                                <i class="fas fa-paper-plane mr-2"></i> Send Notification
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar column -->
            <div class="space-y-8">
                <!-- User profile card -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 200ms;">
                    <div class="flex flex-col items-center text-center">
                        <div class="h-20 w-20 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold mb-4">
                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                        </div>
                        <h3 class="text-xl font-semibold mb-1"><?php echo $username; ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo $_SESSION['user']['email']; ?></p>
                        
                        <div class="bg-gray-100 p-3 rounded-lg w-full text-center mb-4">
                            <p class="font-medium">
                                <?php 
                                    switch ($userRole) {
                                        case 'admin':
                                            echo 'Club Administrator';
                                            break;
                                        case 'club_leader':
                                            echo 'Club Leader';
                                            break;
                                        default:
                                            echo 'Student';
                                    }
                                ?>
                            </p>
                        </div>
                        
                        <a href="index.php?page=profile" class="btn-primary py-2 px-4 w-full text-center">
                            Manage Profile
                        </a>
                    </div>
                </div>
                
                <!-- Notifications card -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 300ms;">
                    <h2 class="text-2xl font-semibold mb-4">Notifications</h2>
                    
                    <?php if (count($notifications) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($notifications as $index => $notification): ?>
                                <?php if ($index >= 5) break; // Limit to 5 notifications ?>
                                <div class="notification">
                                    <h4 class="notification-title"><?php echo $notification['title']; ?></h4>
                                    <p class="mb-1"><?php echo $notification['message']; ?></p>
                                    <p class="notification-time">
                                        <?php echo date('M j, Y g:i a', strtotime($notification['created_at'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($notifications) > 5): ?>
                            <div class="mt-4 text-center">
                                <a href="#" class="text-indigo-600 hover:underline">
                                    View All Notifications
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <div class="text-4xl text-gray-300 mb-2">
                                <i class="fas fa-bell-slash"></i>
                            </div>
                            <p class="text-gray-600">No notifications yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
