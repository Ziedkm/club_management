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
$clubId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

function format_event_datetime($start, $end = null) { /* ... include function ... */ if(!$start) return ['date'=>'Date TBD', 'time'=>'']; $startDate=strtotime($start); $endDate=$end?strtotime($end):null; if(!$startDate) return ['date'=>'Invalid Date', 'time'=>'']; $dateStr=date('D, M j, Y',$startDate); $timeStr=date('g:i A',$startDate); if($endDate&&$endDate>$startDate){if(date('Ymd',$startDate)==date('Ymd',$endDate)){$timeStr.=' - '.date('g:i A',$endDate);}else{$timeStr.=' onwards';}} return ['date'=>$dateStr, 'time'=>$timeStr]; }

// --- Fetch Event Data ---
$events = [];
$userInteractions = ['likes' => [], 'interested' => [], 'attending' => []];

try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }

    // Base query for active events, joining necessary tables
    $sql = "SELECT
                e.id, e.name, e.description, e.event_date, e.event_end_date, e.location, e.poster_image_path, e.created_at,
                e.created_by,
                c.id as club_id, c.name as club_name,
                u.username as creator_username, -- If you want to show who created it
                (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as participant_count,
                (SELECT COUNT(*) FROM event_likes WHERE event_id = e.id) as like_count,
                (SELECT COUNT(*) FROM event_comments WHERE event_id = e.id) as comment_count
            FROM events e
            JOIN clubs c ON e.club_id = c.id
            LEFT JOIN users u ON e.created_by = u.id -- Left join in case creator is deleted
            WHERE e.status = 'active'";

    
    $params = [];
        $sql .= " AND (c.id LIKE :search)";
        $params[':search'] = '%' . $clubId . '%';

    // Ordering (e.g., show upcoming first, then past)
    $sql .= " ORDER BY e.event_date DESC"; // Or ASC for chronological

    // Add Pagination later if needed (LIMIT / OFFSET)

    $stmtEvents = $pdo->prepare($sql);
    $stmtEvents->execute($params);
    $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
    // Optional: Set error message for user
}
$clubMembers = [];

    try {
        // Fetch Members - include department now, order by role then department?
        $stmtMembers = $pdo->prepare("SELECT u.id, u.username, cm.role, cm.department,u.profile_picture_path
                                     FROM users u JOIN club_members cm ON u.id = cm.user_id
                                     WHERE cm.club_id = :club_id AND cm.role IN ('leader', 'member')
                                     ORDER BY FIELD(cm.role, 'leader', 'member'), cm.department ASC, u.username ASC");
        $stmtMembers->bindParam(':club_id', $clubId, PDO::PARAM_INT); $stmtMembers->execute(); $clubMembers = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching club members: " . $e->getMessage());
        // Optional: Set error message for user
    }




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

<main class="main-content pb-0">
<section class="club-header-banner">
            <div class="banner-image-container default-banner ">

<!-- Default Placeholder - Maybe an Icon + Gradient -->

                 <i class="fas fa-users default-banner-icon"></i>

             
        </div>
        
        <div class="banner-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
             <div class="banner-text">
                <span class="club-category-badge"><?php echo htmlspecialchars($club['category']); ?></span>
                <h1 class="club-main-title"><?php echo htmlspecialchars($club['name']); ?></h1>
                <p class="club-member-count"><i class="fas fa-users"></i> <?php echo $memberCount; ?> Members</p>
             </div>
             
        </div>
    </section>
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
                    <a href="manage-club.php?id=<?php echo $clubId; ?>" class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
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
                        <!-- Event Feed -->
                <div class="event-feed">
                 <?php if (count($events) > 0): ?>
                  <?php foreach ($events as $event): ?>
                <?php
                    $liked = $userId ? ($userInteractions['likes'][$event['id']] ?? false) : false;
                    $interested = $userId ? ($userInteractions['interested'][$event['id']] ?? false) : false;
                    $attending = $userId ? ($userInteractions['attending'][$event['id']] ?? false) : false;
                    $formattedDate = format_event_datetime($event['event_date'], $event['event_end_date']);
                    $eventDetailUrl = "event-detail.php?id=" . $event['id']; // URL for event detail
                    $loginUrl = "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']); // Redirect back after login
                ?>
                <div class="border rounded p-4">
                    

                 
                <h4 class="font-semibold"><a href="<?php echo $eventDetailUrl; ?>" class="event-title-link" style="color:black;"><?php echo htmlspecialchars($event['name']); ?></a></h4>
                        <!-- ** Truncated Description ** -->
                        <?php if (!empty($event['poster_image_path']) && file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $event['poster_image_path'])): ?>
                            <!-- ** Aspect Ratio Container for Image ** -->
                             <!-- make it small on the side -->
                            <a href="<?php echo $eventDetailUrl; ?>" class="event-image-link"> 
                                <img src="<?php echo htmlspecialchars($event['poster_image_path']); ?>" alt="Poster for <?php echo htmlspecialchars($event['name']); ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                        
                        <p class="mt-2 text-gray-600">
                            <?php
                                $fullDesc = $event['description'] ?? '';
                                $maxLength = 150; // Adjust character limit
                                echo nl2br(htmlspecialchars(substr($fullDesc, 0, $maxLength)));
                                if (strlen($fullDesc) > $maxLength) {
                                    echo '... <a href="' . $eventDetailUrl . '" class="read-more-link">Read More</a>';
                                }
                            ?>
                        </p>
                        
    

                    <!-- Event Metadata (same) -->
                    <p class="text-sm text-gray-500"><span class="metadata-item" title="<?php echo $formattedDate['date']; ?>">
                            <i class="fas fa-calendar-alt fa-fw"></i> <?php echo $formattedDate['date']; ?>
                        </span> & <span class="metadata-item" title="Time">
                            <i class="fas fa-clock fa-fw"></i> <?php echo $formattedDate['time']; ?>
                         </span> | <?php if(!empty($event['location'])): ?>
                        <span class="metadata-item" title="Location">
                            <i class="fas fa-map-marker-alt fa-fw"></i> <?php echo htmlspecialchars($event['location']); ?>
                        </span>
                        <?php endif; ?></p>
                        


                        </div> <!-- End event-card -->
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-events-message card"> <!-- ... No events message ... --> </div>
        <?php endif; ?>
    </div>         
                    </div> <!-- End event-feed -->
                
            </div>
        </div>
    


        <!-- Right Column: Members & Join Info -->
        <div class="space-y-8">
            <!-- Members List -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Club Members (<?php echo $memberCount; ?>)</h2>

                <?php if (count($clubMembers) > 0): // Use $clubMembers array fetched with picture path ?>
                    <div class="space-y-4">
                        <?php foreach ($clubMembers as $member): ?>
                            <?php
                                // Prepare paths for checking existence and display
                                $memberProfilePicPath = $member['profile_picture_path'] ?? null;
                                $memberProfilePicServerPath = $memberProfilePicPath ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $memberProfilePicPath : null;
                                $showMemberPic = $memberProfilePicPath && $memberProfilePicServerPath && file_exists($memberProfilePicServerPath);
                            ?>
                            <div class="flex items-center justify-between pb-2 border-b border-gray-100 last:border-b-0 last:pb-0">
                                <div class="flex items-center">
                                    <!-- **** MODIFIED AVATAR DISPLAY **** -->
                                    <div class="member-avatar-container"> <!-- New container class -->
                                        <?php if ($showMemberPic): ?>
                                            <img src="<?php echo htmlspecialchars($memberProfilePicPath); ?>" alt="<?php echo htmlspecialchars($member['username']); ?>'s picture" class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold text-lg">
                                        <?php else: ?>
                                            <span class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold text-lg">
                                                <?php echo strtoupper(substr(htmlspecialchars($member['username'] ?? '?'), 0, 1)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- **** END MODIFIED AVATAR DISPLAY **** -->
                                    <div class="ml-3 member-details"> <!-- Added class -->
                                        <p class="member-name"><?php echo htmlspecialchars($member['username'] ?? 'Unknown User'); ?></p>
                                        <p class="text-xs text-gray-500"> <!-- Changed class -->
                                            <?php echo htmlspecialchars($member['department'] ?? 'Member'); // Display department or default ?>
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
                    <p class="text-gray-500">No active members yet (excluding pending).</p>
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

</body>
<style>

.event-image-link{
    display: flex;
    float: right;
    max-width: 100px;
    max-height: 100px;
    margin-left: 10px;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    background-color: #f0f0f0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;

}
/* Header Banner */
.club-header-banner { position: relative; background-color: #e0e7ff; /* Default bg */ color: white; min-height: 250px; display: flex; align-items: flex-end; margin-bottom: 2rem; }
.banner-image-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; }
.banner-image { width: 100%; height: 100%; object-fit: cover; /* Cover area */ }
.banner-image-container.default-banner { background: linear-gradient(45deg, hsl(210, 80%, 60%), hsl(230, 70%, 50%)); display: flex; align-items: center; justify-content: center; }
.default-banner-icon { font-size: 5rem; color: rgba(255, 255, 255, 0.3); }
.banner-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0.1)); /* Dark gradient at bottom */ z-index: 1;}
.banner-content { position: relative; z-index: 2; padding-top: 2rem; padding-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; width: 100%; }

.club-category-badge { display: inline-block; background-color: rgba(255, 255, 255, 0.2); backdrop-filter: blur(5px); color: white; padding: 0.2rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.3);}
.club-main-title { font-size: 2.5rem; font-weight: 700; color: white; line-height: 1.1; margin-bottom: 0.25rem; text-shadow: 1px 1px 3px rgba(0,0,0,0.5); }
.club-member-count { font-size: 1rem; color: rgba(255, 255, 255, 0.9); display: inline-flex; align-items: center; }
.club-member-count i { margin-right: 0.4rem; font-size: 0.9em;}


/* Main Content Grid */
.club-detail-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 2rem; }
@media (min-width: 768px) { .club-detail-grid { grid-template-columns: 2fr 1fr; /* Main content 2/3, sidebar 1/3 */ } }
.club-main-column { display: flex; flex-direction: column; gap: 1.5rem; }
.club-sidebar-column { display: flex; flex-direction: column; gap: 1.5rem; }


 /* Responsive */
 @media (max-width: 767px) {
     .club-header-banner { min-height: 200px; }
     .banner-content { flex-direction: column; align-items: flex-start; }
     .club-main-title { font-size: 2rem; }
     
     .main-content{ margin-left: 0px; }
 }

</style>
</html>

