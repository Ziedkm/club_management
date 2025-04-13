<?php
session_start();
require_once 'config/database.php'; // Adjust path if needed
// Assuming functions are defined here or in database.php
 // Adjust path if needed

// --- Club Detail Logic ---

// Default values
$club = null;
$members = [];
$memberCount = 0;
$isMember = false;
$isLeader = false;
$userId = $_SESSION['user']['id'] ?? null; // Get user ID if logged in

// 1. Validate Club ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Optional: Set a flash message for the user
    // $_SESSION['error_message'] = 'Invalid Club ID.';
    header('Location: clubs.php'); // Redirect to club list
    exit;
}
$clubId = (int)$_GET['id']; // Cast to integer

// 2. Fetch Club Details
if (function_exists('getClubById')) {
    $club = getClubById($clubId);
} else {
    error_log("Function getClubById does not exist.");
    // Optional: Set error message
}

// Redirect if club not found
if (!$club) {
    // Optional: Set a flash message
    // $_SESSION['error_message'] = 'Club not found.';
    header('Location: clubs.php');
    exit;
}

// 3. Fetch Members
if (function_exists('getClubMembers')) {
    $members = getClubMembers($clubId);
    $memberCount = count($members);
} else {
    error_log("Function getClubMembers does not exist.");
}

// 4. Check User Status (Member/Leader) if logged in
if ($userId) {
    if (function_exists('isClubMember')) {
        $isMember = isClubMember($userId, $clubId);
    } else {
        error_log("Function isClubMember does not exist.");
    }
    if (function_exists('isClubLeader')) {
        $isLeader = isClubLeader($userId, $clubId);
    } else {
        error_log("Function isClubLeader does not exist.");
    }
    if (function_exists('isPending')) {
        $isPending = isPending($userId, $clubId);
    } else {
        error_log("Function isPending does not exist.");
    }
}

// --- Handle Actions (POST Requests) ---

$actionSuccess = false; // Flag to avoid duplicate redirects

// Process Join Club Request
if (!$actionSuccess && isset($_POST['join_club']) && $userId && !$isMember && !$isLeader) {
    if (function_exists('joinClub') && joinClub($userId, $clubId)) {
        if (function_exists('sendNotification')) {
            // Ensure club name exists before using it
            $clubName = $club['name'] ?? 'the club';
            sendNotification($userId, 'Club Joined', 'You have successfully joined ' . $clubName);
        }
        $actionSuccess = true;
        header('Location: club-detail.php?page=club-detail&id=' . $clubId . '&joined=1');
        exit;
    } else {
        // Optional: Handle join failure (e.g., set error message)
        error_log("Failed to join club: User $userId, Club $clubId");
    }
}


// Process Leave Club Request
if (!$actionSuccess && isset($_POST['leave_club']) && $userId && ($isMember || $isPending) && !$isLeader) {
     if (function_exists('leaveClub') && leaveClub($userId, $clubId)) {
         if (function_exists('sendNotification')) {
            $clubName = $club['name'] ?? 'the club';
            sendNotification($userId, 'Club Left', 'You have left ' . $clubName);
         }
         $actionSuccess = true;
         header('Location: club-detail.php?page=club-detail&id=' . $clubId . '&left=1');
         exit;
     } else {
         // Optional: Handle leave failure
         error_log("Failed to leave club: User $userId, Club $clubId");
     }
}
include_once 'header.php'; 

