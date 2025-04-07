<?php
session_start(); // MUST be first or very early

// --- PHP Prerequisites & Admin Check ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php'; // Include function files if separate (NO HTML OUTPUT)

// **** CRITICAL: Authorization Check ****
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error_message'] = "Access Denied: Admin privileges required.";
    $role = $_SESSION['user']['role'] ?? null;
    $redirect_page = ($role === 'club_leader') ? 'dashboard.php' : 'home.php';
    header('Location: ' . $redirect_page);
    exit;
}
$adminUserId = $_SESSION['user']['id'];

// --- Helper Function (Include or define) ---
function format_time_ago($datetime, $full = false) { try { $now = new DateTime; $timestamp = strtotime($datetime); if ($timestamp === false) throw new Exception("Invalid datetime"); $ago = new DateTime('@'.$timestamp); $diff = $now->diff($ago); $string = ['y'=>'year','m'=>'month','d'=>'day','h'=>'hour','i'=>'minute','s'=>'second']; foreach ($string as $k=>&$v){if(property_exists($diff, $k)){if($diff->$k)$v=$diff->$k.' '.$v.($diff->$k>1?'s':'');else unset($string[$k]);}else unset($string[$k]);} if(!$full)$string=array_slice($string,0,1); return $string?implode(', ',$string).' ago':'just now'; } catch(Exception $e){ error_log("Time format err: ".$e->getMessage()); $ts=strtotime($datetime); return $ts?date('M j, Y g:i a',$ts):'Invalid date';} }

// --- Session Flash Messages ---
$adminActionMsg = $_SESSION['admin_action_message'] ?? null;
unset($_SESSION['admin_action_message']); // Clear after reading

