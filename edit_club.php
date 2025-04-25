<?php
session_start();

// --- PHP Prerequisites & Admin Check ---
require_once 'config/database.php';
// include_once 'functions.php';

// **** CRITICAL: Authorization Check ****
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error_message'] = "Access Denied: Admin privileges required.";
    header('Location: home.php'); exit;
}
$adminUserId = $_SESSION['user']['id'];

// --- Input: Get Target Club ID ---
$targetClubId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$targetClubId) { /* Redirect if invalid ID */ $_SESSION['admin_action_message'] = ['type' => 'error', 'text' => 'Invalid club ID.']; header('Location: admin.php#admin-clubs'); exit; }

// --- Initialize Variables ---
$clubToEdit = null;
$currentLeader = null; // To store current leader info
$potentialNewLeaders = []; // Users who could become the new leader
$pageError = null;
$formError = null;

// --- Fetch Target Club Data & Current Leader ---
try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }
    // Fetch club details and proposer
    $stmtClub = $pdo->prepare("SELECT c.*, u.username as proposer_username FROM clubs c LEFT JOIN users u ON c.proposed_by_user_id = u.id WHERE c.id = :id");
    $stmtClub->bindParam(':id', $targetClubId, PDO::PARAM_INT);
    $stmtClub->execute();
    $clubToEdit = $stmtClub->fetch(PDO::FETCH_ASSOC);

    if (!$clubToEdit) { throw new Exception("Club with ID {$targetClubId} not found."); }

    // Fetch Current Leader/President (user_id and username)
    $stmtLeader = $pdo->prepare("SELECT cm.user_id, u.username
                                 FROM club_members cm
                                 JOIN users u ON cm.user_id = u.id
                                 WHERE cm.club_id = :club_id AND cm.role = 'leader'
                                 LIMIT 1"); // Assuming only one leader per club
    $stmtLeader->bindParam(':club_id', $targetClubId, PDO::PARAM_INT);
    $stmtLeader->execute();
    $currentLeader = $stmtLeader->fetch(PDO::FETCH_ASSOC); // Will be false if no leader found

    // Fetch Potential New Leaders (Non-admins, excluding the current leader if found)
    $sqlPotential = "SELECT id, username, email FROM users WHERE role != 'admin'";
    $excludeParams = [];
    if ($currentLeader) {
        $sqlPotential .= " AND id != :current_leader_id";
        $excludeParams[':current_leader_id'] = $currentLeader['user_id'];
    }
    $sqlPotential .= " ORDER BY username ASC";
    $stmtPotential = $pdo->prepare($sqlPotential);
    $stmtPotential->execute($excludeParams);
    $potentialNewLeaders = $stmtPotential->fetchAll(PDO::FETCH_ASSOC);


} catch (Exception $e) {
    error_log("Edit Club Fetch Error: " . $e->getMessage());
    $pageError = "Could not load club or leader data.";
    $clubToEdit = false; // Prevent form display
}

// --- Define available categories & statuses ---
$availableCategories = ['Academic', 'Arts & Culture', 'Community Service', 'Recreation', 'Sports', 'Technology', 'Social', 'Other'];
$availableStatuses = ['pending', 'active', 'rejected'];

