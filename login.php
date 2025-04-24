<?php
session_start(); // Ensure session is started

// Include database connection and potentially user functions loginUser() and registerUser() might be defined here
require_once 'config/database.php';
// Include header AFTER potential redirects in POST handling below
// include_once 'header.php'; // Moved this down

// Initialize error and success messages
$error = '';
$success = '';

// Determine which form to show (login or register)
// Default to login unless 'action=register' is in the URL
$showLoginForm = true;
if (isset($_GET['action']) && $_GET['action'] === 'register') {
    $showLoginForm = false;
}

// --- Process Form Submissions ---

// Process login form submission
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation before calling loginUser
    if (empty($email) || empty($password)) {
        // $error = 'Veuillez entrer votre email et mot de passe.'; // French
        $error = 'Please enter your email and password.'; // English
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         // $error = 'Format d\'email invalide.'; // French
         $error = 'Invalid email format.'; // English
    } else {
        // Assuming loginUser function exists and returns true on success, false otherwise
        if (function_exists('loginUser') && loginUser($email, $password)) {
            // Redirect to home page on successful login (or dashboard)
            header('Location: home.php'); // Changed redirect destination
            exit;
        } else {
            // $error = 'Email ou mot de passe invalide.'; // French
            $error = 'Invalid email or password.'; // English
        }
    }
    // Ensure we show the login form if login fails
    $showLoginForm = true;
}

// Process registration form submission
if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        // $error = 'Tous les champs sont requis.'; // French
        $error = 'All fields are required.'; // English
    } elseif ($password !== $confirm_password) {
        // $error = 'Les mots de passe ne correspondent pas.'; // French
        $error = 'Passwords do not match.'; // English
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // $error = 'Format d\'email invalide.'; // French
        $error = 'Invalid email format.'; // English
    } elseif (strlen($password) < 6) { // Example: Add minimum password length validation
         // $error = 'Le mot de passe doit contenir au moins 6 caractères.'; // French
         $error = 'Password must be at least 6 characters long.'; // English
    } else {
         // Assuming registerUser function exists and returns true on success, false otherwise (e.g., user exists)
        if (function_exists('registerUser') && registerUser($username, $email, $password)) {
            // Auto-login after successful registration
             if (function_exists('loginUser') && loginUser($email, $password)) {
                 // Redirect to dashboard or home after successful registration + login
                 header('Location: home.php'); // Changed redirect destination
                 exit;
             } else {
                 // Handle case where auto-login fails (should be rare)
                 // $success = 'Inscription réussie ! Veuillez vous connecter.'; // French
                 $success = 'Registration successful! Please log in.'; // English
                 $showLoginForm = true; // Force showing login form
             }
        } else {
            // Check if the function exists before blaming it for the error
            if (!function_exists('registerUser')) {
                 // $error = 'Erreur : La fonction d\'inscription n\'est pas disponible.'; // French
                 $error = 'Error: Registration function is not available.'; // English
                 // Alternative user-friendly message:
                 // $error = 'Registration failed. Please try again later.';
            } else {
                // $error = 'Ce nom d\'utilisateur ou cet email existe déjà.'; // French
                $error = 'This username or email already exists.'; // English
            }
        }
    }
    // Ensure we show the registration form if registration fails
    $showLoginForm = false;
}

// --- NOW Start HTML Output (Include header here) ---
include_once 'header.php';
?>

