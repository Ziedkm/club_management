<?php
session_start();

// Include database connection
require_once 'config/database.php';



include 'test2.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se connecter - ClubMantra</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Decorative Panel -->
        <div class="left-panel">
            <div class="left-content">
                <span class="brand-tag">ClubMantra</span>
                <h2>Bienvenue à nouveau</h2>
                <p>Connectez-vous pour continuer votre expérience avec les clubs universitaires.</p>
            </div>
        </div>

        <!-- Right Login Form Panel -->
        <div class="right-panel">
            <div class="form-container">
                <a href="/index.php" class="back-link"> <!-- Adjust link as needed -->
                    <!-- Back Arrow SVG Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Retour à l'accueil
                </a>

                <h2>Se connecter</h2>
                <p class="subtitle">Entrez vos identifiants pour accéder à votre compte</p>

                <?php
                // Optional: Display error messages from PHP backend if needed
                // if (isset($_GET['error'])) {
                //     echo '<p class="error-message">'.htmlspecialchars($_GET['error']).'</p>';
                // }
                ?>

                <form action="login_process.php" method="POST"> <!-- Point to your PHP processing script -->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <!-- Email SVG Icon -->
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <input type="email" id="email" name="email" placeholder="votre.email@universite.fr" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-wrapper">
                            <label for="password">Mot de passe</label>
                            <a href="/forgot-password.php" class="forgot-password">Mot de passe oublié?</a> <!-- Adjust link -->
                        </div>
                        <div class="input-wrapper">
                            <!-- Lock SVG Icon -->
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                            <!-- Eye SVG Icon (Toggle) -->
                            <button type="button" id="togglePassword" class="password-toggle">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btnlogin btnlogin-primary">Se connecter</button>
                </form>

                <div class="signup-link">
                    Vous n'avez pas de compte? <a href="/signup.php">S'inscrire</a> <!-- Adjust link -->
                </div>

                <div class="separator">
                    <span>ou continuer avec</span>
                </div>

                <button type="button" class="btnlogin btnlogin-google">
                    <!-- Google Logo SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.47 0 6.5 1.19 8.89 3.29l6.52-6.52C35.51 2.83 29.99 1 24 1 14.41 1 6.48 6.89 3.33 15.19l7.78 6.02C12.59 14.64 17.93 9.5 24 9.5z"></path><path fill="#4285F4" d="M46.16 25.36c0-1.73-.15-3.4-.44-5.01H24v9.5h12.45c-.54 3.08-2.19 5.69-4.71 7.48l7.58 5.86C43.32 39.21 46.16 32.97 46.16 25.36z"></path><path fill="#34A853" d="M11.11 21.21C10.13 18.21 10.13 14.79 11.11 11.79l-7.78-6.02C1.16 10.77 0 15.71 0 21c0 5.29 1.16 10.23 3.33 14.81l7.78-6.02c-.98-3-.98-6.42 0-9.42z"></path><path fill="#FBBC05" d="M24 38.5c5.07 0 9.38-1.73 12.45-4.67l-7.58-5.86c-1.58 1.07-3.6 1.7-5.87 1.7-6.07 0-11.41-5.14-12.89-11.68L3.33 32.81C6.48 41.11 14.41 47 24 47c3.99 0 7.58-.93 10.38-2.52l-.38-.28c-2.52 1.79-5.79 2.8-9.01 2.8z"></path><path fill="none" d="M0 0h48v48H0z"></path></svg>
                    Google
                </button>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script src="scripts.js"></script>
</body>
</html>