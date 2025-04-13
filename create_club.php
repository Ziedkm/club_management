<?php
session_start();

// --- PHP Prerequisites & Logic ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php';

// --- Authentication & Authorization ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['error_message'] = "Please log in to create a club.";
    header('Location: login.php?redirect=create_club.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role']; // Should be 'student' or 'admin' at this point

// Redirect club leaders away (they cannot create new clubs)
if ($userRole === 'club_leader') {
    $_SESSION['error_message'] = "Club leaders cannot create new clubs.";
    header('Location: dashboard.php');
    exit;
}

// --- Define available categories ---
// You could fetch these from a dedicated `categories` table in the future
$availableCategories = ['Academic', 'Arts & Culture', 'Community Service', 'Recreation', 'Sports', 'Technology', 'Social', 'Other'];

// Check if student already leads a club (only if the user is a student)
$alreadyLeadsClub = false;
if ($userRole === 'student') {
    try {
        $stmtCheck = $pdo->prepare("SELECT 1 FROM club_members WHERE user_id = :user_id AND role = 'leader' LIMIT 1");
        $stmtCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            $alreadyLeadsClub = true;
        }
    } catch (Exception $e) {
        error_log("Error checking if student leads club: " . $e->getMessage());
    }
}

// If student already leads, set a page error (form will be hidden)
if ($alreadyLeadsClub) {
    $pageError = "You are already leading a club. Students can only lead one club.";
}

// --- Initialize Variables ---
$clubName = '';
$clubDescription = '';
$clubCategory = ''; // Will hold the selected category
$clubSchedule = '';
$formError = null;
$formSuccess = null; // Usually handled by redirect
// Initialize pageError if not already set by leadership check
$pageError = $pageError ?? null; // Ensure pageError exists


// --- Handle Form Submission (POST Request) ---
// Allow submission only if no page blocking errors exist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pageError) {

    // Retrieve and sanitize input
    $clubName = trim($_POST['club_name'] ?? '');
    $clubDescription = trim($_POST['club_description'] ?? ''); // Description is now required
    $clubCategory = trim($_POST['club_category'] ?? '');
    $clubSchedule = trim($_POST['club_schedule'] ?? '');

    // --- Validation ---
    if (empty($clubName) || empty($clubCategory) || empty($clubDescription)) { // Added description check
        $formError = "Club Name, Category, and Description are required.";
    } elseif (!in_array($clubCategory, $availableCategories)) { // Validate against allowed categories
        $formError = "Invalid category selected.";
    } elseif (strlen($clubName) > 100) { $formError = "Club Name too long."; }
    elseif (strlen($clubCategory) > 50) { $formError = "Category too long."; }
    // Add description length validation if desired

    // --- Process if No Errors ---
    if (empty($formError)) {
        try {
            // Determine status based on user role
            $clubStatus = ($userRole === 'admin') ? 'active' : 'pending'; // Active for admin, pending for student

            // Check if club name already exists (pending or active)
            $stmtCheckName = $pdo->prepare("SELECT id, status FROM clubs WHERE name = :name AND status != 'rejected' LIMIT 1");
            $stmtCheckName->bindParam(':name', $clubName, PDO::PARAM_STR);
            $stmtCheckName->execute();
            $existingClub = $stmtCheckName->fetch(PDO::FETCH_ASSOC);

            if ($existingClub) {
                $formError = "A club with this name already exists";
                $formError .= ($existingClub['status'] === 'pending') ? " and is pending approval." : ".";
            } else {
                // Begin Transaction
                $pdo->beginTransaction();

                // Insert club
                $sqlInsertClub = "INSERT INTO clubs (name, description, category, meeting_schedule, status, proposed_by_user_id)
                                  VALUES (:name, :description, :category, :schedule, :status, :user_id)";
                $stmtInsertClub = $pdo->prepare($sqlInsertClub);
                $stmtInsertClub->bindParam(':name', $clubName);
                $stmtInsertClub->bindParam(':description', $clubDescription);
                $stmtInsertClub->bindParam(':category', $clubCategory);
                $stmtInsertClub->bindParam(':schedule', $clubSchedule);
                $stmtInsertClub->bindParam(':status', $clubStatus); // Use determined status
                $stmtInsertClub->bindParam(':user_id', $userId, PDO::PARAM_INT);

                if ($stmtInsertClub->execute()) {
                    $newClubId = $pdo->lastInsertId();

                    // If admin created it, make them the leader immediately
                    if ($userRole === 'admin') {
                        $sqlMakeLeader = "INSERT INTO club_members (user_id, club_id, role) VALUES (:user_id, :club_id, 'leader')";
                        $stmtMakeLeader = $pdo->prepare($sqlMakeLeader);
                        $stmtMakeLeader->bindParam(':user_id', $userId, PDO::PARAM_INT);
                        $stmtMakeLeader->bindParam(':club_id', $newClubId, PDO::PARAM_INT);

                        if (!$stmtMakeLeader->execute()) {
                            // Problem making admin the leader, roll back
                            $pdo->rollBack();
                            throw new Exception("Failed to assign admin as leader. Club creation rolled back. Error: " . implode(", ", $stmtMakeLeader->errorInfo()));
                        }
                        $successMessage = "Club '" . htmlspecialchars($clubName) . "' created successfully and is now active!";
                    } else {
                        // Student submitted, pending approval
                        $successMessage = "Club proposal for '" . htmlspecialchars($clubName) . "' submitted successfully for admin review!";
                    }

                    // Commit Transaction
                    $pdo->commit();

                    // Set success message and redirect
                    $_SESSION['success_message'] = $successMessage;
                    // Redirect based on role? Maybe dashboard for both?
                    header('Location: dashboard.php'); // Or profile.php or clubs.php
                    exit;

                } else {
                    $pdo->rollBack(); // Roll back if club insert failed
                    $formError = "Failed to submit club proposal. Please try again.";
                    error_log("Club creation insert failed for user {$userId}. PDO Error: " . implode(", ", $stmtInsertClub->errorInfo()));
                }
            }
        } catch (Exception $e) {
            // Roll back transaction if it's active
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $formError = "An error occurred while submitting the proposal.";
            error_log("Club creation exception for user {$userId}. Error: " . $e->getMessage());
        }
    }
}

