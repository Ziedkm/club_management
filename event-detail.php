<?php
session_start();

// --- PHP Prerequisites & Logic ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php'; // Include function files if separate

// --- Helper Functions (Include from previous answers) ---
function format_time_ago($datetime, $full = false) { /* ... */ try { $now = new DateTime; $timestamp = strtotime($datetime); if ($timestamp === false) throw new Exception("Invalid datetime"); $ago = new DateTime('@' . $timestamp); $diff = $now->diff($ago); $string = ['y' => 'year','m' => 'month','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second']; foreach ($string as $k => &$v) { if (property_exists($diff, $k)) { if ($diff->$k) $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); else unset($string[$k]); } else unset($string[$k]); } if (!$full) $string = array_slice($string, 0, 1); return $string ? implode(', ', $string) . ' ago' : 'just now'; } catch (Exception $e) { error_log("Error formatting time ('{$datetime}'): " . $e->getMessage()); $timestamp = strtotime($datetime); return $timestamp ? date('M j, Y g:i a', $timestamp) : 'Invalid date'; } }
function format_event_datetime($start, $end = null) { /* ... */ if(!$start) return ['date'=>'Date TBD', 'time'=>'']; $startDate=strtotime($start); $endDate=$end?strtotime($end):null; if(!$startDate) return ['date'=>'Invalid Date', 'time'=>'']; $dateStr=date('D, M j, Y',$startDate); $timeStr=date('g:i A',$startDate); if($endDate&&$endDate>$startDate){if(date('Ymd',$startDate)==date('Ymd',$endDate)){$timeStr.=' - '.date('g:i A',$endDate);}else{$timeStr.=' (ends '.date('M j, g:i A',$endDate).')';}} return ['date'=>$dateStr, 'time'=>$timeStr]; }

// --- Constants & Config ---
define('COMMENTS_PER_PAGE', 10); // For comment pagination later
date_default_timezone_set('UTC'); // Example: Use your actual timezone

// --- Input Validation & Authentication ---
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$currentUserId = $_SESSION['user']['id'] ?? null;
$userRole = $_SESSION['user']['role'] ?? null; // Get current user role
$isPreview = isset($_GET['preview']) && $_GET['preview'] === 'true'; // Check for preview flag
$loginUrl = "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']);

if (!$eventId) {
    header('Location: events.php');
    exit;
}

// --- Session Flash Messages ---
$interactionMsg = $_SESSION['interaction_message'] ?? null;
$commentMsg = $_SESSION['comment_message'] ?? null;
unset($_SESSION['interaction_message'], $_SESSION['comment_message']);