?>


    <!-- Main Content Area -->
    <main class="main-content py-10 pb-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Success Messages -->
            <?php if (isset($_GET['joined'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow" role="alert">
                    <p class="font-medium">Success!</p>
                    <p>You have successfully send a request to the club.</p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['left'])): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded shadow" role="alert">
                    <p class="font-medium">Update</p>
                    <p>You have left the club.</p>
                </div>
            <?php endif; ?>

            <!-- Club Header Section -->
            <div class="mb-10">
                <div class="bg-white p-6 rounded-lg shadow border border-gray-200 flex flex-col md:flex-row justify-between md:items-center gap-4">
                    <!-- Club Info -->
                    <div class="flex-1">
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($club['name'] ?? 'Club Name'); ?></h1>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($club['description'] ?? 'No description available.'); ?></p>
                        <div class="flex flex-wrap gap-x-4 gap-y-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-users mr-1.5"></i> <?php echo $memberCount; ?> members
                            </span>
                            <?php if (!empty($club['category'])): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <?php echo htmlspecialchars($club['category']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($club['meeting_schedule'])): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-calendar-alt mr-1.5"></i> <?php echo htmlspecialchars($club['meeting_schedule']); ?>
                            </span>
                             <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 flex-shrink-0 mt-4 md:mt-0">
                        <?php if (!$userId): // User not logged in ?>
                            <a href="login.php" class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Sign In to Join
                            </a>
                        <?php elseif ($isLeader): // User is the Leader ?>
                            <a href="club-detail.php?page=dashboard&club_id=<?php echo $clubId; ?>" class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <i class="fas fa-cog mr-2"></i> Manage Club
                            </a>
                        <?php elseif ($isMember): // User is a Member (not leader) ?>
                            <form method="POST" action="club-detail.php?page=club-detail&id=<?php echo $clubId; ?>">
                                <button type="submit" name="leave_club" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                     <i class="fas fa-user-minus mr-2"></i> Leave Club
                                </button>
                            </form>
                            <?php elseif ($isPending): // User is a Pending (not Member) ?>
                            <form method="POST" action="club-detail.php?page=club-detail&id=<?php echo $clubId; ?>">
                                <button type="submit" name="leave_club" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fas fa-user-times mr-2"></i> Cancel Request
                                </button>
                            </form>
                        <?php else: // User is logged in, but not member or leader ?>
                            <form method="POST" action="club-detail.php?page=club-detail&id=<?php echo $clubId; ?>">
                                <button type="submit" name="join_club" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-user-plus mr-2"></i> Join Club
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($userId && ($isMember || $isLeader)): // Contact button if logged in and member/leader ?>
                            <a href="/cm/messages.php" class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-envelope mr-2"></i> Contact
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Club Content Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Left Column: About & Activities -->
                <div class="md:col-span-2 space-y-8">
                    <!-- About Section -->
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">About the Club</h2>
                        <div class="prose prose-blue max-w-none text-gray-600">
                            <?php echo !empty($club['description']) ? nl2br(htmlspecialchars($club['description'])) : '<p>No detailed description available.</p>'; ?>
                        </div>
                        <?php if (!empty($club['meeting_schedule'])): ?>
                        <div class="border-t border-gray-200 pt-4 mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Meeting Schedule</h3>
                            <p class="flex items-center text-gray-600">
                                <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                <?php echo htmlspecialchars($club['meeting_schedule']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Activities Section -->
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Club Activities & Events</h2>
                        <div class="space-y-4">
                            <!-- Placeholder - Replace with actual event fetching/display logic later -->
                            <div class="border-l-4 border-blue-500 pl-4 py-2 bg-blue-50 rounded">
                                <p class="text-gray-600 italic">
                                    Club activities and upcoming events information will be displayed here
                                </p>
                            </div>
                             <!-- Example structure for future events: -->
                                

                            <div class="border rounded p-4">
                                <h4 class="font-semibold">Event Title</h4>
                                <p class="text-sm text-gray-500">Date & Time | Location</p>
                                <p class="mt-2 text-gray-600">Event description...</p>
                            </div>
                             
                        </div>
                    </div>
                </div>

                <!-- Right Column: Members & Join Info -->
                <div class="space-y-8">
                    <!-- Members List -->
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Club Members (<?php echo $memberCount; ?>)</h2>

                        <?php if (count($members) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($members as $member): ?>
                                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 last:border-b-0 last:pb-0">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold text-lg">
                                                <?php echo strtoupper(substr($member['username'] ?? '?', 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($member['username'] ?? 'Unknown User'); ?></p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo ($member['role'] ?? 'member') === 'leader' ? 'Club Leader' : 'Member'; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if (($member['role'] ?? 'member') === 'leader'): ?>
                                            <i class="fas fa-shield-alt text-blue-500 text-lg" title="Club Leader"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500">No members have joined yet.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Join Information Box (Conditional) -->
                    <?php if ($userId && !$isMember && !$isLeader): ?>
                        <div class="bg-blue-50 p-6 rounded-lg border border-blue-100 shadow-sm">
                            <h3 class="text-lg font-semibold text-blue-800 mb-2">Interested in joining?</h3>
                            <p class="text-sm text-blue-700 mb-4">
                                Joining this club gives you access to all activities, events, and communications. Click below to become a member!
                            </p>
                            <form method="POST" action="club-detail.php?page=club-detail&id=<?php echo $clubId; ?>">
                                <button type="submit" name="join_club" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-user-plus mr-2"></i> Join Now
                                </button>
                            </form>
                        </div>
                    <?php elseif (!$userId): ?>
                         <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Want to join?</h3>
                            <p class="text-sm text-gray-700 mb-4">
                                Sign in or create an account to join this club and participate in its activities.
                            </p>
                             <a href="login.php" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Sign In / Sign Up
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div> <!-- End container -->
        <?php
// Include footer if needed
include 'footer.php';
?>
    </main>

    <!-- Add any necessary JS files here -->
    <!-- <script src="scripts.js"></script> -->

</body>
</html>