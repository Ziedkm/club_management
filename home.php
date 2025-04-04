<?php
session_start();

// Include database connection
require_once 'config/database.php';

// Determine which page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
include 'test2.php';
?>


 <!-- Main Content -->
 <main class="main-content">
        <h1>Welcome to Club isgssggssgsggsgs</h1>
        <p>This is a sample page content.</p>
        <h1>Welcome to Club Mantra</h1>
    </main>

    <script src="scripts.js"></script>
</body>
</html>