// --- Session Flash Messages ---
$editMsg = $_SESSION['edit_club_message'] ?? null; unset($_SESSION['edit_club_message']);

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $clubToEdit) {

    // Retrieve inputs (same as before)
    $newClubName = trim($_POST['club_name'] ?? '');
    $newClubDesc = trim($_POST['club_description'] ?? '');
    $newClubCat = trim($_POST['club_category'] ?? '');
    $newClubSched = trim($_POST['club_schedule'] ?? '');
    $newClubStatus = trim($_POST['club_status'] ?? '');
    $newLeaderId = filter_input(INPUT_POST, 'assigned_leader_id', FILTER_VALIDATE_INT);
    $formError = null;

    // --- Validation (same as before) ---
    if (empty($newClubName) || empty($newClubDesc) || empty($newClubCat) || empty($newClubStatus)) { $formError = "Name, Desc, Cat, Status required."; }
    elseif (!in_array($newClubCat, $availableCategories)) { $formError = "Invalid category."; }
    elseif (!in_array($newClubStatus, $availableStatuses)) { $formError = "Invalid status."; }
    elseif (!$newLeaderId) { $formError = "President must be assigned."; }
    // Add length validations...

    // Validate selected leader ID exists and is not admin
    $newLeaderRole = null; // To store the role of the new leader
    if (!$formError) {
        $stmtValidLeader = $pdo->prepare("SELECT role FROM users WHERE id = :id AND role != 'admin'"); // Fetch role too
        $stmtValidLeader->bindParam(':id', $newLeaderId, PDO::PARAM_INT);
        $stmtValidLeader->execute();
        $newLeaderData = $stmtValidLeader->fetch(PDO::FETCH_ASSOC);
        if (!$newLeaderData) {
            $formError = "Selected user cannot be president (not found or is admin).";
        } else {
             $newLeaderRole = $newLeaderData['role']; // Store current role ('student' or 'club_leader')
        }
    }

    // Check name uniqueness (if changed)
    if (!$formError && $newClubName !== $clubToEdit['name']) { /* ... uniqueness check ... */ }

    // --- Perform Update if No Errors ---
    if (empty($formError)) {
        $currentLeaderId = $currentLeader ? $currentLeader['user_id'] : null;
        $leaderChanged = ($newLeaderId !== $currentLeaderId);

        try {
            $pdo->beginTransaction(); // Start transaction for all updates

            // 1. Update Club Details (Same as before)
            $sql = "UPDATE clubs SET name = :n, description = :d, category = :c, meeting_schedule = :s, status = :st WHERE id = :id";
            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->bindParam(':n', $newClubName); $stmtUpdate->bindParam(':d', $newClubDesc); $stmtUpdate->bindParam(':c', $newClubCat); $stmtUpdate->bindParam(':s', $newClubSched); $stmtUpdate->bindParam(':st', $newClubStatus); $stmtUpdate->bindParam(':id', $targetClubId, PDO::PARAM_INT);
            $updateSuccess = $stmtUpdate->execute();

            if ($updateSuccess) {
                $updateSuccessMessage = 'Club details updated. ';

                // 2. Handle Leader Change (if necessary)
                if ($leaderChanged) {
                    // a) Downgrade or remove OLD leader from THIS club
                    if ($currentLeaderId) {
                        $stmtRemoveOld = $pdo->prepare("UPDATE club_members SET role = 'member', department = NULL WHERE user_id = :uid AND club_id = :cid AND role = 'leader'");
                        $stmtRemoveOld->execute([':uid' => $currentLeaderId, ':cid' => $targetClubId]);
                    }

                    // b) Add/Update NEW leader in THIS club
                    $stmtCheckNewMember = $pdo->prepare("SELECT 1 FROM club_members WHERE user_id = :uid AND club_id = :cid");
                    $stmtCheckNewMember->execute([':uid' => $newLeaderId, ':cid' => $targetClubId]);
                    if ($stmtCheckNewMember->fetch()) { // Already a member, update role/dept
                         $stmtAssignNew = $pdo->prepare("UPDATE club_members SET role = 'leader', department = 'President' WHERE user_id = :uid AND club_id = :cid");
                    } else { // Not a member, insert
                         $stmtAssignNew = $pdo->prepare("INSERT INTO club_members (user_id, club_id, role, department) VALUES (:uid, :cid, 'leader', 'President')");
                    }
                    $stmtAssignNew->bindParam(':uid', $newLeaderId, PDO::PARAM_INT);
                    $stmtAssignNew->bindParam(':cid', $targetClubId, PDO::PARAM_INT);
                    if (!$stmtAssignNew->execute()) { throw new Exception("Failed to assign new president in club_members."); }

                    // **** NEW LOGIC: Update user roles in 'users' table ****

                    // c) Promote NEW leader in 'users' table (if they are currently a 'student')
                    if ($newLeaderRole === 'student') { // Check role fetched earlier
                        $stmtPromote = $pdo->prepare("UPDATE users SET role = 'club_leader' WHERE id = :id AND role = 'student'"); // Only update if student
                        $stmtPromote->bindParam(':id', $newLeaderId, PDO::PARAM_INT);
                        $stmtPromote->execute(); // Don't necessarily need to check rowCount, just attempt promotion
                    }

                    // d) Potentially Downgrade OLD leader in 'users' table
                    if ($currentLeaderId) {
                        // Check if the OLD leader leads ANY OTHER active clubs
                        $stmtCheckOtherClubs = $pdo->prepare("SELECT 1 FROM club_members cm JOIN clubs c ON cm.club_id = c.id WHERE cm.user_id = :uid AND cm.role = 'leader' AND c.status = 'active' AND cm.club_id != :excluded_cid LIMIT 1");
                        $stmtCheckOtherClubs->execute([':uid' => $currentLeaderId, ':excluded_cid' => $targetClubId]);

                        if (!$stmtCheckOtherClubs->fetch()) {
                            // Old leader does NOT lead any other active clubs, downgrade to student
                            $stmtDowngrade = $pdo->prepare("UPDATE users SET role = 'student' WHERE id = :id AND role = 'club_leader'"); // Only downgrade if currently leader
                            $stmtDowngrade->bindParam(':id', $currentLeaderId, PDO::PARAM_INT);
                            $stmtDowngrade->execute();
                        }
                    }
                    // **** END OF NEW ROLE LOGIC ****

                    $updateSuccessMessage .= 'President updated. ';
                } // End if leaderChanged

                // 3. Handle Post-Approval Actions (Make original proposer leader - Same as before)
                if ($newClubStatus === 'active' && $clubToEdit['status'] === 'pending' && $clubToEdit['proposed_by_user_id'] && $clubToEdit['proposed_by_user_id'] != $newLeaderId) {
                    $stmtProposerLeader = $pdo->prepare("INSERT IGNORE INTO club_members (user_id, club_id, role) VALUES (:user_id, :club_id, 'leader')");
                    $stmtProposerLeader->execute([':user_id' => $clubToEdit['proposed_by_user_id'], ':club_id' => $targetClubId]);
                    $updateSuccessMessage .= ' (Proposer also made leader).';
                }

                $pdo->commit(); // Commit all changes
                $_SESSION['admin_action_message'] = ['type' => 'success', 'text' => $updateSuccessMessage];
                header('Location: admin.php#admin-clubs'); exit;

            } else { /* Handle club update failure */ $pdo->rollBack(); $formError = 'Update failed.'; error_log("Club update failed ID {$targetClubId}: ".implode(", ", $stmtUpdate->errorInfo())); }
        } catch (Exception $e) { /* Handle exception */ if($pdo->inTransaction()) $pdo->rollBack(); $formError = 'Error: '.$e->getMessage(); error_log("Club update exception ID {$targetClubId}: ".$e->getMessage()); }
    } // End if empty($formError)

    // Redirect back on error
    if (!empty($formError)) { $_SESSION['edit_club_message'] = ['type' => 'error', 'text' => $formError]; header("Location: edit_club.php?id=" . $targetClubId); exit; }

} // End POST handling