// --- Handle POST Actions (Like, Attend, Interest, Comment) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUserId) { // Actions require login
    $action = $_POST['action'] ?? null;
    $submittedEventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

    // Security: Ensure action is for the event currently being viewed
    if ($submittedEventId !== $eventId) {
        $_SESSION['interaction_message'] = ['type' => 'error', 'text' => 'Action mismatch.'];
    } else {
        try {
            if (!isset($pdo)) throw new Exception("DB connection lost");

            if ($action === 'like' || $action === 'unlike') { /* ... Like/Unlike DB Logic ... */
                $isLikedNow=false; $stmtCheck=$pdo->prepare("SELECT 1 FROM event_likes WHERE user_id=? AND event_id=?"); $stmtCheck->execute([$currentUserId,$eventId]); if($stmtCheck->fetch()) $isLikedNow=true;
                if($action==='like' && !$isLikedNow){ $stmt=$pdo->prepare("INSERT IGNORE INTO event_likes (user_id,event_id) VALUES (?,?)"); if($stmt->execute([$currentUserId,$eventId]))$_SESSION['interaction_message']=['type'=>'success','text'=>'Liked!']; else throw new Exception();}
                elseif($action==='unlike' && $isLikedNow){ $stmt=$pdo->prepare("DELETE FROM event_likes WHERE user_id=? AND event_id=?"); if($stmt->execute([$currentUserId,$eventId]))$_SESSION['interaction_message']=['type'=>'info','text'=>'Unliked.']; else throw new Exception();}
            } elseif ($action === 'interested' || $action === 'uninterested') { /* ... Interested/Uninterested DB Logic ... */
                 $isInterestedNow=false; $stmtCheck=$pdo->prepare("SELECT 1 FROM event_interest WHERE user_id=? AND event_id=?"); $stmtCheck->execute([$currentUserId,$eventId]); if($stmtCheck->fetch()) $isInterestedNow=true;
                 if($action==='interested'&&!$isInterestedNow){$stmt=$pdo->prepare("INSERT IGNORE INTO event_interest (user_id,event_id) VALUES (?,?)"); if($stmt->execute([$currentUserId,$eventId]))$_SESSION['interaction_message']=['type'=>'success','text'=>'Interested!']; else throw new Exception();}
                 elseif($action==='uninterested'&&$isInterestedNow){$stmt=$pdo->prepare("DELETE FROM event_interest WHERE user_id=? AND event_id=?"); if($stmt->execute([$currentUserId,$eventId]))$_SESSION['interaction_message']=['type'=>'info','text'=>'Not interested.']; else throw new Exception();}
            } elseif ($action === 'attend' || $action === 'unattend') { /* ... Attend/Unattend DB Logic ... */
                 $isAttendingNow=false; $stmtCheck=$pdo->prepare("SELECT 1 FROM event_attendees WHERE user_id=? AND event_id=?"); $stmtCheck->execute([$currentUserId,$eventId]); if($stmtCheck->fetch()) $isAttendingNow=true;
                 if($action==='attend'&&!$isAttendingNow){$stmt=$pdo->prepare("INSERT IGNORE INTO event_attendees (user_id,event_id) VALUES (?,?)"); if($stmt->execute([$currentUserId,$eventId]))$_SESSION['interaction_message']=['type'=>'success','text'=>'Attending!']; else throw new Exception();}
                 elseif($action==='unattend'&&$isAttendingNow){$stmt=$pdo->prepare("DELETE FROM event_attendees WHERE user_id=? AND event_id=?"); if($stmt->execute([$currentUserId,$eventId]))$_SESSION['interaction_message']=['type'=>'info','text'=>'Not attending.']; else throw new Exception();}
            } elseif ($action === 'add_comment') {
                 $commentText = trim($_POST['comment_text'] ?? '');
                 if (empty($commentText)) {
                     $_SESSION['comment_message'] = ['type' => 'error', 'text' => 'Comment cannot be empty.'];
                 } else {
                     $sql = "INSERT INTO event_comments (event_id, user_id, comment_text) VALUES (?, ?, ?)";
                     $stmt = $pdo->prepare($sql);
                     if ($stmt->execute([$eventId, $currentUserId, $commentText])) {
                         $_SESSION['comment_message'] = ['type' => 'success', 'text' => 'Comment posted.'];
                     } else {
                         $_SESSION['comment_message'] = ['type' => 'error', 'text' => 'Failed to post comment.'];
                         error_log("Comment insert failed: ".implode(", ", $stmt->errorInfo()));
                     }
                 }
            }
        } catch (Exception $e) {
             $msg = ($action === 'add_comment') ? 'comment' : 'interaction';
             $_SESSION[$msg.'_message'] = ['type'=>'error', 'text'=>"Error processing {$msg}."];
             error_log("Event detail action '{$action}' error: ".$e->getMessage());
        }
    }
    // Redirect after any POST action to prevent resubmission and show message
    header("Location: event-detail.php?id=" . $eventId);
    exit;
}


// --- Fetch Event Details, Comments, and User Interactions ---
$event = null;
$comments = [];
$userInteractions = ['liked' => false, 'interested' => false, 'attending' => false];
$pageError = null;

