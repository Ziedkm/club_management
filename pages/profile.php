
<?php
// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=auth');
    exit;
}

// Get current user data
$userId = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];
$email = $_SESSION['user']['email'];
$userRole = $_SESSION['user']['role'];

// Initialize messages
$error = '';
$success = '';

// Process form submission
if (isset($_POST['update_profile'])) {
    $newUsername = $_POST['username'];
    $newEmail = $_POST['email'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate current password if trying to change password
    if (!empty($newPassword)) {
        // Get current password from database
        $query = "SELECT password FROM users WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($currentPassword !== $user['password']) {
            $error = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        }
    }
    
    // Check if username already exists
    if (!$error && $newUsername !== $username) {
        $query = "SELECT * FROM users WHERE username = :username AND id != :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $newUsername);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = 'Username already taken.';
        }
    }
    
    // Check if email already exists
    if (!$error && $newEmail !== $email) {
        $query = "SELECT * FROM users WHERE email = :email AND id != :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $newEmail);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = 'Email already taken.';
        }
    }
    
    // Update profile if no errors
    if (!$error) {
        $query = "UPDATE users SET username = :username, email = :email";
        $params = [
            ':username' => $newUsername,
            ':email' => $newEmail,
            ':id' => $userId
        ];
        
        // Add password to update if provided
        if (!empty($newPassword)) {
            $query .= ", password = :password";
            $params[':password'] = $newPassword;
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($query);
        
        if ($stmt->execute($params)) {
            // Update session data
            $_SESSION['user']['username'] = $newUsername;
            $_SESSION['user']['email'] = $newEmail;
            
            $success = 'Profile updated successfully.';
            
            // Update local variables
            $username = $newUsername;
            $email = $newEmail;
        } else {
            $error = 'Failed to update profile.';
        }
    }
}
?>

<div class="pt-10 pb-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="mb-8 fade-up">
            <h1 class="text-4xl font-bold mb-2">My Profile</h1>
            <p class="text-lg text-gray-600">
                View and update your account information
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Account information form -->
            <div class="md:col-span-2">
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up">
                    <h2 class="text-2xl font-semibold mb-2">Account Information</h2>
                    <p class="text-gray-600 mb-6">
                        Update your personal information and password
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="space-y-6">
                        <!-- Username field -->
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    value="<?php echo $username; ?>"
                                    class="form-input pl-10 py-3 w-full rounded-md"
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Email field -->
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-at text-gray-400"></i>
                                </div>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo $email; ?>"
                                    class="form-input pl-10 py-3 w-full rounded-md"
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Current password field -->
                        <div class="form-group">
                            <label for="current_password" class="form-label">
                                Current Password (required to change password)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    class="form-input pl-10 pr-10 py-3 w-full rounded-md"
                                >
                                <button 
                                    type="button" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 password-toggle"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- New password field -->
                        <div class="form-group">
                            <label for="new_password" class="form-label">
                                New Password (leave blank to keep current)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="form-input pl-10 pr-10 py-3 w-full rounded-md"
                                >
                                <button 
                                    type="button" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 password-toggle"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Confirm new password field -->
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                Confirm New Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-input pl-10 pr-10 py-3 w-full rounded-md"
                                >
                                <button 
                                    type="button" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 password-toggle"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Submit button -->
                        <button 
                            type="submit" 
                            name="update_profile" 
                            class="btn-primary flex items-center py-3 px-4"
                        >
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Account summary -->
            <div>
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 100ms;">
                    <h2 class="text-2xl font-semibold mb-2">Account Summary</h2>
                    <p class="text-gray-600 mb-6">
                        Your current role and account details
                    </p>
                    
                    <!-- User avatar and name -->
                    <div class="flex flex-col items-center text-center mb-6">
                        <div class="h-20 w-20 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold mb-4">
                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                        </div>
                        <h3 class="text-xl font-semibold mb-1"><?php echo $username; ?></h3>
                        <p class="text-gray-600"><?php echo $email; ?></p>
                    </div>
                    
                    <!-- Role information -->
                    <div class="bg-gray-100 p-4 rounded-lg mb-6">
                        <h4 class="font-medium mb-1">
                            <?php 
                                switch ($userRole) {
                                    case 'admin':
                                        echo 'Club Administrator';
                                        break;
                                    case 'club_leader':
                                        echo 'Club Leader';
                                        break;
                                    default:
                                        echo 'Student';
                                }
                            ?>
                        </h4>
                        <p class="text-sm text-gray-600">
                            <?php 
                                switch ($userRole) {
                                    case 'admin':
                                        echo 'You have full access to manage all university clubs and their activities.';
                                        break;
                                    case 'club_leader':
                                        echo 'You can manage your club members, post announcements, and organize events.';
                                        break;
                                    default:
                                        echo 'You can join clubs, participate in events, and receive club notifications.';
                                }
                            ?>
                        </p>
                    </div>
                    
                    <!-- Account actions -->
                    <div>
                        <a 
                            href="actions/logout.php" 
                            class="block border border-red-300 text-red-700 hover:bg-red-50 text-center py-2 px-4 rounded-md transition-colors"
                        >
                            Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