// --- Handle POST Actions (Approve/Reject/Ban/Delete etc.) ---
$actionError = null;
$actionSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $targetId = filter_input(INPUT_POST, 'target_id', FILTER_VALIDATE_INT);
    $redirect = true; // Assume redirect unless error prevents it

    if (!$targetId && !in_array($action, ['some_action_without_id'])) { // Allow actions that might not need an ID
        $actionError = "Invalid target ID specified for action.";
    } else {
        try {
            if (!isset($pdo)) throw new Exception("DB connection lost");
            $pdo->beginTransaction();

            switch ($action) {
                case 'approve_club':
                    $stmtUpdate = $pdo->prepare("UPDATE clubs SET status = 'active' WHERE id = :id AND status = 'pending'"); $stmtUpdate->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtUpdate->execute();
                    if ($stmtUpdate->rowCount() > 0) {
                        $stmtProposer = $pdo->prepare("SELECT proposed_by_user_id FROM clubs WHERE id = :id"); $stmtProposer->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtProposer->execute(); $proposer = $stmtProposer->fetch(PDO::FETCH_ASSOC);
                        if ($proposer && $proposer['proposed_by_user_id']) {
                            $stmtLeader = $pdo->prepare("INSERT IGNORE INTO club_members (user_id, club_id, role) VALUES (:user_id, :club_id, 'leader')"); $stmtLeader->bindParam(':user_id', $proposer['proposed_by_user_id'], PDO::PARAM_INT); $stmtLeader->bindParam(':club_id', $targetId, PDO::PARAM_INT); $stmtLeader->execute();
                        } $actionSuccess = "Club approved."; /* TODO: Notify proposer */
                    } else $actionError = "Club approval failed (not found or already processed).";
                    break;

                case 'reject_club':
                    $stmtUpdate = $pdo->prepare("UPDATE clubs SET status = 'rejected' WHERE id = :id AND status = 'pending'"); $stmtUpdate->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtUpdate->execute();
                    if($stmtUpdate->rowCount() > 0) $actionSuccess = "Club proposal rejected."; else $actionError = "Club rejection failed."; /* TODO: Notify proposer */
                    break;

                case 'approve_event':
                    $stmtUpdate = $pdo->prepare("UPDATE events SET status = 'active' WHERE id = :id AND status = 'pending'"); $stmtUpdate->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtUpdate->execute();
                    if($stmtUpdate->rowCount() > 0) $actionSuccess = "Event approved."; else $actionError = "Event approval failed."; /* TODO: Notify proposer */
                    break;

                case 'reject_event':
                    $stmtUpdate = $pdo->prepare("UPDATE events SET status = 'rejected' WHERE id = :id AND status = 'pending'"); $stmtUpdate->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtUpdate->execute();
                    if($stmtUpdate->rowCount() > 0) $actionSuccess = "Event proposal rejected."; else $actionError = "Event rejection failed."; /* TODO: Notify proposer */
                    break;

                case 'ban_user':
                    $reason = trim($_POST['reason'] ?? 'Violation of guidelines.');
                    $stmtBan = $pdo->prepare("UPDATE users SET is_banned = TRUE, ban_reason = :reason, banned_until = NULL WHERE id = :id AND id != :admin_id AND role != 'admin'"); $stmtBan->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtBan->bindParam(':admin_id', $adminUserId, PDO::PARAM_INT); $stmtBan->bindParam(':reason', $reason, PDO::PARAM_STR); $stmtBan->execute();
                    if($stmtBan->rowCount() > 0) $actionSuccess = "User banned."; else $actionError = "Cannot ban user (not found, admin, or self).";
                    break;

                case 'unban_user':
                    $stmtUnban = $pdo->prepare("UPDATE users SET is_banned = FALSE, ban_reason = NULL, banned_until = NULL WHERE id = :id"); $stmtUnban->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtUnban->execute();
                    if($stmtUnban->rowCount() > 0) $actionSuccess = "User unbanned."; else $actionError = "User unban failed.";
                    break;

                case 'delete_user':
                    // ** CAUTION - ENSURE CASCADE OR MANUAL CLEANUP IS SET UP **
                    $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id = :id AND id != :admin_id AND role != 'admin'"); $stmtDelete->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtDelete->bindParam(':admin_id', $adminUserId, PDO::PARAM_INT); $stmtDelete->execute();
                    if($stmtDelete->rowCount() > 0) $actionSuccess = "User deleted."; else $actionError = "Cannot delete user (not found, admin, or self).";
                    break;

                 case 'delete_club':
                     // ** CAUTION - Check related data (members, events, etc.) **
                     // Simple delete for now, assumes cascade or unimportant related data
                     $stmtDelete = $pdo->prepare("DELETE FROM clubs WHERE id = :id"); $stmtDelete->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtDelete->execute();
                     if($stmtDelete->rowCount() > 0) $actionSuccess = "Club deleted."; else $actionError = "Club deletion failed (not found?).";
                     break;

                 case 'delete_event':
                      // ** CAUTION - Check related data (attendees, likes, etc.) **
                     $stmtDelete = $pdo->prepare("DELETE FROM events WHERE id = :id"); $stmtDelete->bindParam(':id', $targetId, PDO::PARAM_INT); $stmtDelete->execute();
                     if($stmtDelete->rowCount() > 0) $actionSuccess = "Event deleted."; else $actionError = "Event deletion failed (not found?).";
                     break;

                default:
                    $actionError = "Unknown action specified.";
            }

            if (!$actionError) $pdo->commit(); else $pdo->rollBack();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $actionError = "An error occurred: " . $e->getMessage();
            error_log("Admin Action '{$action}' Error: " . $e->getMessage());
            $redirect = false; // Don't redirect if exception occurs during processing
        }
    }

    // Store result in session and redirect if $redirect is true
    if ($redirect) {
        if ($actionSuccess) $_SESSION['admin_action_message'] = ['type' => 'success', 'text' => $actionSuccess];
        if ($actionError) $_SESSION['admin_action_message'] = ['type' => 'error', 'text' => $actionError];
        header("Location: admin.php");
        exit;
    } // If redirect is false, $actionError will be displayed on the current page load

} // --- End POST Handling ---


// --- Initialize Variables for Display Data ---
$pendingClubs = []; $pendingEvents = []; $allUsers = []; $allClubs = []; $allEvents = [];
$pageError = null; // For page load errors

