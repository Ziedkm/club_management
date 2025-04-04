
<?php
// Initialize session
session_start();

// Include database connection
require_once 'config/database.php';

// Determine which page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Include header
include 'includes/header.php';

// Load the appropriate page
switch ($page) {
    case 'home':
        include 'pages/home.php';
        break;
    case 'auth':
        include 'pages/auth.php';
        break;
    case 'clubs':
        include 'pages/clubs.php';
        break;
    case 'club-detail':
        include 'pages/club-detail.php';
        break;
    case 'dashboard':
        include 'pages/dashboard.php';
        break;
    case 'profile':
        include 'pages/profile.php';
        break;
    default:
        include 'pages/404.php';
}

// Include footer
include 'includes/footer.php';
?>
