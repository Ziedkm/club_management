<?php
session_start();

// --- PHP Prerequisites & Logic ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php';

// --- Authentication & Authorization ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['error_message'] = "Please log in first.";
    header('Location: login.php?redirect=create_club.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// --- Constants (Additions for Club Logo) ---
define('CLUB_LOGO_UPLOAD_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/cm/uploads/club_logos/'); // Absolute path
define('CLUB_LOGO_URL_PATH', '/cm/uploads/club_logos/'); // Relative web path
define('LOGO_MAX_FILE_SIZE', 2 * 1024 * 1024); // Max logo size 2MB (adjust as needed)
define('LOGO_ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']); // Same as events or different?
// --- Set Default Timezone ---
// Replace 'Your/Timezone' with the appropriate value, e.g., 'America/New_York'
date_default_timezone_set('Africa/Tunis'); // Example

// --- Role Specific Logic & Data Fetching ---
$availableCategories = ['Academic', 'Arts & Culture', 'Community Service', 'Recreation', 'Sports', 'Technology', 'Social', 'Other'];
$potentialLeaders = []; // For Admin use
$alreadyLeadsClub = false; // For Student use
$pageError = null;

try {
    if (!isset($pdo)) { throw new Exception("DB connection not available."); }

    if ($userRole === 'admin') {
        // Fetch potential leaders (non-admins)
        $stmtUsers = $pdo->prepare("SELECT id, username, email FROM users WHERE role != 'admin' ORDER BY username ASC");
        $stmtUsers->execute();
        $potentialLeaders = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        if (empty($potentialLeaders)) {
            // Allow admin to proceed but maybe show warning? Or disable leader selection.
            $pageError = "No eligible users found to assign as club president.";
        }
    } elseif ($userRole === 'student') {
        // Check if student already leads a club
        $stmtCheck = $pdo->prepare("SELECT 1 FROM club_members WHERE user_id = :user_id AND role = 'leader' LIMIT 1");
        $stmtCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            $alreadyLeadsClub = true;
            $pageError = "You are already leading a club. Students can only lead one club.";
        }
    } else { // club_leader role
        // Redirect club leaders away
        $_SESSION['error_message'] = "Club leaders cannot create new clubs.";
        header('Location: dashboard.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Create Club Initial Check/Fetch Error: " . $e->getMessage());
    $pageError = "An error occurred while preparing the page.";
}


// --- Initialize Variables ---
$clubName = ''; $clubDescription = ''; $clubCategory = ''; $clubSchedule = '';
$assignedLeaderId = ''; // Only used by admin form
$formError = null; $formSuccess = null;
$pageError = $pageError ?? null; // Ensure pageError exists
$logoPathForDb = null; // For logo upload, if needed

// --- Get Session Flash Messages ---
if (isset($_SESSION['error_message']) && !$pageError) { $pageError = $_SESSION['error_message']; unset($_SESSION['error_message']); } // Prioritize page errors set above
if (isset($_SESSION['success_message'])) { $formSuccess = $_SESSION['success_message']; unset($_SESSION['success_message']); }


// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError) { // Allow submission only if no blocking errors

    // Retrieve and sanitize input
    $clubName = trim($_POST['club_name'] ?? '');
    $clubDescription = trim($_POST['club_description'] ?? '');
    $clubCategory = trim($_POST['club_category'] ?? '');
    $clubSchedule = trim($_POST['club_schedule'] ?? '');
    // Get assigned leader ID only if admin submitted
    $assignedLeaderId = ($userRole === 'admin') ? filter_input(INPUT_POST, 'assigned_leader_id', FILTER_VALIDATE_INT) : null;
    $logoFile = $_FILES['club_logo'] ?? null; // NEW: Get logo file data

    // --- Validation ---
    $formError = ''; // Reset
    $logoTargetPath = null; // Full path for moving file
    $logoUniqueFilename = null; // Filename for DB
    if (empty($clubName) || empty($clubCategory) || empty($clubDescription)) {
        $formError = "Club Name, Category, and Description are required.";
    } elseif (!in_array($clubCategory, $availableCategories)) {
        $formError = "Invalid category selected.";
    } elseif ($userRole === 'admin' && !$assignedLeaderId) { // Admin must select a leader
        $formError = "You must select a user to assign as Club President.";
    } // Add length validations...

    // Validate selected leader ID if admin
    if (!$formError && $userRole === 'admin') {
        $stmtValidLeader = $pdo->prepare("SELECT 1 FROM users WHERE id = :id AND role != 'admin'");
        $stmtValidLeader->bindParam(':id', $assignedLeaderId, PDO::PARAM_INT);
        $stmtValidLeader->execute();
        if (!$stmtValidLeader->fetch()) {
             $formError = "Invalid user selected for president.";
        }
    }

    // Check name uniqueness
    if (!$formError) {
        $stmtCheckName = $pdo->prepare("SELECT id, status FROM clubs WHERE name = :name AND status != 'rejected' LIMIT 1");
        $stmtCheckName->bindParam(':name', $clubName, PDO::PARAM_STR);
        $stmtCheckName->execute();
        $existingClub = $stmtCheckName->fetch(PDO::FETCH_ASSOC);
        if ($existingClub) { $formError = "A club with this name already exists".($existingClub['status']==='pending'?" (pending).":"."); }
    }
    // *** NEW: Validate Logo Upload (Optional) ***
    if (!$formError && $logoFile && $logoFile['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($logoFile['error'] !== UPLOAD_ERR_OK) { $formError = "Logo upload error (Code: {$logoFile['error']})."; }
        elseif ($logoFile['size'] > LOGO_MAX_FILE_SIZE) { $formError = "Logo file too large (Max: ".(LOGO_MAX_FILE_SIZE / 1024 / 1024)." MB)."; }
        elseif (!in_array(mime_content_type($logoFile['tmp_name']), LOGO_ALLOWED_MIME_TYPES)) { $formError = "Invalid logo file type."; }
        else {
            // Generate unique filename & path
            $fileExtension = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) { $formError = "Invalid logo file extension."; }
            else {
                $logoUniqueFilename = uniqid('clublogo_', true) . '.' . $fileExtension;
                $logoTargetPath = CLUB_LOGO_UPLOAD_DIR . $logoUniqueFilename; // Full server path
                // Check/Create Directory
                if (!file_exists(CLUB_LOGO_UPLOAD_DIR)) { if (!mkdir(CLUB_LOGO_UPLOAD_DIR, 0775, true)) { $formError = "Cannot create logo directory."; $logoTargetPath = null; } }
                elseif (!is_writable(CLUB_LOGO_UPLOAD_DIR)) { $formError = "Logo directory not writable."; $logoTargetPath = null; }
                 // Set path for DB (relative web path)
                if($logoTargetPath) $logoPathForDb = CLUB_LOGO_URL_PATH . $logoUniqueFilename;
            }
        }
    } // End Logo Validation


    // --- Process if No Errors ---
    
    if (empty($formError)) {
            $uploadSuccess = false;
            // Attempt to move file only if one was provided and validated correctly
            if ($logoTargetPath !== null && $logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
                 if (move_uploaded_file($logoFile['tmp_name'], $logoTargetPath)) {
                    $uploadSuccess = true;
                } else {
                     $formError = "Failed to save uploaded club logo."; error_log("Logo upload move failed: {$logoFile['tmp_name']} to {$logoTargetPath}"); $logoPathForDb = null;
                }
            } elseif ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK && $logoTargetPath === null) {
                 $formError = $formError ?: "Logo directory issue prevented saving image."; $logoPathForDb = null;
            }
        try {
            $clubStatus = ($userRole === 'admin') ? 'active' : 'pending';
            $proposerId = ($userRole === 'admin') ? $userId : $userId; // Admin is proposer if they create it directly

            $pdo->beginTransaction();

            // Insert club
            
             $sqlInsertClub = "INSERT INTO clubs (name, description, category, meeting_schedule, status, proposed_by_user_id, logo_path) VALUES (:name, :description, :category, :schedule, :status, :proposer_id, :logo_path)";
             $stmtInsertClub = $pdo->prepare($sqlInsertClub);
             // Bind parameters...
             $stmtInsertClub->bindParam(':name', $clubName);
             $stmtInsertClub->bindParam(':description', $clubDescription);
             $stmtInsertClub->bindParam(':category', $clubCategory);
             $stmtInsertClub->bindParam(':schedule', $clubSchedule);
             $stmtInsertClub->bindParam(':status', $clubStatus);
             $stmtInsertClub->bindParam(':proposer_id', $proposerId, PDO::PARAM_INT);
             $stmtInsertClub->bindParam(':logo_path', $logoPathForDb); // Bind the logo path/filename

            if ($stmtInsertClub->execute()) {
                $newClubId = $pdo->lastInsertId();
                $successMessage = '';

                // If admin created it, make the ASSIGNED user the leader/president
                if ($userRole === 'admin') {
                    $sqlMakePresident = "INSERT INTO club_members (user_id, club_id, role, department) VALUES (:user_id, :club_id, 'leader', 'President')";
                    // update also the user role to 'club_leader' if not already
                    $sqlUpdateUserRole = "UPDATE users SET role = 'club_leader' WHERE id = :user_id AND role != 'admin'";
                    $stmtUpdateUserRole = $pdo->prepare($sqlUpdateUserRole);
                    $stmtUpdateUserRole->bindParam(':user_id', $assignedLeaderId, PDO::PARAM_INT); // Use assigned ID
                
                    $stmtMakePresident = $pdo->prepare($sqlMakePresident);
                    $stmtMakePresident->bindParam(':user_id', $assignedLeaderId, PDO::PARAM_INT); // Use assigned ID
                    $stmtMakePresident->bindParam(':club_id', $newClubId, PDO::PARAM_INT);
                    if (!$stmtMakePresident->execute() || !$stmtUpdateUserRole->execute()) { $pdo->rollBack(); throw new Exception("Failed to assign president."); }
                    $successMessage = "Club '" . htmlspecialchars($clubName) . "' created and president assigned!";
                } else { // Student submitted
                    $successMessage = "Club proposal for '" . htmlspecialchars($clubName) . "' submitted for review!";
                }

                $pdo->commit();
                $_SESSION['success_message'] = $successMessage;
                header('Location: ' . ($userRole === 'admin' ? 'admin.php#admin-clubs' : 'dashboard.php'));
                exit;

            } else { /* Handle insert club failure */ $pdo->rollBack(); $formError = "Failed to submit proposal."; error_log("Club insert failed: ".implode(", ",$stmtInsertClub->errorInfo())); }
        } catch (Exception $e) { /* Handle exception */ if($pdo->inTransaction()) $pdo->rollBack(); $formError = "Error: ".$e->getMessage(); error_log("Club creation error: ".$e->getMessage()); }
    }

    // If errors occurred after POST, store in session and redirect back
    if (!empty($formError)) {
        $_SESSION['error_message'] = $formError; // Use error_message for consistency
        header("Location: create_club.php");
        exit;
    }
} // End POST handling

