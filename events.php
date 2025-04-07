<?php
session_start();

// --- PHP Prerequisites & Logic ---
require_once 'config/database.php'; // Provides $pdo


// --- Helper Functions ---
function format_time_ago($datetime, $full = false) { /* ... include function ... */ try { $now = new DateTime; $timestamp = strtotime($datetime); if ($timestamp === false) throw new Exception("Invalid datetime"); $ago = new DateTime('@' . $timestamp); $diff = $now->diff($ago); $string = ['y' => 'year','m' => 'month','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second']; foreach ($string as $k => &$v) { if (property_exists($diff, $k)) { if ($diff->$k) $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); else unset($string[$k]); } else unset($string[$k]); } if (!$full) $string = array_slice($string, 0, 1); return $string ? implode(', ', $string) . ' ago' : 'just now'; } catch (Exception $e) { error_log("Error formatting time ('{$datetime}'): " . $e->getMessage()); $timestamp = strtotime($datetime); return $timestamp ? date('M j, Y g:i a', $timestamp) : 'Invalid date'; } }
function format_event_datetime($start, $end = null) { /* ... include function ... */ if(!$start) return ['date'=>'Date TBD', 'time'=>'']; $startDate=strtotime($start); $endDate=$end?strtotime($end):null; if(!$startDate) return ['date'=>'Invalid Date', 'time'=>'']; $dateStr=date('D, M j, Y',$startDate); $timeStr=date('g:i A',$startDate); if($endDate&&$endDate>$startDate){if(date('Ymd',$startDate)==date('Ymd',$endDate)){$timeStr.=' - '.date('g:i A',$endDate);}else{$timeStr.=' onwards';}} return ['date'=>$dateStr, 'time'=>$timeStr]; }

// --- Authentication & Variables ---
$currentUserId = $_SESSION['user']['id'] ?? null;
$pageError = null;
$interactionMsg = $_SESSION['interaction_message'] ?? null;
unset($_SESSION['interaction_message']);

// --- Search/Filter ---
$searchQuery = trim($_GET['search'] ?? '');

// --- Fetch Event Data ---
$events = [];
$userInteractions = ['likes' => [], 'interested' => [], 'attending' => []];