// --- Fetch Data for Display ---
try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }
    // Fetch data using prepared statements as before...
    $stmtPClubs = $pdo->prepare("SELECT c.*, u.username as proposer_username FROM clubs c LEFT JOIN users u ON c.proposed_by_user_id = u.id WHERE c.status = 'pending' ORDER BY c.created_at DESC"); $stmtPClubs->execute(); $pendingClubs = $stmtPClubs->fetchAll(PDO::FETCH_ASSOC);
    $stmtPEvents = $pdo->prepare("SELECT e.*, c.name as club_name, u.username as proposer_username FROM events e JOIN clubs c ON e.club_id = c.id LEFT JOIN users u ON e.created_by = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC"); $stmtPEvents->execute(); $pendingEvents = $stmtPEvents->fetchAll(PDO::FETCH_ASSOC);
    $stmtUsers = $pdo->prepare("SELECT id, username, email, role, is_banned, banned_until, created_at FROM users WHERE id != :admin_id ORDER BY created_at DESC LIMIT 50"); $stmtUsers->bindParam(':admin_id', $adminUserId, PDO::PARAM_INT); $stmtUsers->execute(); $allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    $stmtAClubs = $pdo->prepare("SELECT c.id, c.name, c.category, c.status, u.username as proposer_username, (SELECT COUNT(*) FROM club_members WHERE club_id = c.id) as member_count FROM clubs c LEFT JOIN users u ON c.proposed_by_user_id = u.id ORDER BY c.created_at DESC LIMIT 50"); $stmtAClubs->execute(); $allClubs = $stmtAClubs->fetchAll(PDO::FETCH_ASSOC);
    $stmtAEvents = $pdo->prepare("SELECT e.id, e.name, e.event_date, c.name as club_name, e.status, u.username as creator_username, (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as attendee_count FROM events e JOIN clubs c ON e.club_id = c.id LEFT JOIN users u ON e.created_by = u.id ORDER BY e.event_date DESC LIMIT 50"); $stmtAEvents->execute(); $allEvents = $stmtAEvents->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log("Admin Panel Data Fetch Error: ".$e->getMessage()); $pageError = "Could not load administrative data."; }


// --- NOW START HTML OUTPUT ---
include_once 'test2.php';
?>