<main class="main-content">
    <div class="login-container">
        <!-- Left Decorative Panel -->
        <div class="left-panel">
            <div class="left-content">
                <span class="brand-tag">ClubMantra</span> <!-- Keep Brand Name -->
                <!-- English Text based on form state -->
                <h2><?php echo $showLoginForm ? 'Welcome Back' : 'Join ClubMantra'; ?></h2>
                <p><?php echo $showLoginForm
                    ? 'Log in to continue your experience with university clubs.' // English
                    : 'Create an account to discover and join clubs, and stay connected.'; ?> <!-- English -->
                </p>
                 <div class="auth-features">
                    <!-- English Features -->
                    <div class="feature-card">
                        <p>Join Clubs</p>
                    </div>
                    <div class="feature-card">
                        <p>Upcoming Events</p>
                    </div>
                    <div class="feature-card">
                        <p>Networking</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="right-panel">
            <div class="form-container">
                <!-- English Back Link -->
                <a href="/cm/home.php" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Home
                </a>

                <!-- English Titles based on form state -->
                <h2><?php echo $showLoginForm ? 'Log In' : 'Create Account'; ?></h2>
                <p class="subtitle">
                    <?php echo $showLoginForm ? 'Enter your credentials to access your account' : 'Fill in the information below to register'; ?>
                </p>

                <!-- Error/Success Messages (variables already translated) -->
                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Form posts back to self, action determines registration mode -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?><?php echo !$showLoginForm ? '?action=register' : ''; ?>">

                    <!-- Username field - only for registration -->
                    <?php if (!$showLoginForm): ?>
                        <div class="form-group">
                            <!-- English Label -->
                            <label for="username">Username</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <!-- English Placeholder -->
                                <input type="text" id="username" name="username" placeholder="Choose a username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Email field - for both forms -->
                    <div class="form-group">
                        <!-- English Label -->
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <!-- English Placeholder -->
                            <input type="email" id="email" name="email" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Password field - for both forms -->
                    <div class="form-group">
                        <div class="label-wrapper">
                            <!-- English Label -->
                            <label for="password">Password</label>
                            <!-- <?php if ($showLoginForm): ?> -->
                                <!-- English Link -->
                               <!-- <a href="/forgot-password.php" class="forgot-password">Forgot password?</a> -->
                            <!-- <?php endif; ?> -->
                        </div>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <!-- English Placeholder -->
                            <input type="password" id="password" name="password" placeholder="Your password" required>
                            <button type="button" class="password-toggle" data-target="password">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                         <?php if (!$showLoginForm): ?>
                             <!-- English Hint -->
                             <small style="font-size: 0.8em; color: #6c757d;">Must be at least 6 characters.</small>
                         <?php endif; ?>
                    </div>

                    <!-- Confirm password field - only for registration -->
                    <?php if (!$showLoginForm): ?>
                        <div class="form-group">
                             <!-- English Label -->
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                <!-- English Placeholder -->
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-type your password" required>
                                <button type="button" class="password-toggle" data-target="confirm_password">
                                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Submit button -->
                    <button type="submit" name="<?php echo $showLoginForm ? 'login' : 'register'; ?>" class="btnlogin btnlogin-primary">
                        <?php echo $showLoginForm ? 'Log In' : 'Create Account'; // English ?>
                    </button>
                </form>

                <!-- Alternate Action Link -->
                <div class="signup-link">
                    <!-- English Text -->
                    <?php echo $showLoginForm ? 'Don\'t have an account?' : 'Already have an account?'; ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?><?php echo $showLoginForm ? '?action=register' : ''; ?>">
                         <?php echo $showLoginForm ? 'Sign Up' : 'Log In'; // English ?>
                    </a>
                </div>

                 <!-- Separator -->
                <div class="separator">
                    <!-- English Text -->
                    <span>or continue with</span>
                </div>

                <!-- Google Button -->
                <button type="button" class="btnlogin btnlogin-google">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.47 0 6.5 1.19 8.89 3.29l6.52-6.52C35.51 2.83 29.99 1 24 1 14.41 1 6.48 6.89 3.33 15.19l7.78 6.02C12.59 14.64 17.93 9.5 24 9.5z"></path><path fill="#4285F4" d="M46.16 25.36c0-1.73-.15-3.4-.44-5.01H24v9.5h12.45c-.54 3.08-2.19 5.69-4.71 7.48l7.58 5.86C43.32 39.21 46.16 32.97 46.16 25.36z"></path><path fill="#34A853" d="M11.11 21.21C10.13 18.21 10.13 14.79 11.11 11.79l-7.78-6.02C1.16 10.77 0 15.71 0 21c0 5.29 1.16 10.23 3.33 14.81l7.78-6.02c-.98-3-.98-6.42 0-9.42z"></path><path fill="#FBBC05" d="M24 38.5c5.07 0 9.38-1.73 12.45-4.67l-7.58-5.86c-1.58 1.07-3.6 1.7-5.87 1.7-6.07 0-11.41-5.14-12.89-11.68L3.33 32.81C6.48 41.11 14.41 47 24 47c3.99 0 7.58-.93 10.38-2.52l-.38-.28c-2.52 1.79-5.79 2.8-9.01 2.8z"></path><path fill="none" d="M0 0h48v48H0z"></path></svg>
                    Google
                </button>

            </div>
        </div>
    </div>
    

    <!-- Password Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggles = document.querySelectorAll('.password-toggle');
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetInputId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetInputId);
                    if (passwordInput) {
                        const eyeIcon = this.querySelector('.eye-icon');
                        const eyeOffIcon = this.querySelector('.eye-off-icon');
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            if (eyeIcon) eyeIcon.style.display = 'none';
                            if (eyeOffIcon) eyeOffIcon.style.display = 'inline';
                            this.classList.add('active');
                        } else {
                            passwordInput.type = 'password';
                            if (eyeIcon) eyeIcon.style.display = 'inline';
                            if (eyeOffIcon) eyeOffIcon.style.display = 'none';
                            this.classList.remove('active');
                        }
                    }
                });
                // Initialize icon state
                 const targetInputId = toggle.getAttribute('data-target');
                 const passwordInput = document.getElementById(targetInputId);
                 const eyeIcon = toggle.querySelector('.eye-icon');
                 const eyeOffIcon = toggle.querySelector('.eye-off-icon');
                 if(passwordInput && eyeIcon && eyeOffIcon) {
                     if (passwordInput.type === 'password') { eyeIcon.style.display='inline'; eyeOffIcon.style.display='none';}
                     else { eyeIcon.style.display='none'; eyeOffIcon.style.display='inline'; }
                 }
            });
        });
    </script>
     <!-- Include other scripts if needed -->
     <!-- <script src="script.js"></script> -->
     
</main>
<!-- Include footer AFTER main content -->

</body>
</html>