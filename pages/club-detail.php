
<?php
// Check if club ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?page=clubs');
    exit;
}

$clubId = $_GET['id'];

// Get club details
$club = getClubById($clubId);

// If club doesn't exist, redirect to clubs page
if (!$club) {
    header('Location: index.php?page=clubs');
    exit;
}

// Get club members
$members = getClubMembers($clubId);
$memberCount = count($members);

// Check if current user is a member or leader of the club
$isMember = false;
$isLeader = false;

if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    $isMember = isClubMember($userId, $clubId);
    $isLeader = isClubLeader($userId, $clubId);
}

// Process join club request
if (isset($_POST['join_club']) && isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    
    if (joinClub($userId, $clubId)) {
        // Send notification
        sendNotification($userId, 'Club Joined', 'You have successfully joined ' . $club['name']);
        
        // Refresh page to update status
        header('Location: index.php?page=club-detail&id=' . $clubId . '&joined=1');
        exit;
    }
}

// Process leave club request
if (isset($_POST['leave_club']) && isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    
    if (leaveClub($userId, $clubId)) {
        // Send notification
        sendNotification($userId, 'Club Left', 'You have left ' . $club['name']);
        
        // Refresh page to update status
        header('Location: index.php?page=club-detail&id=' . $clubId . '&left=1');
        exit;
    }
}
?>

<div class="pt-10 pb-16">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Success messages -->
        <?php if (isset($_GET['joined'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <p>You have successfully joined the club.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['left'])): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
                <p>You have left the club.</p>
            </div>
        <?php endif; ?>
        
        <!-- Club header -->
        <div class="mb-10 fade-up">
            <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                <!-- Club info -->
                <div>
                    <h1 class="text-4xl font-bold mb-2"><?php echo $club['name']; ?></h1>
                    <p class="text-gray-600 mb-4"><?php echo $club['description']; ?></p>
                    <div class="flex flex-wrap gap-4">
                        <span class="club-tag club-tag-primary">
                            <i class="fas fa-users mr-1"></i> <?php echo $memberCount; ?> members
                        </span>
                        <span class="club-tag club-tag-secondary">
                            <?php echo $club['category']; ?>
                        </span>
                        <span class="club-tag club-tag-secondary">
                            <i class="fas fa-calendar mr-1"></i> <?php echo $club['meeting_schedule']; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="flex gap-3">
                    <?php if (!isset($_SESSION['user'])): ?>
                        <a href="index.php?page=auth" class="btn-primary py-2 px-4">
                            Sign In to Join
                        </a>
                    <?php elseif ($isLeader): ?>
                        <a href="index.php?page=dashboard" class="btn-primary py-2 px-4">
                            Manage Club
                        </a>
                    <?php elseif ($isMember): ?>
                        <form method="POST" action="">
                            <button type="submit" name="leave_club" class="btn-secondary py-2 px-4">
                                Leave Club
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="">
                            <button type="submit" name="join_club" class="btn-primary py-2 px-4 flex items-center">
                                <i class="fas fa-user-plus mr-2"></i> Join Club
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($isMember || $isLeader): ?>
                        <a href="index.php?page=dashboard" class="btn-secondary py-2 px-4 flex items-center">
                            <i class="fas fa-envelope mr-2"></i> Contact
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Club content -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Left column - About and Activities -->
            <div class="md:col-span-2 space-y-8">
                <!-- About section -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up">
                    <h2 class="text-2xl font-semibold mb-4">About the Club</h2>
                    <p class="text-gray-600 mb-4">
                        <?php echo $club['description']; ?>
                    </p>
                    <div class="border-t pt-4 mt-4">
                        <h3 class="text-lg font-medium mb-2">Meeting Schedule</h3>
                        <p class="flex items-center text-gray-600">
                            <i class="fas fa-calendar-alt mr-2 text-indigo-500"></i>
                            <?php echo $club['meeting_schedule']; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Activities section -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 100ms;">
                    <h2 class="text-2xl font-semibold mb-4">Club Activities</h2>
                    <div class="space-y-4">
                        <!-- Placeholder for future events integration (Sprint 2) -->
                        <div class="border-l-4 border-indigo-500 pl-4 py-2">
                            <p class="text-gray-500 italic">
                                Club activities and events will be available in the next update (Sprint 2).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right column - Members -->
            <div class="space-y-8">
                <!-- Club members -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 200ms;">
                    <h2 class="text-2xl font-semibold mb-4">Club Members</h2>
                    
                    <?php if (count($members) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($members as $member): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                            <?php echo strtoupper(substr($member['username'], 0, 1)); ?>
                                        </div>
                                        <div class="ml-3">
                                            <p class="font-medium"><?php echo $member['username']; ?></p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo $member['role'] === 'leader' ? 'Club Leader' : 'Member'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if ($member['role'] === 'leader'): ?>
                                        <i class="fas fa-shield-alt text-indigo-600"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No members yet. Be the first to join!</p>
                    <?php endif; ?>
                </div>
                
                <!-- Join information -->
                <?php if (!$isMember && !$isLeader): ?>
                    <div class="bg-indigo-50 p-6 rounded-lg border border-indigo-100 fade-up" style="animation-delay: 300ms;">
                        <h3 class="text-lg font-semibold mb-2">Interested in joining?</h3>
                        <p class="text-gray-600 mb-4">
                            Joining this club gives you access to all activities, events, and communications.
                        </p>
                        <?php if (isset($_SESSION['user'])): ?>
                            <form method="POST" action="">
                                <button type="submit" name="join_club" class="btn-primary w-full">
                                    Join Now
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="index.php?page=auth" class="btn-primary block text-center w-full">
                                Sign In to Join
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