<!-- Main Content Area -->
<main class="main-content admin-panel-container">
    <div class="admin-content-wrapper">

        <!-- Page Header -->
        <div class="admin-header"><h1>Admin Panel</h1><p>Manage requests, users, clubs, and events.</p></div>

        <!-- Action Feedback Messages -->
        <?php if ($adminActionMsg): ?><div class="message <?php echo $adminActionMsg['type'] === 'success' ? 'success-message' : 'error-message'; ?>" role="alert"><?php echo htmlspecialchars($adminActionMsg['text']); ?></div><?php endif; ?>
        <!-- General Page Load / POST Error -->
        <?php if ($pageError || $actionError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError ?: $actionError); ?></div> <?php endif; ?>

        <!-- Admin Sections using Tabs -->
        <div class="admin-tabs-section">
             <nav class="admin-tabs" aria-label="Admin Sections">
                <button type="button" class="admin-tab-button active" data-tab-target="#admin-pending" role="tab">Pending Requests</button>
                <button type="button" class="admin-tab-button" data-tab-target="#admin-users" role="tab">Users</button>
                <button type="button" class="admin-tab-button" data-tab-target="#admin-clubs" role="tab">Clubs</button>
                <button type="button" class="admin-tab-button" data-tab-target="#admin-events" role="tab">Events</button>
            </nav>
            <div class="admin-tab-content">
                <!-- Pending Requests Panel -->
                <div id="admin-pending" class="admin-tab-panel active" role="tabpanel">
                    <section class="admin-section card"><h2>Pending Clubs (<?php echo count($pendingClubs); ?>)</h2> <?php if(count($pendingClubs)>0): ?><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Club</th><th>Category</th><th>By</th><th>Date</th><th>Actions</th></tr></thead><tbody> <?php foreach($pendingClubs as $club): ?> <tr> <td data-label="Club"><?php echo htmlspecialchars($club['name']);?></td> <td data-label="Category"><?php echo htmlspecialchars($club['category']);?></td> <td data-label="By"><?php echo htmlspecialchars($club['proposer_username']??'N/A');?></td> <td data-label="Date"><?php echo format_time_ago($club['created_at']);?></td> <td class="actions"><form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('Approve club?');"><input type="hidden" name="action" value="approve_club"><input type="hidden" name="target_id" value="<?php echo $club['id'];?>"><button type="submit" class="btn-action approve" title="Approve"><i class="fas fa-check"></i></button></form> <form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('Reject club?');"><input type="hidden" name="action" value="reject_club"><input type="hidden" name="target_id" value="<?php echo $club['id'];?>"><button type="submit" class="btn-action reject" title="Reject"><i class="fas fa-times"></i></button></form> <a href="club-detail.php?id=<?php echo $club['id'];?>&preview=true" target="_blank" class="btn-action view" title="Preview"><i class="fas fa-eye"></i></a></td> </tr> <?php endforeach; ?> </tbody></table></div> <?php else: ?><p class="empty-list-message">No pending clubs.</p><?php endif; ?></section>
                    <section class="admin-section card"><h2>Pending Events (<?php echo count($pendingEvents); ?>)</h2> <?php if(count($pendingEvents)>0): ?><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Event</th><th>Club</th><th>Date</th><th>By</th><th>Actions</th></tr></thead><tbody> <?php foreach($pendingEvents as $event): ?> <tr> <td data-label="Event"><?php echo htmlspecialchars($event['name']);?></td> <td data-label="Club"><?php echo htmlspecialchars($event['club_name']);?></td> <td data-label="Date"><?php echo date('M j, Y @ g:i A',strtotime($event['event_date']));?></td> <td data-label="By"><?php echo htmlspecialchars($event['proposer_username']??'N/A');?></td> <td class="actions"><form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('Approve event?');"><input type="hidden" name="action" value="approve_event"><input type="hidden" name="target_id" value="<?php echo $event['id'];?>"><button type="submit" class="btn-action approve" title="Approve"><i class="fas fa-check"></i></button></form> <form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('Reject event?');"><input type="hidden" name="action" value="reject_event"><input type="hidden" name="target_id" value="<?php echo $event['id'];?>"><button type="submit" class="btn-action reject" title="Reject"><i class="fas fa-times"></i></button></form> <a href="event-detail.php?id=<?php echo $event['id'];?>&preview=true" target="_blank" class="btn-action view" title="Preview"><i class="fas fa-eye"></i></a></td> </tr> <?php endforeach; ?> </tbody></table></div> <?php else: ?><p class="empty-list-message">No pending events.</p><?php endif; ?></section>
                </div>
                <!-- User Management Panel -->
                <div id="admin-users" class="admin-tab-panel" role="tabpanel">
                    <section class="admin-section card"><h2>Users (<?php echo count($allUsers); ?>)</h2><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody> <?php foreach($allUsers as $user): ?><tr class="<?php echo $user['is_banned']?'banned-row':'';?>"> <td data-label="Username"><?php echo htmlspecialchars($user['username']);?></td> <td data-label="Email"><?php echo htmlspecialchars($user['email']);?></td> <td data-label="Role"><?php echo ucfirst(str_replace('_',' ',$user['role']));?></td> <td data-label="Status"><?php echo $user['is_banned']?'<span class="status-badge banned">Banned</span>':'<span class="status-badge active">Active</span>';?></td> <td data-label="Joined"><?php echo format_time_ago($user['created_at']);?></td> <td class="actions"> <?php if($user['is_banned']): ?><form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('Unban user?');"><input type="hidden" name="action" value="unban_user"><input type="hidden" name="target_id" value="<?php echo $user['id'];?>"><button type="submit" class="btn-action unban" title="Unban"><i class="fas fa-unlock"></i></button></form><?php else: ?><form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('BAN user?');"><input type="hidden" name="action" value="ban_user"><input type="hidden" name="target_id" value="<?php echo $user['id'];?>"><input type="text" name="reason" placeholder="Reason (Opt.)" class="reason-input"><button type="submit" class="btn-action ban" title="Ban"><i class="fas fa-gavel"></i></button></form><?php endif; ?> <a href="edit_user.php?id=<?php echo $user['id'];?>" class="btn-action edit" title="Edit"><i class="fas fa-edit"></i></a> <form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('DELETE user PERMANENTLY?');"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="target_id" value="<?php echo $user['id'];?>"><button type="submit" class="btn-action delete" title="Delete"><i class="fas fa-trash-alt"></i></button></form></td> </tr><?php endforeach; ?></tbody></table></div></section>
                </div>
                <!-- Club Management Panel -->
                <div id="admin-clubs" class="admin-tab-panel" role="tabpanel">
                    <section class="admin-section card"><h2>Clubs (<?php echo count($allClubs); ?>)</h2><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Name</th><th>Category</th><th>Members</th><th>Status</th><th>Actions</th></tr></thead><tbody> <?php foreach($allClubs as $club): ?><tr> <td data-label="Name"><?php echo htmlspecialchars($club['name']);?></td> <td data-label="Category"><?php echo htmlspecialchars($club['category']);?></td> <td data-label="Members"><?php echo $club['member_count']??0;?></td> <td data-label="Status"><span class="status-badge <?php echo $club['status'];?>"><?php echo ucfirst($club['status']);?></span></td> <td class="actions"><a href="edit_club.php?id=<?php echo $club['id'];?>" class="btn-action edit" title="Edit"><i class="fas fa-edit"></i></a> <form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('DELETE club permanently?');"><input type="hidden" name="action" value="delete_club"><input type="hidden" name="target_id" value="<?php echo $club['id'];?>"><button type="submit" class="btn-action delete" title="Delete"><i class="fas fa-trash-alt"></i></button></form></td> </tr><?php endforeach; ?></tbody></table></div></section>
                </div>
                 <!-- Event Management Panel -->
                <div id="admin-events" class="admin-tab-panel" role="tabpanel">
                    <section class="admin-section card"><h2>Events (<?php echo count($allEvents); ?>)</h2><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Name</th><th>Club</th><th>Date</th><th>Attendees</th><th>Status</th><th>Actions</th></tr></thead><tbody> <?php foreach($allEvents as $event): ?><tr> <td data-label="Name"><?php echo htmlspecialchars($event['name']);?></td> <td data-label="Club"><?php echo htmlspecialchars($event['club_name']);?></td> <td data-label="Date"><?php echo date('M j, Y @ g:i A', strtotime($event['event_date']));?></td> <td data-label="Attendees"><?php echo $event['attendee_count']??0;?></td> <td data-label="Status"><span class="status-badge <?php echo $event['status'];?>"><?php echo ucfirst($event['status']);?></span></td> <td class="actions"><a href="edit_event.php?id=<?php echo $event['id'];?>" class="btn-action edit" title="Edit"><i class="fas fa-edit"></i></a> <form method="POST" action="admin.php" class="action-form" onsubmit="return confirm('DELETE event permanently?');"><input type="hidden" name="action" value="delete_event"><input type="hidden" name="target_id" value="<?php echo $event['id'];?>"><button type="submit" class="btn-action delete" title="Delete"><i class="fas fa-trash-alt"></i></button></form></td> </tr><?php endforeach; ?></tbody></table></div></section>
                </div>
            </div> <!-- End Admin Tab Content -->
        </div> <!-- End Admin Tabs Section -->

    </div> <!-- End Wrapper -->
