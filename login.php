<?php
session_start();

// Include database connection and potentially user functions
require_once 'config/database.php';
// Assuming loginUser() and registerUser() might be defined here or in database.php
include 'test2.php'; // Keep this if it defines loginUser/registerUser, otherwise remove/adjust

// Initialize error and success messages
$error = '';
$success = ''; // Although not used for success messages in the original first file's processing, kept for potential future use.

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
        $error = 'Veuillez entrer votre email et mot de passe.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $error = 'Format d\'email invalide.';
    } else {
        // Assuming loginUser function exists and returns true on success, false otherwise
        if (function_exists('loginUser') && loginUser($email, $password)) {
            // Redirect to dashboard on successful login
            // Make sure no HTML is output before this header call
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            $error = 'Email ou mot de passe invalide.';
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
        $error = 'Tous les champs sont requis.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format d\'email invalide.';
    } elseif (strlen($password) < 6) { // Example: Add minimum password length validation
         $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
         // Assuming registerUser function exists and returns true on success, false otherwise (e.g., user exists)
        if (function_exists('registerUser') && registerUser($username, $email, $password)) {
            // Auto-login after successful registration
            // Assuming loginUser function exists
             if (function_exists('loginUser') && loginUser($email, $password)) {
                 header('Location: index.php?page=dashboard');
                 exit;
             } else {
                 // Handle case where auto-login fails (should be rare if registration succeeded)
                 $success = 'Inscription réussie ! Veuillez vous connecter.';
                 // Force showing login form after successful registration if auto-login fails
                 $showLoginForm = true;
             }
        } else {
            // Check if the function exists before blaming it for the error
            if (!function_exists('registerUser')) {
                 $error = 'Erreur : La fonction d\'inscription n\'est pas disponible.';
            } else {
                $error = 'Ce nom d\'utilisateur ou cet email existe déjà.';
            }
        }
    }
    // Ensure we show the registration form if registration fails
    $showLoginForm = false;
}

// --- HTML Output Starts Here ---
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $showLoginForm ? 'Se connecter' : 'S\'inscrire'; ?> - ClubMantra</title>
    <link rel="stylesheet" href="styles.css"> <!-- Make sure this path is correct -->
    <style>

        /* Basic styles for error/success messages - adapt or move to styles.css */
        .input-wrapper input[type="email"],
