<?php
session_start();

// --- PHP Prerequisites & Admin Check ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php'; // Include function files if separate

// --- Constants (Additions for Club Logo) ---
// Adjust '/cm/' if your project structure is different relative to the web root
define('CLUB_LOGO_UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/cm/uploads/club_logos/'); // Absolute server path
define('CLUB_LOGO_URL_PATH', '/cm/uploads/club_logos/'); // Relative web path for src attribute
define('LOGO_MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('LOGO_ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// **** CRITICAL: Authorization Check ****
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) { $_SESSION['error_message']="Login required."; header('Location: login.php'); exit; }
$currentUserId = $_SESSION['user']['id'];
$currentUserRole = $_SESSION['user']['role'];

// --- Get Target Club ID ---
$clubId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$clubId) { $_SESSION['error_message']="Invalid Club ID."; header('Location: dashboard.php'); exit; } // Send non-admins back to their dashboard

// --- Check Permission & Fetch Club Data ---
$canManage = false; $isClubLeader = false; $clubToManage = null; $pageError = null;
try {
    if (!isset($pdo)) { throw new Exception("DB connection error."); }
    // Fetch club data including logo_path
    $stmtClub = $pdo->prepare("SELECT * FROM clubs WHERE id = :id");
    $stmtClub->bindParam(':id', $clubId, PDO::PARAM_INT); $stmtClub->execute(); $clubToManage = $stmtClub->fetch(PDO::FETCH_ASSOC);
    if (!$clubToManage) { throw new Exception("Club not found."); }
    // Check management permission
    if ($currentUserRole === 'admin') { $canManage = true; } else { $stmtLeaderCheck = $pdo->prepare("SELECT 1 FROM club_members WHERE user_id = :user_id AND club_id = :club_id AND role = 'leader'"); $stmtLeaderCheck->bindParam(':user_id', $currentUserId, PDO::PARAM_INT); $stmtLeaderCheck->bindParam(':club_id', $clubId, PDO::PARAM_INT); $stmtLeaderCheck->execute(); if ($stmtLeaderCheck->fetch()) { $canManage = true; $isClubLeader = true; } }
    if (!$canManage) { $_SESSION['error_message']="Permission denied."; header('Location: dashboard.php'); exit; } // Deny if not admin or specific leader
} catch (Exception $e) { error_log("Manage Club Permission/Fetch Error: ".$e->getMessage()); $pageError="Could not load club data."; $clubToManage=false; }
// --- Helper Functions ---
function format_time_ago($datetime, $full = false) { /* ... function code ... */ try{ $now=new DateTime; $ts=strtotime($datetime); if($ts===false)throw new Exception(); $ago=new DateTime('@'.$ts); $diff=$now->diff($ago); $s=['y'=>'year','m'=>'month','d'=>'day','h'=>'hour','i'=>'minute','s'=>'second']; foreach($s as $k=>&$v){if(property_exists($diff,$k)){if($diff->$k)$v=$diff->$k.' '.$v.($diff->$k>1?'s':'');else unset($s[$k]);}else unset($s[$k]);} if(!$full)$s=array_slice($s,0,1); return $s?implode(', ',$s).' ago':'just now'; } catch(Exception $e){ error_log("Time format err: ".$e->getMessage()); $ts=strtotime($datetime); return $ts?date('M j, Y g:i a',$ts):'Invalid date';} }

// --- Define available categories & statuses ---
$availableCategories = ['Academic', 'Arts & Culture', 'Community Service', 'Recreation', 'Sports', 'Technology', 'Social', 'Other'];
$availableStatuses = ['pending', 'active', 'rejected']; // Needed if admin edits status
$assignableDepartments = [
    'Vice President',
    'HR Responsible',
    'General Secretary',
    'Media Responsible',
    'Sponsoring Responsible',
    'Logistique Responsible',
    'Media Member',
    'Sponsoring Member',
    'Logistique Member',
    'General Member' // Or leave blank/NULL for generic members
];
sort($assignableDepartments);

// --- Session Flash Messages ---
$actionMsg = $_SESSION['manage_club_message'] ?? null; unset($_SESSION['manage_club_message']);

// --- Initialize Form Variables ---
$formError = null; // **** INITIALIZED ****