// --- NOW START HTML OUTPUT ---
include_once 'header.php'; // Use header.php directly
?>

<!-- Main Content Area -->
<main class="main-content edit-club-container">
    <div class="edit-club-wrapper card">
        <div class="section-header">
             <h1 class="section-title">Edit Club Details</h1>
             <?php if ($clubToEdit): ?> <p class="section-subtitle">Modifying: <strong><?php echo htmlspecialchars($clubToEdit['name']); ?></strong></p> <?php endif; ?>
        </div>
        <!-- Messages Display -->
        <?php if ($pageError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?> <a href="admin.php#admin-clubs" class="text-link">Back</a></div><?php endif; ?>
        <?php if ($editMsg): ?> <div class="message <?php echo $editMsg['type'] === 'success' ? 'success-message' : ($editMsg['type'] === 'warning' ? 'info-message' : 'error-message'); ?>" role="alert"><?php echo htmlspecialchars($editMsg['text']); ?></div><?php endif; ?>

        <!-- Edit Club Form -->
        <?php if ($clubToEdit && !$pageError): ?>
            <form method="POST" action="edit_club.php?id=<?php echo $targetClubId; ?>" class="edit-form">
                <input type="hidden" name="target_id" value="<?php echo $targetClubId; ?>">

                <!-- Club Name --> <div class="form-group"><label for="club_name">Club Name <span class="required">*</span></label><input type="text" id="club_name" name="club_name" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($clubToEdit['name']); ?>" maxlength="100" required></div>
                <!-- Category --> <div class="form-group"><label for="club_category">Category <span class="required">*</span></label><select id="club_category" name="club_category" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required><option value="" disabled>-- Select --</option><?php foreach ($availableCategories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($clubToEdit['category'] === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?></select></div>
                <!-- Schedule --> <div class="form-group"><label for="club_schedule">Meeting Schedule</label><input type="text" id="club_schedule" name="club_schedule" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($clubToEdit['meeting_schedule'] ?? ''); ?>" maxlength="100" placeholder="e.g., Tuesdays 5 PM"></div>
                <!-- Description --> <div class="form-group"><label for="club_description">Description <span class="required">*</span></label><textarea id="club_description" name="club_description" class="form-textarea" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" rows="5" required><?php echo htmlspecialchars($clubToEdit['description'] ?? ''); ?></textarea></div>

                <hr class="form-divider">

                 <!-- **** NEW: Assign President **** -->
                 <div class="form-group">
                    <label for="assigned_leader_id">Club President <span class="required">*</span></label>
                     <select id="assigned_leader_id" name="assigned_leader_id" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required>
                         <option value="" disabled>-- Assign a President --</option>
                         <?php if ($currentLeader): // Add current leader to the top of the list ?>
                              <option value="<?php echo htmlspecialchars($currentLeader['user_id']); ?>" selected>
                                 <?php echo htmlspecialchars($currentLeader['username']); ?> (Current)
                             </option>
                         <?php endif; ?>
                         <?php foreach ($potentialNewLeaders as $leader): ?>
                             <option value="<?php echo htmlspecialchars($leader['id']); ?>">
                                 <?php echo htmlspecialchars($leader['username']); ?> (<?php echo htmlspecialchars($leader['email']); ?>)
                             </option>
                         <?php endforeach; ?>
                     </select>
                      <small class="input-hint">Select user to lead this club.</small>
                     <?php if(empty($potentialNewLeaders) && !$currentLeader) : ?>
                          <p class="error-text input-hint">No eligible users found to assign.</p>
                     <?php endif; ?>
                  </div>

                 <!-- Club Status -->
                  <div class="form-group"><label for="club_status">Club Status <span class="required">*</span></label><select id="club_status" name="club_status" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required><option value="" disabled>-- Select --</option><?php foreach ($availableStatuses as $status): ?><option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($clubToEdit['status'] === $status) ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($status)); ?></option><?php endforeach; ?></select><small class="input-hint">Set to 'Active' to allow joining/events.</small></div>
                <!-- Proposer Info -->
                 <div class="form-group"><label>Originally Proposed By</label><p class="form-static-text" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;"><?php echo htmlspecialchars($clubToEdit['proposer_username'] ?? 'N/A'); ?></p></div>

                <!-- Submit Button -->
                <div class="form-actions"> <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Update Club</button> <a href="admin.php#admin-clubs" class="btn btn-secondary">Cancel</a> </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<!-- Styles and Scripts -->
<style> /* Paste relevant styles */ </style>
<script> /* Paste relevant scripts */ </script>

</body>
</html>