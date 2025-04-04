
<?php
// Initialize error and success messages
$error = '';
$success = '';

// Process login form submission
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (loginUser($email, $password)) {
        // Redirect to dashboard on successful login
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

// Process registration form submission
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        if (registerUser($username, $email, $password)) {
            // Auto-login after successful registration
            loginUser($email, $password);
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            $error = 'Username or email already exists.';
        }
    }
}

// Determine which form to show (login or register)
$showLoginForm = true;
if (isset($_GET['action']) && $_GET['action'] === 'register') {
    $showLoginForm = false;
}
?>

<div class="auth-page">
    <div class="auth-container">
        <!-- Left column - information -->
        <div class="auth-info fade-up">
            <h1 class="auth-title">
                <?php echo $showLoginForm ? 'Welcome back to ClubNest' : 'Join ClubNest today'; ?>
            </h1>
            <p class="auth-description">
                <?php echo $showLoginForm 
                    ? 'Sign in to continue your journey with university clubs, manage membership, and stay connected with your community.'
                    : 'Create an account to discover and join university clubs, connect with like-minded students, and enhance your campus experience.'; ?>
            </p>
            
            <div class="auth-features">
                <div class="auth-feature-card">
                    <h3 class="auth-feature-title">For Students</h3>
                    <p class="auth-feature-text">
                        Browse and join clubs, receive notifications about events
                    </p>
                </div>
                <div class="auth-feature-card">
                    <h3 class="auth-feature-title">For Club Leaders</h3>
                    <p class="auth-feature-text">
                        Manage club members, send notifications, organize events
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Right column - form -->
        <div class="auth-form-container fade-up">
            <div class="auth-form-card">
                <div class="auth-form-header">
                    <h2 class="auth-form-title">
                        <?php echo $showLoginForm ? 'Welcome Back' : 'Create Account'; ?>
                    </h2>
                    <p class="auth-form-subtitle">
                        <?php echo $showLoginForm 
                            ? 'Sign in to access your ClubNest account' 
                            : 'Join ClubNest to discover and manage university clubs'; ?>
                    </p>
                </div>
                
                <?php if ($error): ?>
                    <div class="auth-alert auth-alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="auth-alert auth-alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php?page=auth<?php echo !$showLoginForm ? '&action=register' : ''; ?>" class="auth-form">
                    <!-- Registration form fields -->
                    <?php if (!$showLoginForm): ?>
                        <div class="auth-input-group">
                            <div class="auth-input-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                placeholder="Username" 
                                class="auth-input"
                                required
                            >
                        </div>
                    <?php endif; ?>
                    
                    <!-- Email field - for both forms -->
                    <div class="auth-input-group">
                        <div class="auth-input-icon">
                            <i class="fas fa-at"></i>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Email address" 
                            class="auth-input"
                            required
                        >
                    </div>
                    
                    <!-- Password field - for both forms -->
                    <div class="auth-input-group">
                        <div class="auth-input-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Password" 
                            class="auth-input"
                            required
                        >
                        <button 
                            type="button" 
                            class="auth-input-toggle password-toggle"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <!-- Confirm password field - only for registration -->
                    <?php if (!$showLoginForm): ?>
                        <div class="auth-input-group">
                            <div class="auth-input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Confirm Password" 
                                class="auth-input"
                                required
                            >
                            <button 
                                type="button" 
                                class="auth-input-toggle password-toggle"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Forgot password link - only for login -->
                    <?php if ($showLoginForm): ?>
                        <div class="auth-forgot-password">
                            <a href="#" class="auth-forgot-link">
                                Forgot password?
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Submit button -->
                    <button 
                        type="submit" 
                        name="<?php echo $showLoginForm ? 'login' : 'register'; ?>" 
                        class="btn-primary auth-submit-button"
                    >
                        <?php echo $showLoginForm ? 'Sign In' : 'Create Account'; ?>
                    </button>
                </form>
                
                <div class="auth-alternate">
                    <p>
                        <?php echo $showLoginForm ? "Don't have an account? " : "Already have an account? "; ?>
                        <a 
                            href="index.php?page=auth<?php echo $showLoginForm ? '&action=register' : ''; ?>" 
                            class="auth-alternate-link"
                        >
                            <?php echo $showLoginForm ? 'Sign Up' : 'Sign In'; ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