// --- Get Session Flash Messages ---
// (Same as before)
if (isset($_SESSION['error_message'])) { $pageError = $_SESSION['error_message']; unset($_SESSION['error_message']); }
if (isset($_SESSION['success_message'])) { $formSuccess = $_SESSION['success_message']; unset($_SESSION['success_message']); }

// --- NOW START HTML OUTPUT ---
include_once 'header.php'; 
?>

<!-- Main Content Area -->
<main class="main-content create-club-container">
    <div class="create-club-wrapper card">

        <!-- Page Header -->
        <div class="section-header">
            <h1 class="section-title">
                <?php echo ($userRole === 'admin') ? 'Create New Club' : 'Propose a New Club'; ?>
            </h1>
            <p class="section-subtitle">
                <?php echo ($userRole === 'admin') ? 'Fill out the details below to create an active club immediately.' : 'Fill out the details below. Your proposal will be sent for admin review.'; ?>
            </p>
        </div>

         <!-- General Page Load Error -->
         <?php if ($pageError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?></div><?php endif; ?>
         <!-- Form Submission Feedback -->
        <?php if ($formError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($formError); ?></div><?php endif; ?>
         <?php if ($formSuccess): ?> <div class="message success-message" role="alert"><?php echo htmlspecialchars($formSuccess); ?></div><?php endif; ?>

        <!-- Club Creation Form -->
        <?php if (!$pageError): // Show form if no blocking errors (like student already leading) ?>
            <form method="POST" action="create_club.php" class="create-form">
                <!-- Club Name -->
                <div class="form-group">
                    <label for="club_name">Club Name <span class="required">*</span></label>
                    <input type="text" id="club_name" name="club_name" class="form-input"
                           value="<?php echo htmlspecialchars($clubName); ?>" maxlength="100" required style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;">
                </div>

                <!-- Club Category Dropdown -->
                <div class="form-group">
                    <label for="club_category">Category <span class="required">*</span></label>
                    <select id="club_category" name="club_category" class="form-input" required style="color:black;width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;">
                        <option value="" disabled <?php echo empty($clubCategory) ? 'selected' : ''; ?>>-- Select a Category --</option>
                        <?php foreach ($availableCategories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($clubCategory === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                 <!-- Club Description (Now Required) -->
                 <div class="form-group">
                    <label for="club_description">Description <span class="required">*</span></label>
                    <textarea id="club_description" name="club_description" class="form-textarea"
                              rows="5" placeholder="Describe the purpose and activities of the club..."
                              required style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;"><?php echo htmlspecialchars($clubDescription); ?></textarea>
                </div>

                <!-- Meeting Schedule -->
                 <div class="form-group">
                    <label for="club_schedule">Meeting Schedule (Optional)</label>
                    <input type="text" id="club_schedule" name="club_schedule" class="form-input"
                           value="<?php echo htmlspecialchars($clubSchedule); ?>" maxlength="100"
                           placeholder="e.g., Tuesdays at 5 PM, Bi-weekly Fridays" style="width: 100%; border: 2px solid var(--border-color);border-radius: 0.5rem;padding:10px;">
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo ($userRole === 'admin') ? 'Create Active Club' : 'Submit Proposal'; ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>

    </div> <!-- End Wrapper -->
</main>

<!-- Styles and Scripts remain the same -->
<style> /* Paste styles from previous answer */ </style>
<script> /* Paste scripts from previous answer if any */ </script>

</body>
</html>