// --- Handle POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage && $clubToManage) {
    $action = $_POST['action'] ?? null;
    $formSuccess = null;
    $redirect = true;
    $logoPathForDb = $clubToManage['logo_path']; // Start with existing path for update details
    $logoTargetPath = null; // Full server path for new upload
    $logoToRemove = null;   // Old logo server path to delete
    $fileErrorOccurred = false; // Flag for file system errors
    $uploadAttempted = false; // Flag if user submitted a file
    $uploadValidationError = null; // Specific upload error
    
    function handleLogoUpload($logoFile, $removeLogo, $clubToManage) {
        $logoPathForDb = $clubToManage['logo_path'];
        $logoToRemove = null;
        $logoTargetPath = null;
    
        if ($removeLogo) {
            $logoToRemove = $clubToManage['logo_path'];
            $logoPathForDb = null;
        } elseif ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
            if ($logoFile['size'] > LOGO_MAX_FILE_SIZE) {
                throw new Exception("Logo too large.");
            }
    
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $logoFile['tmp_name']);
            finfo_close($finfo);
    
            if (!in_array($mimeType, LOGO_ALLOWED_MIME_TYPES)) {
                throw new Exception("Invalid logo type.");
            }
    
            $fileExtension = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));
            $logoUniqueFilename = uniqid('clublogo_', true) . '.' . $fileExtension;
            $logoTargetPath = CLUB_LOGO_UPLOAD_DIR . $logoUniqueFilename;
    
            if (!file_exists(CLUB_LOGO_UPLOAD_DIR) && !mkdir(CLUB_LOGO_UPLOAD_DIR, 0775, true)) {
                throw new Exception("Cannot create logo directory.");
            }
    
            if (!is_writable(CLUB_LOGO_UPLOAD_DIR)) {
                throw new Exception("Logo directory is not writable.");
            }
    
            $logoPathForDb = CLUB_LOGO_URL_PATH . $logoUniqueFilename;
            $logoToRemove = $clubToManage['logo_path'];
        } elseif ($logoFile && $logoFile['error'] !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("Logo upload error (Code: {$logoFile['error']}).");
        }
    
                                // Check if the upload directory exists
                                $uploadDirExists = file_exists(CLUB_LOGO_UPLOAD_DIR);

                                // If the directory does not exist, attempt to create it
                                if (!$uploadDirExists) {
                                    // mkdir creates the directory with 0775 permissions, allowing the owner and group to write
                                    // This is necessary to store uploaded club logos securely
                                    if (!mkdir(CLUB_LOGO_UPLOAD_DIR, 0775, true)) {
                                        // Log an error if directory creation fails
                                        $formError = "Cannot create logo directory.";
                                        error_log("Failed to create directory: " . CLUB_LOGO_UPLOAD_DIR . ". Check permissions.");
                                        $logoTargetPath = null;
                                    } elseif (!file_exists(CLUB_LOGO_UPLOAD_DIR)) {
                                        // Double-check if the directory was created successfully
                                        $formError = "Directory creation failed unexpectedly.";
                                        error_log("Directory creation failed even after mkdir: " . CLUB_LOGO_UPLOAD_DIR);
                                        $logoTargetPath = null;
                                    } else {
                                        // Directory creation succeeded
                                        $uploadDirExists = true;
                                    }
                                }
    }

    try {
        $pdo->beginTransaction();

        switch ($action) {
            case 'update_details':
                // Retrieve inputs
                $newName=trim($_POST['club_name']??''); $newDesc=trim($_POST['club_description']??''); $newCat=trim($_POST['club_category']??''); $newSched=trim($_POST['club_schedule']??'');
                $logoFile = $_FILES['club_logo'] ?? null;

                
                
                $removeLogo = isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1';

                // Basic Validation
                if(empty($newName)||empty($newDesc)||empty($newCat)){ $formError="Name, Desc, Category required."; }
                elseif(!in_array($newCat,$availableCategories)){ $formError="Invalid category."; }
                // Length checks...

                // Name uniqueness check (if changed)
                if(!$formError && $newName !== $clubToManage['name']){ /* ... name check ... */ 
                    $stmtNameCheck=$pdo->prepare("SELECT COUNT(*) FROM clubs WHERE name=:name AND id!=:id"); $stmtNameCheck->bindParam(':name',$newName); $stmtNameCheck->bindParam(':id',$clubId,PDO::PARAM_INT); $stmtNameCheck->execute(); if($stmtNameCheck->fetchColumn()>0){$formError="Club name already exists."; }
                }

                // Logo Handling Validation (only if basic validation passed)
                if (!$formError) {
                    try {
                        [$logoPathForDb, $logoToRemove, $logoTargetPath] = handleLogoUpload($logoFile, $removeLogo, $clubToManage);
                        $uploadAttempted = true; // Logo upload attempted
                        
                    } catch (Exception $e) {
                        $formError = $e->getMessage();
                    }
                }

                // Perform DB Update if still no errors
                if(!$formError){
                    $sql="UPDATE clubs SET name=:n, description=:d, category=:c, meeting_schedule=:s, logo_path=:logo WHERE id=:id"; // Added logo_path
                    $stmt=$pdo->prepare($sql);
                    // Bind params including logo
                    $stmt->bindParam(':n',$newName); $stmt->bindParam(':d',$newDesc); $stmt->bindParam(':c',$newCat); $stmt->bindParam(':s',$newSched);
                    $stmt->bindParam(':logo',$logoPathForDb); // Bind new/old/null path
                    $stmt->bindParam(':id',$clubId,PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        // Handle File System Changes AFTER successful DB update
                        $updateSuccessMessage = 'Details updated.';
                        $oldLogoServerPath = $logoToRemove ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $logoToRemove : null;
                        $newLogoServerPath = $logoTargetPath;

                        // a) Move new file if needed
                        if ($newLogoServerPath !== null && $logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
                            if (!move_uploaded_file($logoFile['tmp_name'], $newLogoServerPath)) {
                                error_log("!!! CRITICAL: DB updated but failed to move new club logo: {$logoFile['tmp_name']} to {$newLogoServerPath}");
                                $updateSuccessMessage .= " (WARNING: Failed saving new logo!)";
                                $fileErrorOccurred = true;
                            }
                        }
                        // b) Delete old file if needed
                        if (!$fileErrorOccurred && $oldLogoServerPath && file_exists($oldLogoServerPath) && $oldLogoServerPath !== $newLogoServerPath ) {
                            if (!unlink($oldLogoServerPath)) {
                                 error_log("Warning: Failed to delete old club logo: {$oldLogoServerPath}");
                                 $updateSuccessMessage .= " (Note: Couldn't remove old logo.)";
                            }
                        }
                        // Update was successful (even if file ops had warnings)
                        $formSuccess = $updateSuccessMessage;
                        // Refresh local variable for display if needed (only if not redirecting immediately)
                        $clubToManage['name']=$newName;
                        $clubToManage['logo_path']=$logoPathForDb;

                    } else { $formError="Update failed."; } // DB update itself failed
                }
                break; // End case 'update_details'


            case 'remove_member':
                // --- Remove Member ---
                $memberIdToRemove=filter_input(INPUT_POST,'member_id',FILTER_VALIDATE_INT);if($memberIdToRemove){if($memberIdToRemove===$currentUserId && $isClubLeader){$formError="Leaders cannot remove self.";}else{$stmtRole=$pdo->prepare("SELECT role FROM club_members WHERE user_id=:uid AND club_id=:cid");$stmtRole->execute([':uid'=>$memberIdToRemove,':cid'=>$clubId]);$memRole=$stmtRole->fetchColumn();if($memRole==='leader'){$formError="Cannot remove leader.";}else{$stmtDel=$pdo->prepare("DELETE FROM club_members WHERE user_id=:uid AND club_id=:cid");$stmtDel->execute([':uid'=>$memberIdToRemove,':cid'=>$clubId]);if($stmtDel->rowCount()>0)$formSuccess="Member removed.";else $formError="Remove failed.";}}}else{$formError="Invalid member ID.";}
                break;

            case 'approve_request':
                 // --- Approve Join Request ---
                 $requesterId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                 if ($requesterId) {
                     // Approve sets role to 'member', department NULL initially
                     $stmtApprove = $pdo->prepare("UPDATE club_members SET role = 'member', department = NULL WHERE user_id = :user_id AND club_id = :club_id AND role = 'pending'");
                     $stmtApprove->bindParam(':user_id', $requesterId, PDO::PARAM_INT); $stmtApprove->bindParam(':club_id', $clubId, PDO::PARAM_INT); $stmtApprove->execute();
                     if ($stmtApprove->rowCount() > 0) $formSuccess = "Join request approved (Role: Member)."; else $formError = "Approval failed.";
                 } else $formError = "Invalid user ID.";
                 break;

            case 'reject_request':
                 // --- Reject Join Request ---
                 $requesterId=filter_input(INPUT_POST,'user_id',FILTER_VALIDATE_INT);if($requesterId){$stmtR=$pdo->prepare("DELETE FROM club_members WHERE user_id=:uid AND club_id=:cid AND role='pending'");$stmtR->bindParam(':uid',$requesterId,PDO::PARAM_INT);$stmtR->bindParam(':cid',$clubId,PDO::PARAM_INT);$stmtR->execute();if($stmtR->rowCount()>0)$formSuccess="Request rejected.";else $formError="Rejection failed.";}else $formError="Invalid user ID.";
                 break;
            case 'update_department':
                $memberIdToUpdate = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
                $newDepartment = trim($_POST['new_department'] ?? ''); // Can be empty string to clear
                
                if (!$memberIdToUpdate) { $formError = "Invalid member ID for department update."; }
                // Allow empty string (clearing department) or check if it's in the defined list
                elseif (!empty($newDepartment) && !in_array($newDepartment, $assignableDepartments)) {
                     $formError = "Invalid department selected ('".htmlspecialchars($newDepartment)."').";
                }
                // Prevent changing leader's department this way (though leaders usually don't have one)
                elseif ($memberIdToUpdate === $currentUserId && $isClubLeader) { $formError = "Leaders manage departments, their own isn't set here."; }
                else {
                    // Check if member exists and is not pending/leader
                    $stmtCheckMem = $pdo->prepare("SELECT role FROM club_members WHERE user_id = :uid AND club_id = :cid");
                    $stmtCheckMem->execute([':uid' => $memberIdToUpdate, ':cid' => $clubId]);
                    $currentMemberRole = $stmtCheckMem->fetchColumn();

                    if ($currentMemberRole === 'leader') {
                        $formError = "Cannot assign a department to the club Leader.";
                    } elseif ($currentMemberRole === false) {
                         $formError = "Member not found in this club.";
                    } elseif ($currentMemberRole === 'pending') {
                         $formError = "Approve join request first before assigning department.";
                    } else {
                        // Proceed with update - Use NULL if empty string was selected
                        $deptValue = !empty($newDepartment) ? $newDepartment : null;
                        $stmtUpdateDept = $pdo->prepare("UPDATE club_members SET department = :new_dept WHERE user_id = :uid AND club_id = :cid");
                        $stmtUpdateDept->bindParam(':new_dept', $deptValue, PDO::PARAM_STR|PDO::PARAM_NULL); // Allow NULL
                        $stmtUpdateDept->bindParam(':uid', $memberIdToUpdate, PDO::PARAM_INT);
                        $stmtUpdateDept->bindParam(':cid', $clubId, PDO::PARAM_INT);
                        $stmtUpdateDept->execute();

                        // rowCount might be 0 if value didn't change, so check errorInfo instead for failure
                        if ($stmtUpdateDept->errorInfo()[0] === '00000') {
                            $formSuccess = "Member department updated successfully.";
                        } else {
                            $formError = "Failed to update department.";
                             error_log("Dept update failed: ".implode(", ", $stmtUpdateDept->errorInfo()));
                        }
                    }
                }
                break;

            case 'send_notification':
                 // --- Send Notification ---
                 $title=trim($_POST['notification_title']??''); $message=trim($_POST['notification_message']??'');
                 if(empty($title)||empty($message)){$formError='Title and Message required.';} else { $membersQuery="SELECT user_id FROM club_members WHERE club_id=:cid"; $stmtM=$pdo->prepare($membersQuery); $stmtM->bindParam(':cid',$clubId,PDO::PARAM_INT); $stmtM->execute(); $members=$stmtM->fetchAll(PDO::FETCH_ASSOC); $sent=0; if(function_exists('sendNotification')){ $fullT="[{$clubToManage['name']}] {$title}"; foreach($members as $m){if(sendNotification($m['user_id'],$fullT,$message))$sent++;else error_log("Fail send U:{$m['user_id']} C:{$clubId}");} $formSuccess="Sent to {$sent} members.";} else {$formError='Notify func missing.';error_log('sendNotification missing');}}
                 break;

            // Add other actions like 'promote_member', 'demote_leader' later if needed

            default: $formError = "Unknown action."; $redirect = false; break;
        }

        if (!$formError) $pdo->commit(); else $pdo->rollBack();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $formError = "An error occurred: " . $e->getMessage();
        error_log("Manage Club Action '{$action}' Error (Club ID {$clubId}): " . $e->getMessage());
        $redirect = false;
    }

    if ($redirect) {
        if ($formSuccess) $_SESSION['manage_club_message'] = ['type' => 'success', 'text' => $formSuccess];
        if ($formError) $_SESSION['manage_club_message'] = ['type' => 'error', 'text' => $formError];
        header("Location: manage-club.php?id=" . $clubId); exit;
    }
} // --- End POST Handling ---