try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }

    // Base query for active events, joining necessary tables
    $sql = "SELECT
                e.id, e.name, e.description, e.event_date, e.event_end_date, e.location, e.poster_image_path, e.created_at,
                c.id as club_id, c.name as club_name,
                u.username as creator_username, -- If you want to show who created it
                (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as participant_count,
                (SELECT COUNT(*) FROM event_likes WHERE event_id = e.id) as like_count,
                (SELECT COUNT(*) FROM event_comments WHERE event_id = e.id) as comment_count
            FROM events e
            JOIN clubs c ON e.club_id = c.id
            LEFT JOIN users u ON e.created_by = u.id -- Left join in case creator is deleted
            WHERE e.status = 'active'";

    // Add Search condition
    $params = [];
    if (!empty($searchQuery)) {
        $sql .= " AND (e.name LIKE :search OR e.description LIKE :search OR c.name LIKE :search OR e.location LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    // Ordering (e.g., show upcoming first, then past)
    $sql .= " ORDER BY e.event_date DESC"; // Or ASC for chronological

    // Add Pagination later if needed (LIMIT / OFFSET)

    $stmtEvents = $pdo->prepare($sql);
    $stmtEvents->execute($params);
    $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user's interactions for the fetched events (if logged in)
    if ($currentUserId && !empty($events)) {
        $eventIds = array_column($events, 'id');
        $placeholders = implode(',', array_fill(0, count($eventIds), '?')); // ?,?,?

        // Likes
        $stmtLikes = $pdo->prepare("SELECT event_id FROM event_likes WHERE user_id = ? AND event_id IN ($placeholders)");
        $stmtLikes->execute(array_merge([$currentUserId], $eventIds));
        while ($row = $stmtLikes->fetch(PDO::FETCH_ASSOC)) { $userInteractions['likes'][$row['event_id']] = true; }

        // Interested
        $stmtInterest = $pdo->prepare("SELECT event_id FROM event_interest WHERE user_id = ? AND event_id IN ($placeholders)");
        $stmtInterest->execute(array_merge([$currentUserId], $eventIds));
        while ($row = $stmtInterest->fetch(PDO::FETCH_ASSOC)) { $userInteractions['interested'][$row['event_id']] = true; }

        // Attending
        $stmtAttendees = $pdo->prepare("SELECT event_id FROM event_attendees WHERE user_id = ? AND event_id IN ($placeholders)");
        $stmtAttendees->execute(array_merge([$currentUserId], $eventIds));
        while ($row = $stmtAttendees->fetch(PDO::FETCH_ASSOC)) { $userInteractions['attending'][$row['event_id']] = true; }
    }

} catch (Exception $e) {
    error_log("Events Page Error: " . $e->getMessage());
    $pageError = "Could not load events. Please try again later.";
    $events = []; // Ensure events is empty on error
}


// --- Handle Interaction Actions (POST Request - Requires separate handler for AJAX) ---
// This is a simplified version using POST and page reload.
// A real implementation would likely use handle_event_action.php and JS/fetch.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $currentUserId) {
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    $redirectUrl = "events.php" . (!empty($searchQuery) ? '?search='.urlencode($searchQuery) : ''); // Redirect back with search term

    if ($eventId) {
        try {
            // Wrap actions in transaction potentially
            if ($action === 'like' || $action === 'unlike') {
                $isLiked = isset($userInteractions['likes'][$eventId]);
                if ($action === 'like' && !$isLiked) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO event_likes (user_id, event_id) VALUES (?, ?)");
                    $stmt->execute([$currentUserId, $eventId]);
                    $_SESSION['interaction_message'] = ['type'=>'success', 'text'=>'Event liked!'];
                } elseif ($action === 'unlike' && $isLiked) {
                    $stmt = $pdo->prepare("DELETE FROM event_likes WHERE user_id = ? AND event_id = ?");
                    $stmt->execute([$currentUserId, $eventId]);
                     $_SESSION['interaction_message'] = ['type'=>'info', 'text'=>'Event unliked.'];
                }
            } elseif ($action === 'interested' || $action === 'uninterested') {
                 $isInterested = isset($userInteractions['interested'][$eventId]);
                if ($action === 'interested' && !$isInterested) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO event_interest (user_id, event_id) VALUES (?, ?)"); $stmt->execute([$currentUserId, $eventId]);
                    $_SESSION['interaction_message'] = ['type'=>'success', 'text'=>'Marked as interested!'];
                } elseif ($action === 'uninterested' && $isInterested) {
                    $stmt = $pdo->prepare("DELETE FROM event_interest WHERE user_id = ? AND event_id = ?"); $stmt->execute([$currentUserId, $eventId]);
                    $_SESSION['interaction_message'] = ['type'=>'info', 'text'=>'Removed interest mark.'];
                }
            } elseif ($action === 'attend' || $action === 'unattend') {
                 $isAttending = isset($userInteractions['attending'][$eventId]);
                 if ($action === 'attend' && !$isAttending) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO event_attendees (user_id, event_id) VALUES (?, ?)"); $stmt->execute([$currentUserId, $eventId]);
                     $_SESSION['interaction_message'] = ['type'=>'success', 'text'=>'You are now registered as attending!'];
                 } elseif ($action === 'unattend' && $isAttending) {
                    $stmt = $pdo->prepare("DELETE FROM event_attendees WHERE user_id = ? AND event_id = ?"); $stmt->execute([$currentUserId, $eventId]);
                    $_SESSION['interaction_message'] = ['type'=>'info', 'text'=>'Registration cancelled.'];
                 }
            }
            // Redirect after action
            header("Location: " . $redirectUrl);
            exit;
        } catch (Exception $e) {
             error_log("Event action error: ".$e->getMessage());
             $_SESSION['interaction_message'] = ['type'=>'error', 'text'=>'Could not perform action.'];
             header("Location: " . $redirectUrl); // Still redirect
             exit;
        }
    }
}

// --- Handle Interaction Actions (POST Request - Same as before) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $currentUserId) {
    /* ... Same POST handling logic for like/unlike, interested/uninterested, attend/unattend ... */
    /* Make sure redirect preserves search query: */
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT); $action = $_POST['action']; $redirectUrl = "events.php" . (!empty($searchQuery) ? '?search='.urlencode($searchQuery) : '');
    if($eventId){ try { /* ... DB logic based on $action ... */ if($action==='like' || $action==='unlike'){ /* ... */} elseif($action==='interested' || $action==='uninterested'){ /* ... */} elseif($action==='attend' || $action==='unattend'){ /* ... */} header("Location: ".$redirectUrl); exit; } catch(Exception $e){ error_log("Event action error: ".$e->getMessage()); $_SESSION['interaction_message']=['type'=>'error','text'=>'Action failed.']; header("Location: ".$redirectUrl); exit; } }
}