</main>
<!-- JavaScript for Admin Tabs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const adminTabContainer = document.querySelector('.admin-tabs');
    const adminTabButtons = document.querySelectorAll('.admin-tab-button');
    const adminTabPanels = document.querySelectorAll('.admin-tab-panel');

    if (adminTabContainer) {
        adminTabContainer.addEventListener('click', (event) => {
            const clickedButton = event.target.closest('.admin-tab-button');
            if (!clickedButton || clickedButton.classList.contains('active')) return;

            const targetPanelId = clickedButton.getAttribute('data-tab-target');
            const targetPanel = document.querySelector(targetPanelId);

            if (!targetPanel) return;

            adminTabButtons.forEach(button => button.classList.remove('active'));
            adminTabPanels.forEach(panel => panel.classList.remove('active'));

            clickedButton.classList.add('active');
            targetPanel.classList.add('active');

             // Optional: Update URL hash for bookmarking/linking
             // window.location.hash = targetPanelId;
        });

         // Optional: Activate tab based on URL hash on load
         /*
         if (window.location.hash) {
             const initialTarget = window.location.hash;
             const initialButton = document.querySelector(`.admin-tab-button[data-tab-target="${initialTarget}"]`);
             if (initialButton) {
                 initialButton.click(); // Simulate click to activate
             }
         }
         */
    }
});
</script>