try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }

    // Fetch Event Details
    // ** MODIFIED SQL WHERE Clause **
    $sqlEvent = "SELECT e.*, c.name as club_name, u.username as creator_username, c.logo_path as logo_path,
                 (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as participant_count,
                 (SELECT COUNT(*) FROM event_likes WHERE event_id = e.id) as like_count,
                 (SELECT COUNT(*) FROM event_comments WHERE event_id = e.id) as comment_count,
                 (SELECT COUNT(*) FROM event_interest WHERE event_id = e.id) as interest_count
                 FROM events e
                 JOIN clubs c ON e.club_id = c.id
                 LEFT JOIN users u ON e.created_by = u.id
                 WHERE e.id = :event_id"; // Fetch by ID first

    // ** ADD Conditional Status Check **
    // Only enforce 'active' status if NOT in preview mode OR if user is not an admin
    if (!($isPreview && $userRole === 'admin')) { // Allow admins to preview anything
         if (!$isPreview) { // If not preview, must be active
              $sqlEvent .= " AND e.status = 'active'";
         }
         // If preview=true but user is NOT admin, they still only see active events (optional security)
         else { $sqlEvent .= " AND e.status = 'active'"; }
    }
    // else: If preview=true AND user IS admin, show event regardless of status (except maybe 'rejected'?)
    // Optional: Exclude rejected events even for admin preview?
    if ($isPreview && $userRole === 'admin') { $sqlEvent .= " AND e.status != 'rejected'"; }


    $stmtEvent = $pdo->prepare($sqlEvent);
    $stmtEvent->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmtEvent->execute();
    $event = $stmtEvent->fetch(PDO::FETCH_ASSOC);

    // ** MODIFIED CHECK: Check only if $event is false (truly not found) **
    if (!$event) {
        $pageError = "Event not found."; // Simplified error message
        // Don't proceed to fetch comments/interactions if event not found
    } else {
        // Fetch Comments (only if event found)
        $sqlComments = "SELECT ec.*, u.username as commenter_username,u.profile_picture_path FROM event_comments ec JOIN users u ON ec.user_id = u.id WHERE ec.event_id = :event_id ORDER BY ec.created_at DESC";
        $stmtComments = $pdo->prepare($sqlComments); $stmtComments->bindParam(':event_id', $eventId, PDO::PARAM_INT); $stmtComments->execute(); $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Current User's Interactions (only if event found and user logged in)
        if ($currentUserId) { /* ... fetch likes, interest, attending ... */
            $stmtInteractions = $pdo->prepare("SELECT * FROM event_likes WHERE user_id = :user_id AND event_id = :event_id");
            $stmtInteractions->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
            $stmtInteractions->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmtInteractions->execute();
            $userInteractions['liked'] = (bool)$stmtInteractions->fetchColumn();

            // Repeat for interest and attendance
            $stmtInterest = $pdo->prepare("SELECT * FROM event_interest WHERE user_id = :user_id AND event_id = :event_id");
            $stmtInterest->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
            $stmtInterest->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmtInterest->execute();
            $userInteractions['interested'] = (bool)$stmtInterest->fetchColumn();

            // Fetch attendance status
            $stmtAttendance = $pdo->prepare("SELECT * FROM event_attendees WHERE user_id = :user_id AND event_id = :event_id");
            $stmtAttendance->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
            $stmtAttendance->bindParam(':event_id', $eventId, PDO::PARAM_INT);
            $stmtAttendance->execute();
            $userInteractions['attending'] = (bool)$stmtAttendance->fetchColumn();
        }
        
        
    }

} catch (Exception $e) { /* ... error handling ... */
    $pageError = "Error loading event details."; // Simplified error message
    error_log("Event detail fetch error: " . $e->getMessage());
}

// --- NOW START HTML OUTPUT ---
include_once 'header.php'; // Already included at the top

?>

<!-- Main Content Area -->
<main class="main-content event-detail-page-container">
    <div class="event-detail-content-wrapper">