// --- Fetch Additional Data for Display AFTER potential POST actions ---
$clubMembers = []; $clubEvents = []; $pendingRequests = [];
if ($clubToManage) {
    try {
        // Fetch Members - include department now, order by role then department?
        $stmtMembers = $pdo->prepare("SELECT u.id, u.username, cm.role, cm.department
                                     FROM users u JOIN club_members cm ON u.id = cm.user_id
                                     WHERE cm.club_id = :club_id AND cm.role IN ('leader', 'member')
                                     ORDER BY FIELD(cm.role, 'leader', 'member'), cm.department ASC, u.username ASC");
        $stmtMembers->bindParam(':club_id', $clubId, PDO::PARAM_INT); $stmtMembers->execute(); $clubMembers = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Pending Join Requests (same)
        $stmtPending = $pdo->prepare("SELECT u.id, u.username, cm.joined_at FROM users u JOIN club_members cm ON u.id = cm.user_id WHERE cm.club_id = :club_id AND cm.role = 'pending' ORDER BY cm.joined_at ASC");
        $stmtPending->bindParam(':club_id', $clubId, PDO::PARAM_INT); $stmtPending->execute(); $pendingRequests = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Active Events (same)
        $stmtEvents = $pdo->prepare("SELECT id, name, event_date FROM events WHERE club_id = :club_id AND status = 'active' ORDER BY event_date DESC LIMIT 10");
        $stmtEvents->bindParam(':club_id', $clubId, PDO::PARAM_INT); $stmtEvents->execute(); $clubEvents = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Manage Club Fetch List Error: ".$e->getMessage()); $pageError = $pageError ?? "Could not load members/events."; }
}

// --- NOW START HTML OUTPUT ---
include_once 'header.php'; 
?>

<!-- Main Content Area -->
<main class="main-content manage-club-container">
    <div class="manage-club-wrapper">

        <!-- Page Header -->
        <div class="section-header"> <?php if ($clubToManage): ?> <h1 class="section-title">Manage Club: <?php echo htmlspecialchars($clubToManage['name']); ?></h1> <p class="section-subtitle">Update details, manage members & requests, view events, send notifications.</p> <?php else: ?> <h1 class="section-title">Manage Club</h1> <?php endif; ?> </div>

        <!-- Feedback Messages -->
        <?php if ($pageError): ?><div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?> <a href="<?php echo ($currentUserRole==='admin'?'admin.php':'dashboard.php');?>" class="text-link">Back</a></div><?php endif; ?>
        <?php if ($actionMsg): ?><div class="message <?php echo $actionMsg['type']==='success'?'success-message':'error-message';?>" role="alert"><?php echo htmlspecialchars($actionMsg['text']); ?></div><?php endif; ?>
        <?php if ($formError && !$redirect):?><div class="message error-message" role="alert"><?php echo htmlspecialchars($formError);?></div><?php endif; ?>


        <?php if ($clubToManage && !$pageError): ?>
            <div class="manage-club-grid">

                <!-- Left Column: Edit Details & Send Notification -->
                <div class="manage-column-main space-y-8">
                    <section class="card"> <h2 class="section-heading">Edit Club Details</h2> <form method="POST" action="manage-club.php?id=<?php echo $clubId; ?>" class="edit-form space-y-4"> <input type="hidden" name="action" value="update_details"> <div class="form-group"><label for="club_name">Name<span class="required">*</span></label><input type="text" id="club_name" name="club_name" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($clubToManage['name']);?>" required></div> <div class="form-group"><label for="club_category">Category<span class="required">*</span></label><select id="club_category" name="club_category" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required><option value="" disabled>-- Select --</option><?php foreach($availableCategories as $cat):?><option value="<?php echo htmlspecialchars($cat);?>" <?php echo($clubToManage['category']===$cat)?'selected':'';?>><?php echo htmlspecialchars($cat);?></option><?php endforeach;?></select></div> <div class="form-group"><label for="club_schedule">Schedule</label><input type="text" id="club_schedule" name="club_schedule" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($clubToManage['meeting_schedule']??'');?>"></div> <div class="form-group"><label for="club_description">Description<span class="required">*</span></label><textarea id="club_description" name="club_description" class="form-textarea" rows="5" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required><?php echo htmlspecialchars($clubToManage['description']??'');?></textarea></div> <!-- **** NEW: Club Logo Management **** -->
                            <hr class="form-divider">
                             <div class="form-group">
                                <label>Current Club Logo</label>
                                <div class="current-logo-preview">
                                    <?php $currentLogoWebPath=$clubToManage['logo_path']??null; $currentLogoServerPath=$currentLogoWebPath?rtrim($_SERVER['DOCUMENT_ROOT'],'/').$currentLogoWebPath:null; $hasCurrentLogo=$currentLogoWebPath&&file_exists($currentLogoServerPath);?>
                                    <?php if($hasCurrentLogo):?>
                                        <img src="<?php echo htmlspecialchars($currentLogoWebPath);?>?<?php echo time();?>" alt="Logo" class="logo-preview-img">
                                        <label class="checkbox-label small-label"><input type="checkbox" name="remove_logo" value="1" class="form-checkbox-sm"><span>Remove</span></label>
                                    <?php else:?>
                                        <p class="form-static-text italic">No logo.</p>
                                    <?php endif;?>
                                </div>
                            </div>
                             <div class="form-group">
                                <label for="club_logo">Upload New Logo</label>
                                <input type="file" id="club_logo" name="club_logo" class="form-input file-input" accept=".jpg,.jpeg,.png,.gif,.webp">
                                <small class="input-hint">Max: <?php echo LOGO_MAX_FILE_SIZE/1024/1024;?> MB. Overwrites current.</small>
                            </div>
                             <!-- **** END Club Logo Management **** -->
                              <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Details</button></div> </form> </section>
                    <section class="card"> <h2 class="section-heading">Send Notification</h2> <form method="POST" action="manage-club.php?id=<?php echo $clubId; ?>" class="space-y-4 dashboard-form"><input type="hidden" name="action" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="send_notification"><div class="form-group"><label for="notification_title" class="form-label">Title<span class="required">*</span></label><input type="text" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" id="notification_title" name="notification_title" class="form-input" placeholder="e.g., Meeting Reminder" required></div><div class="form-group"><label for="notification_message" class="form-label">Message<span class="required">*</span></label><textarea id="notification_message" name="notification_message" class="form-textarea" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" rows="4" placeholder="Enter details..." required></textarea></div><div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button></div></form> </section>
                </div>

                <!-- Right Column: Requests, Members & Events -->
                 <div class="manage-column-sidebar space-y-8">
                     <!-- **** NEW: Pending Join Requests Section **** -->
                    <section class="card">
                        <h2 class="section-heading">Pending Join Requests (<?php echo count($pendingRequests); ?>)</h2>
                        <?php if(count($pendingRequests) > 0): ?>
                            <div class="list-container compact-list">
                                <?php foreach($pendingRequests as $request): ?>
                                    <div class="list-item request-list-item">
                                        <div class="item-main">
                                            <span class="member-name"><?php echo htmlspecialchars($request['username']); ?></span>
                                            <span class="item-meta req-time">Requested <?php echo format_time_ago($request['joined_at']); ?></span>
                                        </div>
                                        <div class="item-action request-actions">
                                             <form method="POST" action="manage-club.php?id=<?php echo $clubId; ?>" class="action-form"><input type="hidden" name="action" value="approve_request"><input type="hidden" name="user_id" value="<?php echo $request['id']; ?>"><button type="submit" class="btn-action approve" title="Approve"><i class="fas fa-check"></i></button></form>
                                             <form method="POST" action="manage-club.php?id=<?php echo $clubId; ?>" class="action-form" onsubmit="return confirm('Reject join request from <?php echo htmlspecialchars(addslashes($request['username'])); ?>?');"><input type="hidden" name="action" value="reject_request"><input type="hidden" name="user_id" value="<?php echo $request['id']; ?>"><button type="submit" class="btn-action reject" title="Reject"><i class="fas fa-times"></i></button></form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                             <p class="empty-list-message small">No pending join requests.</p>
                        <?php endif; ?>
                    </section> <!-- **** END Pending Join Requests **** -->

                    <!-- **** MODIFIED Members Section **** -->
                    <section class="card">
                        <h2 class="section-heading">Current Members (<?php echo count($clubMembers); ?>)</h2>
                        <?php if(count($clubMembers)>0): ?>
                            <div class="list-container compact-list members-list">
                                <?php foreach($clubMembers as $member):?>
                                    <div class="list-item member-list-item">
                                        <div class="item-main member-info">
                                            <span class="member-name"><?php echo htmlspecialchars($member['username']);?><!-- Display Department -->
                                            <span class="member-department">
                                                <?php echo htmlspecialchars($member['department'] ?? 'No Department'); // Show department ?>
                                            </span></span>
                                            
                                            
                                        </div>
                                        <div class="item-action member-actions">
                                            <?php // --- Department Update Form (Not for leader/self) --- ?>
                                            <?php if ($canManage && $member['role'] !== 'leader' && $member['id'] !== $currentUserId): ?>
                                                <form method="POST" action="manage-club.php?id=<?php echo $clubId; ?>" class="action-form department-update-form">
                                                    <input type="hidden" name="action" value="update_department">
                                                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                    <select name="new_department" class="form-select-sm" aria-label="Change department for <?php echo htmlspecialchars($member['username']); ?>">
                                                        <option value="">-- Assign Dept --</option> <!-- Option to clear -->
                                                        <?php foreach($assignableDepartments as $dept): ?>
                                                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($member['department'] === $dept) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($dept); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn-action update" title="Update Department"><i class="fas fa-check"></i></button>
                                                </form>
                                            <?php endif; ?>

                                            <?php // --- Remove Member Form (Not for leader/self) --- ?>
                                            <?php if($canManage && $member['role'] !== 'leader' && $member['id'] !== $currentUserId):?>
                                                <form method="POST" action="manage-club.php?id=<?php echo $clubId;?>" class="action-form" onsubmit="return confirm('Remove <?php echo htmlspecialchars(addslashes($member['username'])); ?>?');"> <input type="hidden" name="action" value="remove_member"> <input type="hidden" name="member_id" value="<?php echo $member['id'];?>"> <button type="submit" class="btn-delete-sm" title="Remove Member"><i class="fas fa-user-minus"></i></button> </form>
                                            <?php endif;?>
                                        </div>
                                    </div>
                                <?php endforeach;?>
                            </div>
                        <?php else:?> <p class="empty-list-message small">No members yet (excluding pending).</p> <?php endif;?>
                    </section>
                    <!-- **** END Members Section **** -->
     <section class="card"> <h2 class="section-heading">Recent Club Events</h2> <?php if(count($clubEvents)>0): ?><div class="list-container compact-list"><?php foreach($clubEvents as $event):?><div class="list-item event-list-item"><div class="item-main"><a href="event-detail.php?id=<?php echo $event['id'];?>" class="item-title event-link"><?php echo htmlspecialchars($event['name']);?></a></div><div class="item-action"><a href="edit_event.php?id=<?php echo $event['id'];?>" class="btn-action edit" title="Edit"><i class="fas fa-edit"></i></a></div></div><?php endforeach;?></div><?php else:?><p class="empty-list-message small">No active events.</p><?php endif;?><div class="form-actions" style="margin-top:1rem;"><a href="create_event.php?club_id=<?php echo $clubId;?>" class="btn btn-secondary btn-sm" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;"><i class="fas fa-plus"></i> New Event</a></div> </section>
                 </div> <!-- End Sidebar Column -->
            </div> <!-- End Grid -->
        <?php endif; // End check for valid $clubToManage ?>
    </div> <!-- End Wrapper -->
    <?php include_once 'footer.php'; ?>
</main>

<!-- Styles -->
<style>
    /* --- Paste all previous styles --- */
    /* --- Add/Modify Styles for Member List --- */
.members-list .list-item { flex-wrap: wrap; /* Allow wrap */ }
    .member-info { display: flex; flex-direction: column; flex-grow: 1; padding-right: 1rem; min-width: 150px; /* Prevent excessive squish */ }
    .member-name { font-weight: 600;margin-bottom: 6px; }
    .member-name .member-department { font-size: 0.7rem; color: #fff; margin-top: 2px; border-radius: 20px;  background-color:var(--primary-color); padding: 2px 10px; } body.dark .member-department { color: #ccc; }
    .member-actions { display: flex; align-items: center; flex-shrink: 0; flex-wrap: wrap; gap: 0.5rem; margin-left: auto; /* Push actions right */}
    .department-update-form { display: inline-flex; align-items: center; gap: 0.3rem; }
    .form-select-sm { /* Keep previous style */
        width: auto; /* Allow auto width for select */
        border: 2px solid var(--border-color); /* Keep border style */
        border-radius: 0.5rem; /* Keep border radius */
        padding: 0.3rem 0.5rem; /* Adjust padding for smaller size */
        font-size: 0.9rem; /* Smaller font size for compactness */
    }
    .department-update-form .btn-action.update { /* Keep previous style */
        background-color: #007bff; /* Blue background for update button */
        color: #fff; /* White text */
        border: none; /* No border */
        padding: 0.3rem 0.5rem; /* Padding for button */
        border-radius: 0.3rem; /* Rounded corners */
        cursor: pointer; /* Pointer cursor on hover */
    }
    .department-update-form .btn-action.update:hover { /* Hover effect for update button */
        background-color: #0056b3; /* Darker blue on hover */
        color: #fff; /* White text on hover */
    }
    .member-actions .btn-delete-sm { /* Keep previous style */
        color: #dc3545; /* Red color for delete button */
        font-size: 1.2rem; /* Larger icon size */
        padding: 0.3rem 0.5rem; /* Padding for button */
        border-radius: 0.3rem; /* Rounded corners */
    }
    .member-actions .btn-delete-sm:hover { /* Hover effect for delete button */
        color: #c82333; /* Darker red on hover */
    }

    /* Adjust mobile view if needed */
     @media (max-width: 768px) { /* Wider breakpoint for action wrapping */
        .member-list-item { flex-direction: column; align-items: flex-start; }
        .member-actions { margin-left: 0; margin-top: 0.75rem; width: 100%; justify-content: flex-start; }
         .department-update-form { width: 100%; } /* Make form take full width */
         .department-update-form select { flex-grow: 1; max-width: none; }
         
     }
    /* Reuse styles from profile.php/admin.php: card, message, form-group, form-input, form-textarea, form-select, form-actions, btn, btn-primary, btn-secondary, section-heading, list-container, list-item, item-main, item-action, empty-list-message, etc. */
    .manage-club-container .manage-club-wrapper { max-width: 1100px; margin: 1rem auto; }
    .manage-club-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 2rem; }
    @media (min-width: 992px) { .manage-club-grid { grid-template-columns: 2fr 1fr; } }
    .section-header { text-align: left; max-width: none; margin-bottom: 1.5rem; }
    .space-y-8 > * + * { margin-top: 2rem; } /* Consistent spacing between cards */

    /* List item refinements */
    .list-item { align-items: center; }
    .compact-list .list-item { padding: 0.6rem 1rem; }
    .request-list-item, .member-list-item, .event-list-item { /* Can add specific tweaks */
        display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 1rem; border-bottom: 1px solid #ddd;
        background-color: #f9f9f9; /* Light background for better visibility */
        transition: background-color 0.2s; /* Smooth hover effect */
        cursor: pointer; /* Pointer cursor for better UX */
    }
    .request-list-item:hover, .member-list-item:hover, .event-list-item:hover { background-color: #f1f1f1; } /* Hover effect */
    .item-main { flex-grow: 1; } /* Allow main item to grow */
    .item-main .member-name { font-weight: bold; } /* Emphasize member name */
    .item-main .role-badge { background-color: #007bff; color: #fff; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8rem; margin-left: 0.5rem; } /* Leader badge style */
    .item-action { display: flex; align-items: center; } /* Align action buttons */
    .item-action .btn-action { margin-left: 0.5rem; } /* Space between action buttons */
    .item-action .btn-delete-sm { color: #dc3545; } /* Delete button color */
    .item-action .btn-delete-sm:hover { color: #c82333; } /* Darker on hover */
    .item-action .btn-action.edit { color: #007bff; } /* Edit button color */
    .item-action .btn-action.edit:hover { color: #0056b3; } /* Darker on hover */
    .item-action .btn-action.approve { color: #28a745; } /* Approve button color */
    .item-action .btn-action.approve:hover { color: #218838; } /* Darker on hover */
    .item-action .btn-action.reject { color: #dc3545; } /* Reject button color */
    .item-action .btn-action.reject:hover { color: #c82333; } /* Darker on hover */
    .item-action .btn-action.edit, .item-action .btn-action.approve, .item-action .btn-action.reject { font-size: 1.2rem; } /* Icon size */
    .item-action .btn-action i { margin: 0; } /* Remove icon margin */
    .item-action .btn-action.approve:hover, .item-action .btn-action.reject:hover { background-color: rgba(0, 123, 255, 0.1); } /* Light background on hover for action buttons */
    .item-action .btn-action.edit:hover { background-color: rgba(0, 123, 255, 0.1); } /* Light background on hover for edit button */
    .item-action .btn-action.approve, .item-action .btn-action.reject { border-radius: 3px; } /* Rounded corners for action buttons */
    .item-action .btn-action.edit, .item-action .btn-action.approve, .item-action .btn-action.reject { padding: 0.3rem 0.5rem; } /* Padding for action buttons */
    .item-action .btn-action.edit:hover, .item-action .btn-action.approve:hover, .item-action .btn-action.reject:hover { color: #fff; } /* White text on hover for action buttons */
    .item-action .btn-action.edit:hover { background-color: #0056b3; } /* Darker blue on hover for edit button */
    .item-action .btn-action.approve:hover { background-color: #218838; } /* Darker green on hover for approve button */
    .item-action .btn-action.reject:hover { background-color: #c82333; } /* Darker red on hover for reject button */
    .item-action .btn-action.approve, .item-action .btn-action.reject { border-radius: 3px; } /* Rounded corners for action buttons */
    .item-action .btn-action.edit, .item-action .btn-action.approve, .item-action .btn-action.reject { padding: 0.3rem 0.5rem; } /* Padding for action buttons */
    .item-action .btn-action.edit:hover, .item-action .btn-action.approve:hover, .item-action .btn-action.reject:hover { color: #fff; } /* White text on hover for action buttons */
    .item-action .btn-action.edit:hover { background-color: #0056b3; } /* Darker blue on hover for edit button */
    .item-action .btn-action.approve:hover { background-color: #218838; } /* Darker green on hover for approve button */
    .item-action .btn-action.reject:hover { background-color: #c82333; } /* Darker red on hover for reject button */
    .item-action .btn-action.edit, .item-action .btn-action.approve, .item-action .btn-action.reject { border-radius: 3px; } /* Rounded corners for action buttons */   
    .request-actions { display: inline-flex; gap: 0.5rem; }
    .req-time { font-size: 0.75rem; color: #888; margin-left: 0.5rem;} body.dark .req-time { color: #aaa;}
    .btn-action { /* Reuse from admin */ background: none; border: none; cursor: pointer; padding: 0.3rem 0.4rem; font-size: 1rem; line-height: 1; border-radius: 3px; transition: background-color 0.2s, color 0.2s; }
    .btn-action.approve { color: #155724; } .btn-action.approve:hover { background-color: #d4edda; } body.dark .btn-action.approve { color: #a3cfbb; } body.dark .btn-action.approve:hover { background-color: rgba(163, 207, 187, 0.2); }
    .btn-action.reject { color: #721c24; } .btn-action.reject:hover { background-color: #f8d7da; } body.dark .btn-action.reject { color: #f5c6cb; } body.dark .btn-action.reject:hover { background-color: rgba(245, 198, 203, 0.2); }
    .btn-delete-sm { background: none; border: none; color: #aaa; cursor: pointer; padding: 0.1rem 0.3rem; font-size: 0.9rem; line-height: 1;} .btn-delete-sm:hover { color: #dc3545; } body.dark .btn-delete-sm { color: #888; } body.dark .btn-delete-sm:hover { color: #f5c6cb; }
    .empty-list-message.small { font-size: 0.9rem; padding: 1rem; text-align: center; }
/* --- Add styles for logo preview --- */
.current-logo-preview { display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem; }
    .logo-preview-img { max-width: 60px; max-height: 60px; border-radius: 4px; border: 1px solid var(--border-color); object-fit: contain; background-color: #f8f9fa;} body.dark .logo-preview-img { border-color: #444; background-color: #333; }
    .checkbox-label.small-label { font-size: 0.9rem; color: #555; } body.dark .checkbox-label.small-label { color: #ccc; }
    .form-checkbox-sm { width: 0.9rem; height: 0.9rem; margin-right: 0.4rem; accent-color: var(--primary-color); }
     .file-input { padding: 0.5rem; } /* Adjust file input padding if needed */
     .form-static-text.italic { font-style: italic; color: #888;} body.dark .form-static-text.italic { color: #aaa;}
</style>



</body>
</html>