<!-- Specific CSS for Admin Panel -->
<style>
    /* Reuse general styles like .card, .message etc. */

    .admin-panel-container .admin-content-wrapper { max-width: 1200px; margin: 1rem auto; padding: 0 1rem; }
    .admin-header { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); } body.dark .admin-header { border-bottom-color: #333366; }
    .admin-header h1 { font-size: 2rem; font-weight: 600; }
    .admin-header p { font-size: 1rem; color: #666; } body.dark .admin-header p { color: #bbb; }

    /* Admin Tabs */
    .admin-tabs-section { margin-bottom: 2rem; }
    .admin-tabs { border-bottom: 2px solid var(--border-color); display: flex; gap: 0.5rem; margin-bottom: -1px; /* Overlap border */ } body.dark .admin-tabs { border-bottom-color: #333366; }
    .admin-tab-button {
        padding: 0.75rem 1.25rem; border: none; border-bottom: 2px solid transparent;
        background: none; cursor: pointer; font-weight: 500; color: #555;
        transition: color 0.2s, border-color 0.2s; margin-bottom: -1px; /* Align with border */
    }
    body.dark .admin-tab-button { color: #bbb; }
    .admin-tab-button:hover { color: var(--primary-color); }
    .admin-tab-button.active { color: var(--primary-color); border-bottom-color: var(--primary-color); font-weight: 600; }

    .admin-tab-content { padding-top: 2rem; }
    .admin-tab-panel { display: none; animation: fadeIn 0.3s ease-in-out; }
    .admin-tab-panel.active { display: block; }
     @keyframes fadeIn { from{opacity:0;} to{opacity:1;} } /* Keep fade */

    .admin-section { margin-bottom: 2rem; }
    .admin-section h2 { /* Reuse card h2 style */ font-size: 1.3rem; margin-bottom: 1rem; padding-bottom: 0.5rem;}

    /* Admin Tables */
    .admin-table-wrapper { overflow-x: auto; } /* Allow horizontal scroll on small screens */
    .admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .admin-table th, .admin-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border-color); } body.dark .admin-table th, body.dark .admin-table td { border-bottom-color: #333366; }
    .admin-table th { font-weight: 600; background-color: rgba(0,0,0,0.02); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; color: #555; } body.dark .admin-table th { background-color: rgba(255,255,255,0.05); color: #bbb;}
    .admin-table td.actions { white-space: nowrap; text-align: right; }
    .admin-table .action-form { display: inline-block; margin: 0 0.2rem; padding: 0; }
    .btn-action { background: none; border: none; cursor: pointer; padding: 0.3rem 0.4rem; font-size: 1rem; line-height: 1; border-radius: 3px; transition: background-color 0.2s, color 0.2s; }
    .btn-action.approve { color: #155724; } .btn-action.approve:hover { background-color: #d4edda; } body.dark .btn-action.approve { color: #a3cfbb; } body.dark .btn-action.approve:hover { background-color: rgba(163, 207, 187, 0.2); }
    .btn-action.reject, .btn-action.delete { color: #721c24; } .btn-action.reject:hover, .btn-action.delete:hover { background-color: #f8d7da; } body.dark .btn-action.reject, body.dark .btn-action.delete { color: #f5c6cb; } body.dark .btn-action.reject:hover, body.dark .btn-action.delete:hover { background-color: rgba(245, 198, 203, 0.2); }
    .btn-action.ban { color: #856404; } .btn-action.ban:hover { background-color: #fff3cd; } body.dark .btn-action.ban { color: #ffeeba; } body.dark .btn-action.ban:hover { background-color: rgba(255, 238, 186, 0.2); }
    .btn-action.unban { color: #004085; } .btn-action.unban:hover { background-color: #cce5ff; } body.dark .btn-action.unban { color: #b8daff; } body.dark .btn-action.unban:hover { background-color: rgba(184, 218, 255, 0.2); }
    .btn-action.view, .btn-action.edit { color: #004085; } .btn-action.view:hover, .btn-action.edit:hover { background-color: #cce5ff; } body.dark .btn-action.view, body.dark .btn-action.edit { color: #b8daff; } body.dark .btn-action.view:hover, body.dark .btn-action.edit:hover { background-color: rgba(184, 218, 255, 0.2); }
    .actions .reason-input { padding: 2px 4px; font-size: 0.8rem; border: 1px solid #ccc; border-radius: 3px; margin-right: 3px; max-width: 100px;} body.dark .actions .reason-input { background: #444; border-color: #666; color: #eee;}
    .status-badge { display: inline-block; padding: 0.15rem 0.5rem; font-size: 0.75rem; border-radius: 999px; font-weight: 500; line-height: 1.2; text-transform: capitalize;}
    .status-badge.active { background-color: #d4edda; color: #155724; } body.dark .status-badge.active { background-color: #1c6c30; color: #d4edda;}
    .status-badge.pending { background-color: #fff3cd; color: #856404; } body.dark .status-badge.pending { background-color: #856404; color: #fff3cd;}
    .status-badge.rejected { background-color: #f8d7da; color: #721c24; } body.dark .status-badge.rejected { background-color: #721c24; color: #f8d7da;}
    .status-badge.banned { background-color: #f8d7da; color: #721c24; } body.dark .status-badge.banned { background-color: #721c24; color: #f8d7da;}
    tr.banned-row td { opacity: 0.6; font-style: italic; }

    .empty-list-message { /* ... Reuse style ... */ }

     /* Responsive Table */
     @media (max-width: 767px) {
         .admin-table thead { display: none; } /* Hide headers on mobile */
         .admin-table tbody, .admin-table tr, .admin-table td { display: block; width: 100%; }
         .admin-table tr { border: 1px solid var(--border-color); border-radius: 4px; margin-bottom: 1rem; } body.dark .admin-table tr { border-color: #333366; }
         .admin-table td { border: none; border-bottom: 1px solid #eee; text-align: right; position: relative; padding-left: 50%; white-space: normal;} body.dark .admin-table td { border-bottom-color: #444; }
         .admin-table td::before { /* Use data-label for mobile */
             content: attr(data-label);
             position: absolute; left: 0.75rem; width: 45%; padding-right: 10px;
             text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; color: #555;
         }
         body.dark .admin-table td::before { color: #bbb; }
         .admin-table td.actions { text-align: left; padding-left: 0.75rem; } /* Actions align left on mobile */
         .admin-table td.actions::before { display: none; } /* No label for actions cell */
          .actions .reason-input { max-width: none; width: calc(100% - 50px); /* Adjust width */ }

          /* Add data-label attributes to your td elements in PHP if using this mobile table approach */
          /* Example: <td data-label="Club Name"></td> */
          /* Note: Mobile table view needs data-label attributes added to TDs in PHP to work well */
     }

</style>
</body>
</html>