// --- NOW START HTML OUTPUT ---
include_once 'header.php'; // Use header.php directly
?>

<!-- Main Content Area -->
<main class="main-content create-club-container">
    <div class="create-club-wrapper card">

        <!-- Page Header -->
        <div class="section-header">
            <h1 class="section-title"><?php echo ($userRole === 'admin') ? 'Create New Club' : 'Propose a New Club'; ?></h1>
            <p class="section-subtitle"><?php echo ($userRole === 'admin') ? 'Enter details and assign a president. Club will be active immediately.' : 'Fill out details. Proposal requires admin review.'; ?></p>
        </div>

        <!-- Messages Display -->
        <?php if ($pageError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?></div><?php endif; ?>
        <?php if ($formError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($formError); ?></div><?php endif; ?>
        <?php if ($formSuccess): ?> <div class="message success-message" role="alert"><?php echo htmlspecialchars($formSuccess); ?></div><?php endif; ?>

        <!-- Club Creation Form -->
        <?php if (!$pageError): // Show form only if no blocking errors ?>
            <form method="POST" action="create_club.php" class="create-form">
                <!-- Club Name -->
                <div class="form-group"><label for="club_name">Club Name <span class="required">*</span></label><input type="text" id="club_name" name="club_name" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($clubName); ?>" maxlength="100" required></div>
                <!-- Club Category Dropdown -->
                <div class="form-group"><label for="club_category">Category <span class="required">*</span></label><select id="club_category" name="club_category" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" required><option value="" disabled <?php echo empty($clubCategory) ? 'selected' : ''; ?>>-- Select Category --</option><?php foreach ($availableCategories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($clubCategory === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?></select></div>
                <!-- Club Description -->
                <div class="form-group"><label for="club_description">Description <span class="required">*</span></label><textarea id="club_description" name="club_description" class="form-textarea" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" rows="5" placeholder="Purpose & activities..." required><?php echo htmlspecialchars($clubDescription); ?></textarea></div>
                <!-- Meeting Schedule -->
                <div class="form-group"><label for="club_schedule">Meeting Schedule (Optional)</label><input type="text" id="club_schedule" name="club_schedule" class="form-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;" value="<?php echo htmlspecialchars($clubSchedule); ?>" maxlength="100" placeholder="e.g., Tuesdays at 5 PM"></div>
                <!-- **** NEW: Club Logo Upload **** -->
                <div class="form-group">
                    <label for="club_logo">Club Logo (Optional)</label>
                    <input type="file" id="club_logo" name="club_logo" class="form-input file-input" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;"
                           accept=".jpg, .jpeg, .png, .gif, .webp">
                     <small class="input-hint">Max: <?php echo LOGO_MAX_FILE_SIZE / 1024 / 1024; ?> MB. Types: JPG, PNG, GIF, WEBP.</small>
                </div>

                <!-- **** Assign President (Admin Only) **** -->
                <?php if ($userRole === 'admin'): ?>
                    <hr class="form-divider">
                    <div class="form-group">
                        <label for="assigned_leader_id">Assign Club President <span class="required">*</span></label>
                        <?php if (!empty($potentialLeaders)): ?>
                            <select id="assigned_leader_id" name="assigned_leader_id" class="form-input" required style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;">
                                 <option value="" disabled <?php echo empty($assignedLeaderId) ? 'selected' : ''; ?>>-- Select User --</option>
                                <?php foreach ($potentialLeaders as $leader): ?>
                                    <option value="<?php echo htmlspecialchars($leader['id']); ?>" <?php echo ($assignedLeaderId == $leader['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($leader['username']); ?> (<?php echo htmlspecialchars($leader['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="input-hint">This user will have 'leader' role and 'President' department for this club.</small>
                        <?php else: ?>
                             <p class="error-text input-hint">No eligible users found to assign.</p>
                        <?php endif; ?>
                     </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" <?php echo ($userRole === 'admin' && empty($potentialLeaders)) ? 'disabled' : '';?>>
                        <i class="fas fa-paper-plane"></i> <?php echo ($userRole === 'admin') ? 'Create Club & Assign President' : 'Submit Proposal'; ?>
                    </button>
                     <a href="<?php echo ($userRole === 'admin' ? 'admin.php#admin-clubs' : 'dashboard.php'); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>

    </div> <!-- End Wrapper -->
    <?php include_once 'footer.php'; ?>
</main>

<!-- Styles and Scripts -->
<style> /* Add/reuse styles from edit_club.php/edit_user.php */ </style>
<script> /* Add/reuse scripts */ </script>


</body>
</html>