// --- NOW START HTML OUTPUT ---
include_once 'test2.php'; // Provides navbar/layout
?>

<!-- Main Content Area -->
<main class="main-content events-page-container">
    <div class="events-content-wrapper">

        <!-- Page Header & Search -->
        <div class="events-header">
            <h1>Upcoming & Recent Events</h1>
             <form action="events.php" method="GET" class="event-search-form"> <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="event-search-input"> <button type="submit" class="event-search-button" aria-label="Search"><i class="fas fa-search"></i></button> <?php if (!empty($searchQuery)): ?><a href="events.php" class="event-clear-search" title="Clear Search" aria-label="Clear Search">×</a><?php endif; ?> </form>
        </div>

        <!-- Interaction Message Display -->
        <?php if ($interactionMsg): ?><div class="message <?php echo $interactionMsg['type'] === 'success' ? 'success-message' : ($interactionMsg['type'] === 'info' ? 'info-message' : 'error-message'); ?>" role="alert"><?php echo htmlspecialchars($interactionMsg['text']); ?></div><?php endif; ?>
        <!-- General Page Load Error -->
        <?php if ($pageError): ?><div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?></div><?php endif; ?>

        <!-- Event Feed -->
        <div class="event-feed">
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                    <?php
                        $liked = $currentUserId ? ($userInteractions['likes'][$event['id']] ?? false) : false;
                        $interested = $currentUserId ? ($userInteractions['interested'][$event['id']] ?? false) : false;
                        $attending = $currentUserId ? ($userInteractions['attending'][$event['id']] ?? false) : false;
                        $formattedDate = format_event_datetime($event['event_date'], $event['event_end_date']);
                        $eventDetailUrl = "event-detail.php?id=" . $event['id']; // URL for event detail
                        $loginUrl = "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']); // Redirect back after login
                    ?>
                    <article class="event-card card">
                        <!-- Event Header (same) -->
                        <div class="event-card-header">
                            <div class="event-club-info">
                                <a href="club-detail.php?id=<?php echo $event['club_id']; ?>" class="club-avatar-link">
                                    <div class="avatar-placeholder small-avatar">
                                        <?php echo strtoupper(substr($event['club_name'] ?? '?', 0, 1)); ?>
                                    </div>
                                </a>
                                <div>
                                    <a href="club-detail.php?id=<?php echo $event['club_id']; ?>" class="club-name-link">
                                        <?php echo htmlspecialchars($event['club_name'] ?? 'Unknown Club'); ?>
                                    </a>
                                    <span class="event-post-time">
                                        Posted <?php echo format_time_ago($event['created_at']); ?>
                                        <?php // echo $event['creator_username'] ? ' by ' . htmlspecialchars($event['creator_username']) : ''; ?>
                                    </span>
                                </div>
                            </div>
                            <!-- Optional: Add dropdown menu for edit/delete if user is leader/admin -->
                             
                        </div>

                        <!-- Event Content -->
                        <div class="event-card-body">
                            <h2 class="event-title"><a href="<?php echo $eventDetailUrl; ?>" class="event-title-link"><?php echo htmlspecialchars($event['name']); ?></a></h2>
                            <!-- ** Truncated Description ** -->
                            <p class="event-description truncated">
                                <?php
                                    $fullDesc = $event['description'] ?? '';
                                    $maxLength = 150; // Adjust character limit
                                    echo nl2br(htmlspecialchars(substr($fullDesc, 0, $maxLength)));
                                    if (strlen($fullDesc) > $maxLength) {
                                        echo '... <a href="' . $eventDetailUrl . '" class="read-more-link">Read More</a>';
                                    }
                                ?>
                            </p>
                            <?php if (!empty($event['poster_image_path']) && file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $event['poster_image_path'])): ?>
                                <!-- ** Aspect Ratio Container for Image ** -->
                                <a href="<?php echo $eventDetailUrl; ?>" class="event-poster aspect-ratio-1-1">
                                    <img src="<?php echo htmlspecialchars($event['poster_image_path']); ?>" alt="Poster for <?php echo htmlspecialchars($event['name']); ?>" loading="lazy">
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Event Metadata (same) -->
                        <div class="event-metadata">
                             <span class="metadata-item" title="<?php echo $formattedDate['date']; ?>">
                                <i class="fas fa-calendar-alt fa-fw"></i> <?php echo $formattedDate['date']; ?>
                            </span>
                             <span class="metadata-item" title="Time">
                                <i class="fas fa-clock fa-fw"></i> <?php echo $formattedDate['time']; ?>
                             </span>
                             <?php if(!empty($event['location'])): ?>
                            <span class="metadata-item" title="Location">
                                <i class="fas fa-map-marker-alt fa-fw"></i> <?php echo htmlspecialchars($event['location']); ?>
                            </span>
                            <?php endif; ?>
                             <span class="metadata-item" title="Participants">
                                <i class="fas fa-users fa-fw"></i> <?php echo $event['participant_count'] ?? 0; ?> going
                             </span>
                        </div>


                        <!-- ** UPDATED Event Stats (Includes Interested) ** -->
                         <div class="event-stats">
                             <span><i class="fas fa-thumbs-up fa-xs"></i> <?php echo $event['like_count'] ?? 0; ?></span>
                             <span><i class="fas fa-star fa-xs"></i> <?php echo $event['interest_count'] ?? 0; ?> Interested</span>
                             <span><i class="fas fa-comment fa-xs"></i> <?php echo $event['comment_count'] ?? 0; ?></span>
                         </div>

                        <!-- ** UPDATED Event Actions ** -->
                        <div class="event-actions equal-width-actions">
                             <!-- Like Button -->
                            <?php if ($currentUserId): ?>
                            <form method="POST" action="events.php<?php echo !empty($searchQuery) ? '?search='.urlencode($searchQuery) : ''; ?>" class="action-form"> <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"> <input type="hidden" name="action" value="<?php echo $liked ? 'unlike' : 'like'; ?>"> <button type="submit" class="action-button <?php echo $liked ? 'active' : ''; ?>"> <i class="fas fa-thumbs-up fa-fw"></i> Like<?php // echo $liked ? 'd' : ''; ?> </button> </form>
                            <?php else: ?>
                             <a href="<?php echo $loginUrl; ?>" class="action-button"><i class="fas fa-thumbs-up fa-fw"></i> Like</a>
                            <?php endif; ?>

                             <!-- Comment Button -->
                            <a href="<?php echo $eventDetailUrl; ?>#comments" class="action-button"> <i class="fas fa-comment fa-fw"></i> Comment </a>

                             <!-- Interested Button -->
                            <?php if ($currentUserId): ?>
                             <form method="POST" action="events.php<?php echo !empty($searchQuery) ? '?search='.urlencode($searchQuery) : ''; ?>" class="action-form"> <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"> <input type="hidden" name="action" value="<?php echo $interested ? 'uninterested' : 'interested'; ?>"> <button type="submit" class="action-button <?php echo $interested ? 'active' : ''; ?>"> <i class="fas fa-star fa-fw"></i> Interested<?php // echo $interested ? '?' : ''; ?> </button> </form>
                            <?php else: ?>
                             <a href="<?php echo $loginUrl; ?>" class="action-button"><i class="fas fa-star fa-fw"></i> Interested</a>
                             <?php endif; ?>

                            <!-- Participate Button -->
                            <?php if ($currentUserId): ?>
                            <form method="POST" action="events.php<?php echo !empty($searchQuery) ? '?search='.urlencode($searchQuery) : ''; ?>" class="action-form"> <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"> <input type="hidden" name="action" value="<?php echo $attending ? 'unattend' : 'attend'; ?>"> <button type="submit" class="action-button <?php echo $attending ? 'active primary' : ''; ?>"> <i class="fas fa-check-circle fa-fw"></i> <?php echo $attending ? 'Attending' : 'Attend'; ?> </button> </form>
                            <?php else: ?>
                             <a href="<?php echo $loginUrl; ?>" class="action-button"><i class="fas fa-check-circle fa-fw"></i> Attend</a>
                            <?php endif; ?>

                            <!-- Share Button (Placeholder) -->
                            <button type="button" class="action-button" onclick="/* Add share logic or modal here */ alert('Share functionality coming soon!');" <?php echo !$currentUserId ? 'disabled title="Log in to share"' : ''; ?>> <i class="fas fa-share fa-fw"></i> Share </button>
                        </div>

                    </article> <!-- End event-card -->
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-events-message card"> <!-- ... No events message ... --> </div>
            <?php endif; ?>
        </div> <!-- End event-feed -->

    </div> <!-- End events-content-wrapper -->
    <?php include_once 'footer.php'; ?>
</main>




</body>
</html>