<!-- Display Page Load Error First -->
<?php if ($pageError): ?>
            <div class="message error-message card" role="alert"><?php echo htmlspecialchars($pageError); ?> <a href="events.php" class="text-link">Back to Events</a></div>
        <?php elseif ($event): // Only render if event was found (regardless of status if previewing) ?>

            <!-- Show status prominently if not active (and user is admin/previewing) -->
            <?php if ($event['status'] !== 'active' && $isPreview && $userRole === 'admin'): ?>
                <div class="message info-message" role="alert">
                    <strong>Preview Mode:</strong> This event status is currently '<?php echo htmlspecialchars($event['status']); ?>'.
                     <?php if ($event['status'] === 'pending'): ?>
                         <div style="margin-top: 10px;">
                             <form method="POST" action="admin.php" style="display: inline-block; margin-right: 5px;"><input type="hidden" name="action" value="approve_event"><input type="hidden" name="target_id" value="<?php echo $event['id']; ?>"><button type="submit" class="btn btn-success btn-sm">Approve Event</button></form>
                             <form method="POST" action="admin.php" style="display: inline-block;"><input type="hidden" name="action" value="reject_event"><input type="hidden" name="target_id" value="<?php echo $event['id']; ?>"><button type="submit" class="btn btn-danger btn-sm">Reject Event</button></form>
                         </div>
                     <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php
                // Pre-calculate statuses for cleaner HTML
                $liked = $userInteractions['liked'];
                $interested = $userInteractions['interested'];
                $attending = $userInteractions['attending'];
                $formattedDate = format_event_datetime($event['event_date'], $event['event_end_date']);
                $eventActionUrl = "event-detail.php?id=" . $eventId; // Post back to this page
            ?>

           <!-- Interaction/Comment Message Display -->
           <?php if ($interactionMsg): ?><div class="message <?php echo $interactionMsg['type'] === 'success' ? 'success-message' : ($interactionMsg['type'] === 'info' ? 'info-message' : 'error-message'); ?>" role="alert"><?php echo htmlspecialchars($interactionMsg['text']); ?></div><?php endif; ?>
             <?php if ($commentMsg): ?><div class="message <?php echo $commentMsg['type'] === 'success' ? 'success-message' : 'error-message'; ?>" role="alert"><?php echo htmlspecialchars($commentMsg['text']); ?></div><?php endif; ?>

            <article class="event-detail-card card">
                <!-- Event Header -->
                <div class="event-card-header">
                    <div class="event-club-info">
                        
                         <a href="club-detail.php?id=<?php echo $event['club_id']; ?>" class="club-avatar-link"> 
                         <?php
                                    // Check if logo path exists and the file is accessible
                                    $clubLogoWebPath = $event['logo_path'] ?? null;
                                    //console log
                                    echo "<script>console.log('Club Logo Web Path: " . htmlspecialchars($clubLogoWebPath) . "');</script>";
                                    // IMPORTANT: Adjust the document root check based on how $clubLogoWebPath is stored (relative vs absolute web path)
                                    // If $clubLogoWebPath is like '/cm/uploads/club_logos/xyz.jpg'
                                    $clubLogoServerPath = $clubLogoWebPath ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $clubLogoWebPath : null;
                                    // If $clubLogoWebPath is just 'xyz.jpg', you need to prepend the directory path:
                                    // $clubLogoServerPath = $clubLogoWebPath ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/cm/uploads/club_logos/' . $clubLogoWebPath : null;

                                    $showClubLogo = $clubLogoWebPath && file_exists($clubLogoServerPath);
                                    ?>
                                    <?php if ($showClubLogo): ?>
                                        <img src="<?php echo htmlspecialchars($clubLogoWebPath); ?>" alt="<?php echo htmlspecialchars($event['club_name'] ?? ''); ?> Logo" class="avatar-placeholder small-avatar">
                                    <?php else: ?>
                        <div class="avatar-placeholder small-avatar"><?php echo strtoupper(substr($event['club_name'] ?? '?', 0, 1)); ?></div>
                                    <?php endif; ?>
                    </a> <div> <a href="club-detail.php?id=<?php echo $event['club_id']; ?>" class="club-name-link"><?php echo htmlspecialchars($event['club_name'] ?? '?'); ?></a> <span class="event-post-time">Posted <?php echo format_time_ago($event['created_at']); ?><?php echo $event['creator_username'] ? ' by '.htmlspecialchars($event['creator_username']) : ''; ?></span> </div> </div>
                </div>

                <!-- Event Body -->
                <div class="event-card-body">
                    <h1 class="event-title"><?php echo htmlspecialchars($event['name']); ?></h1>

                    <!-- Poster Image -->
                    <?php if (!empty($event['poster_image_path']) && file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $event['poster_image_path'])): ?>
                        <div class="event-detail-poster aspect-ratio-1-1">
                            <img src="<?php echo htmlspecialchars($event['poster_image_path']); ?>" alt="Poster for <?php echo htmlspecialchars($event['name']); ?>" loading="lazy">
                        </div>
                    <?php endif; ?>

                     <!-- Metadata Bar -->
                    <div class="event-metadata detail-metadata">
                         <span class="metadata-item" title="<?php echo $formattedDate['date']; ?>"><i class="fas fa-calendar-alt fa-fw"></i> <?php echo $formattedDate['date']; ?></span>
                         <span class="metadata-item" title="Time"><i class="fas fa-clock fa-fw"></i> <?php echo $formattedDate['time']; ?></span>
                         <?php if(!empty($event['location'])): ?><span class="metadata-item" title="Location"><i class="fas fa-map-marker-alt fa-fw"></i> <?php echo htmlspecialchars($event['location']); ?></span><?php endif; ?>
                         <span class="metadata-item" title="Participants"><i class="fas fa-users fa-fw"></i> <?php echo $event['participant_count'] ?? 0; ?> going</span>
                    </div>

                    <!-- Full Description -->
                    <div class="event-full-description prose">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>
                </div>
                <?php if ($event['status'] === 'active' || ($isPreview && $userRole === 'admin')): ?>
                 <!-- Event Stats & Actions -->
                <div class="event-stats-actions">
                    <div class="event-stats">
                        <span><i class="fas fa-thumbs-up fa-xs"></i> <?php echo $event['like_count'] ?? 0; ?></span>
                        <span><i class="fas fa-star fa-xs"></i> <?php echo $event['interest_count'] ?? 0; ?> Interested</span>
                        <span><i class="fas fa-comment fa-xs"></i> <?php echo $event['comment_count'] ?? 0; ?></span>
                    </div>
                    <div class="event-actions equal-width-actions">
                         <!-- Like Button -->
                        <?php if($currentUserId):?><form method="POST" action="<?php echo $eventActionUrl; ?>" class="action-form"><input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"><input type="hidden" name="action" value="<?php echo $liked?'unlike':'like'; ?>"><button type="submit" class="action-button <?php echo $liked?'active':''; ?>"><i class="fas fa-thumbs-up fa-fw"></i> Like</button></form><?php else: ?><a href="<?php echo $loginUrl; ?>" class="action-button"><i class="fas fa-thumbs-up fa-fw"></i> Like</a><?php endif; ?>
                        <!-- Interested Button -->
                         <?php if($currentUserId):?><form method="POST" action="<?php echo $eventActionUrl; ?>" class="action-form"><input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"><input type="hidden" name="action" value="<?php echo $interested?'uninterested':'interested'; ?>"><button type="submit" class="action-button <?php echo $interested?'active':''; ?>"><i class="fas fa-star fa-fw"></i> Interested</button></form><?php else: ?><a href="<?php echo $loginUrl; ?>" class="action-button"><i class="fas fa-star fa-fw"></i> Interested</a><?php endif; ?>
                        <!-- Attend Button -->
                        <?php if($currentUserId):?><form method="POST" action="<?php echo $eventActionUrl; ?>" class="action-form"><input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"><input type="hidden" name="action" value="<?php echo $attending?'unattend':'attend'; ?>"><button type="submit" class="action-button <?php echo $attending?'active primary':''; ?>"><i class="fas fa-check-circle fa-fw"></i> <?php echo $attending?'Attending':'Attend';?></button></form><?php else: ?><a href="<?php echo $loginUrl; ?>" class="action-button"><i class="fas fa-check-circle fa-fw"></i> Attend</a><?php endif; ?>
                        <!-- Share Button -->
                        <button type="button" class="action-button" onclick="alert('Share feature coming soon!');" <?php echo !$currentUserId ? 'disabled title="Log in"' : ''; ?>><i class="fas fa-share fa-fw"></i> Share</button>
                    </div>
                </div>
                <?php endif; // End check for active event ?>
            </article>

            <!-- Comments Section -->
            <?php if ($event['status'] === 'active' || ($isPreview && $userRole === 'admin')): ?>
            <section id="comments" class="comments-section card">
                <h2>Comments (<?php echo $event['comment_count'] ?? 0; ?>)</h2>

                <?php if ($currentUserId):
                    
                    // Get path from session - use null coalescing for safety
                    $userProfilePic = $_SESSION['user']['profile_picture_path'] ?? null;
                    // Construct full server path to check if file exists
                    $profilePicServerPath = $userProfilePic ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $userProfilePic : null;
                    $showProfilePic = $userProfilePic && file_exists($profilePicServerPath);
                ?>
                    <!-- Comment Form -->
                    <form method="POST" action="<?php echo $eventActionUrl; ?>#comments" class="comment-form">
                         <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                         <input type="hidden" name="action" value="add_comment">
                        <div class="comment-input-group">
                            <div class="avatar-placeholder comment-avatar">
                                <?php if ($showProfilePic): ?>
                                    <img src="<?php echo htmlspecialchars($userProfilePic); ?>" alt="Your Profile Picture" class="h-8 w-8 rounded-full bg-blue-200 flex items-center justify-center">
                                <?php else: ?>
                                <?php echo strtoupper(substr($_SESSION['user']['username'] ?? '?', 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <textarea name="comment_text" class="comment-input" rows="2" placeholder="Add your comment..." required aria-label="Add your comment"></textarea>
                        </div>
                        <div class="comment-form-actions">
                            <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
                        </div>
                    </form>
                 <?php else: ?>
                    <p class="comment-login-prompt">Please <a href="<?php echo $loginUrl; ?>" class="text-link">log in</a> to post a comment.</p>
                 <?php endif; ?>


                <!-- Comments List -->
                <div class="comment-list">
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                            <?php
                                // Prepare paths for comment avatar
                                $commenterPicPath = $comment['profile_picture_path'] ?? null;
                                $commenterPicServerPath = $commenterPicPath ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $commenterPicPath : null;
                                $showCommenterPic = $commenterPicPath && $commenterPicServerPath && file_exists($commenterPicServerPath);
                            ?>
                            <div class="comment-item">
                                <div class="avatar-placeholder comment-avatar">
                                <?php if ($showCommenterPic): ?>
                                        <img src="<?php echo htmlspecialchars($commenterPicPath); ?>" alt="<?php echo htmlspecialchars($comment['commenter_username'] ?? 'User'); ?>'s picture" class="flex-shrink-0 h-9 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold text-lg">
                                    <?php else: ?>
                                     <?php echo strtoupper(substr(htmlspecialchars($comment['commenter_username'] ?? '?'), 0, 1)); ?>
                                    <?php endif; ?>
                                    </div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <span class="commenter-name"><?php echo htmlspecialchars($comment['commenter_username'] ?? 'User'); ?></span>
                                        <span class="comment-time"><?php echo format_time_ago($comment['created_at']); ?></span>
                                    </div>
                                    <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                    <!-- Optional: Add reply/like for comments later -->
                                </div>
                                 <!-- Optional: Delete button for comment owner / admin -->
                                 <?php  if ($currentUserId && ($comment['user_id'] == $currentUserId || $userRole === 'admin')): ?>
                                    <form method="POST" action="<?php echo $eventActionUrl; ?>#comments">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <button type="submit" class="btn-delete comment-delete" aria-label="Delete comment">×</button>
                                    </form>
                                 <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                         <!-- Add pagination links here later -->
                    <?php elseif($currentUserId): // Show message only if logged in and no comments ?>
                         <p class="no-comments">Be the first to comment on this event!</p>
                    <?php endif; ?>
                </div>

            </section>

        <?php endif; // End check for valid $event ?>
        <?php else: ?>
             <!-- This else block is now only reached if $pageError was set to "Event not found." -->
             <div class="message error-message card" role="alert"><?php echo htmlspecialchars($pageError ?? 'Event could not be loaded.'); ?> <a href="events.php" class="text-link">Back to Events</a></div>
        <?php endif; // End check for valid $event ?>
    </div> <!-- End wrapper -->
    <?php include_once 'footer.php'; ?>
</main>




</body>
</html>