.input-wrapper input[type="password"],
        .input-wrapper input[type="text"] {
    width: 100%;
    padding: 12px 15px 12px 40px; /* Padding: top/bottom, right, left (for icon) */
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1em;
    transition: border-color 0.2s ease;
}
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
         /* Style to hide the eye-off icon initially */
        .password-toggle .eye-off-icon {
            display: none;
        }
        .password-toggle.active .eye-icon {
            display: none;
        }
        .password-toggle.active .eye-off-icon {
            display: inline; /* Or block, depending on SVG */
        }
        .login-container{
            margin-top: 50px;
            margin-bottom: 100px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Decorative Panel -->
        <div class="left-panel">
            <div class="left-content">
                <span class="brand-tag">ClubMantra</span>
                <h2><?php echo $showLoginForm ? 'Bienvenue à nouveau' : 'Rejoignez ClubMantra'; ?></h2>
                <p><?php echo $showLoginForm
                    ? 'Connectez-vous pour continuer votre expérience avec les clubs universitaires.'
                    : 'Créez un compte pour découvrir et rejoindre les clubs, et rester connecté.'; ?>
                </p>
                 <!-- Optional: Add feature cards like in the first file if desired -->
                 <!--
                 <div class="auth-features"> ... </div>
                 -->
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="right-panel">
            <div class="form-container">
                <a href="/index.php" class="back-link"> <!-- Adjust link as needed -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Retour à l'accueil
                </a>

                <h2><?php echo $showLoginForm ? 'Se connecter' : 'Créer un compte'; ?></h2>
                <p class="subtitle">
                    <?php echo $showLoginForm ? 'Entrez vos identifiants pour accéder à votre compte' : 'Remplissez les informations ci-dessous pour vous inscrire'; ?>
                </p>

                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); // Sanitize output ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); // Sanitize output ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?><?php echo !$showLoginForm ? '?action=register' : ''; ?>">

                    <!-- Username field - only for registration -->
                    <?php if (!$showLoginForm): ?>
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur</label>
                            <div class="input-wrapper">
                                <!-- User SVG Icon -->
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <input type="text" id="username" name="username" placeholder="Choisissez un nom d'utilisateur" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Email field - for both forms -->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <!-- Email SVG Icon -->
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <input type="email" id="email" name="email" placeholder="votre.email@universite.fr" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Password field - for both forms -->
                    <div class="form-group">
                        <div class="label-wrapper">
                            <label for="password">Mot de passe</label>
                            <?php if ($showLoginForm): ?>
                                <a href="/forgot-password.php" class="forgot-password">Mot de passe oublié?</a> <!-- Adjust link -->
                            <?php endif; ?>
                        </div>
                        <div class="input-wrapper">
                            <!-- Lock SVG Icon -->
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                            <!-- Password Toggle Button -->
                            <button type="button" class="password-toggle" data-target="password">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                         <?php if (!$showLoginForm): ?>
                             <small style="font-size: 0.8em; color: #6c757d;">Doit contenir au moins 6 caractères.</small>
                         <?php endif; ?>
                    </div>

                    <!-- Confirm password field - only for registration -->
                    <?php if (!$showLoginForm): ?>
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <div class="input-wrapper">
                                <!-- Lock SVG Icon -->
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Retapez votre mot de passe" required>
                                <!-- Password Toggle Button -->
                                 <button type="button" class="password-toggle" data-target="confirm_password">
                                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    <svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="<?php echo $showLoginForm ? 'login' : 'register'; ?>" class="btnlogin btnlogin-primary">
                        <?php echo $showLoginForm ? 'Se connecter' : 'Créer un compte'; ?>
                    </button>
                </form>

                <div class="signup-link">
                    <?php echo $showLoginForm ? 'Vous n\'avez pas de compte?' : 'Vous avez déjà un compte?'; ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?><?php echo $showLoginForm ? '?action=register' : ''; ?>">
                         <?php echo $showLoginForm ? 'S\'inscrire' : 'Se connecter'; ?>
                    </a>
                </div>

                <!-- Optional: Keep or remove Google Login / Separator -->
                
                <div class="separator">
                    <span>ou continuer avec</span>
                </div>

                <button type="button" class="btnlogin btnlogin-google">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.47 0 6.5 1.19 8.89 3.29l6.52-6.52C35.51 2.83 29.99 1 24 1 14.41 1 6.48 6.89 3.33 15.19l7.78 6.02C12.59 14.64 17.93 9.5 24 9.5z"></path><path fill="#4285F4" d="M46.16 25.36c0-1.73-.15-3.4-.44-5.01H24v9.5h12.45c-.54 3.08-2.19 5.69-4.71 7.48l7.58 5.86C43.32 39.21 46.16 32.97 46.16 25.36z"></path><path fill="#34A853" d="M11.11 21.21C10.13 18.21 10.13 14.79 11.11 11.79l-7.78-6.02C1.16 10.77 0 15.71 0 21c0 5.29 1.16 10.23 3.33 14.81l7.78-6.02c-.98-3-.98-6.42 0-9.42z"></path><path fill="#FBBC05" d="M24 38.5c5.07 0 9.38-1.73 12.45-4.67l-7.58-5.86c-1.58 1.07-3.6 1.7-5.87 1.7-6.07 0-11.41-5.14-12.89-11.68L3.33 32.81C6.48 41.11 14.41 47 24 47c3.99 0 7.58-.93 10.38-2.52l-.38-.28c-2.52 1.79-5.79 2.8-9.01 2.8z"></path><path fill="none" d="M0 0h48v48H0z"></path></svg>
                    Google
                </button>
                
            </div>
        </div>
    </div>

    <!-- Make sure these script paths are correct -->
    <!-- <script src="script.js"></script> -->
    <script>
        // Simple password toggle script (adapt if you have a more complex one in scripts.js)
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggles = document.querySelectorAll('.password-toggle');

            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetInputId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetInputId);
                    if (passwordInput) {
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            this.classList.add('active'); // Add class to show eye-off
                        } else {
                            passwordInput.type = 'password';
                            this.classList.remove('active'); // Remove class to show eye
                        }
                    }
                });
            });
        });
    </script>
     <script src="scripts.js"></script>
     <script src="script.js"></script>

</body>
</html>