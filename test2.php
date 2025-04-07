<?php date_default_timezone_set('Africa/Tunis'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISGS Clubs</title>

    <!-- Core Stylesheets -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles2.css">
    <link rel="stylesheet" href="footer.css">

    <!-- Third Party CSS -->
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" 
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer" />
    
    <!-- JavaScript Files -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="scripts.js"></script>
    <script src="script.js"></script>
</head>
<body>
    <!-- Desktop Sidebar -->
    <header class="sidebar">
        <div class="sidebar-content">
            <div class="logo">
                <a href="/">
                    <span class="text-primary">ISGS Clubs</span>
                </a>
            </div>
            <nav class="nav-links">
                <a href="/cm/home.php" class="nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
                        <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5"/>
                      </svg> Home
                </a>
                <a href="/cm/clubs.php" class="nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25"fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                      </svg> Clubs
                </a>
                <a href="/cm/events.php" class="nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25"fill="currentColor" class="bi bi-calendar2-week-fill" viewBox="0 0 16 16">
                        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5m9.954 3H2.545c-.3 0-.545.224-.545.5v1c0 .276.244.5.545.5h10.91c.3 0 .545-.224.545-.5v-1c0-.276-.244-.5-.546-.5M8.5 7a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm3 0a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM3 10.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5m3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z"/>
                      </svg> Events
                </a>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'admin'): ?>
                    <a href="/cm/admin.php" class="nav-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-person-badge-fill" viewBox="0 0 16 16">
                            <path d="M10.5 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zM8 2a2.5 2.5 0 1 0 .001-4.001A2.5 2.5 0 0 0 8 2zm4.5-.5a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h9zM8 .75A7.25 7.25 0 1 1 .75 8 .75.75 0 0 1 .75.75h7z"/>
                        </svg> Admin Panel
                    </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'club_leader'): ?>
                <a href="/cm/dashboard.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-grid-1x2-fill" viewBox="0 0 16 16">
  <path d="M0 1a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm9 0a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1zm0 9a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1z"/>
</svg>
                     Dashboard
                </a>
                <?php endif; ?>
                <!--messages-->
                <?php if (isset($_SESSION['user'])): ?>
                <a href="/cm/messages.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-envelope-fill" viewBox="0 0 16 16">
  <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
</svg> Messages
                </a>
                <?php endif; ?>
                <!-- ==== START Create Buttons ==== -->
            <?php if (isset($_SESSION['user'])): ?>
                <?php
                    // Check permissions for each button
                    $canCreateClub = ($_SESSION['user']['role'] === 'student' || $_SESSION['user']['role'] === 'admin');
                    // Add logic here later in create_club.php to check if student already leads a club
                    $canCreateEvent = ($_SESSION['user']['role'] === 'club_leader' || $_SESSION['user']['role'] === 'admin');
                ?>

                <?php if ($canCreateClub): ?>
                    <a href="/cm/create_club.php" class="nav-link"> <!-- Simple link -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
                        </svg>
                        Create Club
                    </a>
                <?php endif; ?>

                <?php if ($canCreateEvent): ?>
                    <a href="/cm/create_event.php" class="nav-link"> <!-- Simple link -->
                         <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-calendar-plus-fill" viewBox="0 0 16 16">
                            <path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2M8.5 8.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zM8 12a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                         </svg>
                         Create Event
                    </a>
                <?php endif; ?>

            <?php endif; // End check for logged in user ?>
            <!-- ==== END Create Buttons ==== -->
            
                <a href="/cm/about/about.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-lightbulb-fill" viewBox="0 0 16 16">
  <path d="M2 6a6 6 0 1 1 10.174 4.31c-.203.196-.359.4-.453.619l-.762 1.769A.5.5 0 0 1 10.5 13h-5a.5.5 0 0 1-.46-.302l-.761-1.77a2 2 0 0 0-.453-.618A5.98 5.98 0 0 1 2 6m3 8.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1l-.224.447a1 1 0 0 1-.894.553H6.618a1 1 0 0 1-.894-.553L5.5 15a.5.5 0 0 1-.5-.5"/>
</svg> About
                </a>
            </nav>
            <div class="sidebar-bottom">
                <div class="settings">
                    <select id="language-select" style="background-color: transparent; color: var(--primary-color); border-radius: 5px; padding: 5px; border: 1px solid var(--primary-color);">
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="ar">العربية</option>
                    </select>
                    <button id="theme-toggle"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-brightness-low-fill" viewBox="0 0 16 16">
                        <path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0M8.5 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m0 11a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m5-5a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m-11 0a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m9.743-4.036a.5.5 0 1 1-.707-.707.5.5 0 0 1 .707.707m-7.779 7.779a.5.5 0 1 1-.707-.707.5.5 0 0 1 .707.707m7.072 0a.5.5 0 1 1 .707-.707.5.5 0 0 1-.707.707M3.757 4.464a.5.5 0 1 1 .707-.707.5.5 0 0 1-.707.707"/>
                      </svg></button>
                </div>
                <?php if (isset($_SESSION['user'])): ?>
                        <div class="profilebox">
                            <a href="profile.php" class="flex items-center space-x-2">
                                <div class="h-8 w-8 rounded-full bg-indigo-200 flex items-center justify-center">
                                    <?php echo substr($_SESSION['user']['username'], 0, 1); ?>
                                </div>
                                <span><?php echo $_SESSION['user']['username']; ?></span>
                            </a>
                            <a href="actions/logout.php" class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                                </svg>
                            </a>
                        </div>
                    <?php else: ?>
                        
                        <a href="/cm/login.php" class="btn btn-outline">Login</a>
                <a href="/cm/login.php?action=register" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Mobile Bottom Navigation -->
    <div class="bottom-nav">
        <a href="/cm/home.php" class="nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
                        <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5"/>
                      </svg>
            <span>Home</span>
        </a>
        <a href="/cm/clubs.php" class="nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                      </svg>
            <span>Clubs</span>
        </a>
        <a href="/cm/events.php" class="nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-calendar2-week-fill" viewBox="0 0 16 16">
                        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5m9.954 3H2.545c-.3 0-.545.224-.545.5v1c0 .276.244.5.545.5h10.91c.3 0 .545-.224.545-.5v-1c0-.276-.244-.5-.546-.5M8.5 7a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm3 0a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM3 10.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5m3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z"/>
                      </svg>
            <span>Events</span>
        </a>
        <?php if (isset($_SESSION['user'])): ?>
                <a href="/cm/messages.php" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-envelope-fill" viewBox="0 0 16 16">
  <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
</svg> <span>Messages</span>
                </a>
                <?php endif; ?>
        
        <button id="menu-toggle" class="nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
</svg>
            <span>Menu</span>
        </button>
    </div>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu">
        <div class="mobile-menu-content">
            <div class="mobile-menu-header">
                <a href="/" class="logo">
                    ISGS Clubs
                </a>
                
            </div>
            
            <?php if (isset($_SESSION['user'])): ?>
                <?php
                    // Check permissions for each button
                    $canCreateClub = ($_SESSION['user']['role'] === 'student' || $_SESSION['user']['role'] === 'admin');
                    // Add logic here later in create_club.php to check if student already leads a club
                    $canCreateEvent = ($_SESSION['user']['role'] === 'club_leader' || $_SESSION['user']['role'] === 'admin');
                ?>

                <?php if ($canCreateClub): ?>
                    <a href="/cm/create_club.php" class="nav-list-phone" style="background-color: var(--background-color); color: var(--text-color); border-radius: 5px; padding: 10px; align-items: center; display: flex; gap: 10px; border: 1px solid var(--primary-color); margin: 10px 0;"> <!-- Simple link -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
                        </svg>
                        Create Club
                    </a>
                <?php endif; ?>

                <?php if ($canCreateEvent): ?>
                    <a href="/cm/create_event.php" class="nav-list-phone" style="background-color: var(--background-color); color: var(--text-color); border-radius: 5px; padding: 10px; align-items: center; display: flex; gap: 10px; border: 1px solid var(--primary-color); margin: 10px 0;"> <!-- Simple link -->
                         <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-calendar-plus-fill" viewBox="0 0 16 16">
                            <path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2M8.5 8.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zM8 12a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                         </svg>
                         Create Event
                    </a>
                <?php endif; ?>

            <?php endif; // End check for logged in user ?>
            
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'admin'): ?>
                    <a href="/cm/admin.php" class="nav-list-phone" style="background-color: var(--background-color); color: var(--text-color); border-radius: 5px; padding: 10px; align-items: center; display: flex; gap: 10px; border: 1px solid var(--primary-color); margin: 10px 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-person-badge-fill" viewBox="0 0 16 16">
                            <path d="M10.5 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zM8 2a2.5 2.5 0 1 0 .001-4.001A2.5 2.5 0 0 0 8 2zm4.5-.5a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5h9zM8 .75A7.25 7.25 0 1 1 .75 8 .75.75 0 0 1 .75.75h7z"/>
                        </svg> Admin Panel
                    </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'club_leader'): ?>
                <a href="/cm/dashboard.php" class="nav-list-phone" style="background-color: var(--background-color); color: var(--text-color); border-radius: 5px; padding: 10px; align-items: center; display: flex; gap: 10px; border: 1px solid var(--primary-color); margin: 10px 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-grid-1x2-fill" viewBox="0 0 16 16">
  <path d="M0 1a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm9 0a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1zm0 9a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1z"/>
</svg>
                     Dashboard
                </a>
                <?php endif; ?>
            <a href="/cm/about/about.php" class="nav-list-phone" style="background-color: var(--background-color); color: var(--text-color); border-radius: 5px; padding: 10px; align-items: center; display: flex; gap: 10px; border: 1px solid var(--primary-color); margin: 10px 0;">
        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-lightbulb-fill" viewBox="0 0 16 16">
  <path d="M2 6a6 6 0 1 1 10.174 4.31c-.203.196-.359.4-.453.619l-.762 1.769A.5.5 0 0 1 10.5 13h-5a.5.5 0 0 1-.46-.302l-.761-1.77a2 2 0 0 0-.453-.618A5.98 5.98 0 0 1 2 6m3 8.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1l-.224.447a1 1 0 0 1-.894.553H6.618a1 1 0 0 1-.894-.553L5.5 15a.5.5 0 0 1-.5-.5"/>
</svg>
            About
        </a>
            <div class="mobile-menu-settings">
                <select id="mobile-language-select" style="background-color: transparent; color: var(--primary-color); border-radius: 5px; padding: 5px; border: 1px solid var(--primary-color);">
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                    <option value="ar">العربية</option>
                </select>
                <button id="mobile-theme-toggle"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-brightness-low-fill" viewBox="0 0 16 16">
                        <path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0M8.5 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m0 11a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m5-5a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m-11 0a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m9.743-4.036a.5.5 0 1 1-.707-.707.5.5 0 0 1 .707.707m-7.779 7.779a.5.5 0 1 1-.707-.707.5.5 0 0 1 .707.707m7.072 0a.5.5 0 1 1 .707-.707.5.5 0 0 1-.707.707M3.757 4.464a.5.5 0 1 1 .707-.707.5.5 0 0 1-.707.707"/>
                      </svg></button>
            </div>
            <div class="mobile-menu-auth">
            <?php if (isset($_SESSION['user'])): ?>
                        <div class="profilebox">
                            <a href="profile.php" class="flex items-center space-x-2">
                                <div class="h-8 w-8 rounded-full bg-indigo-200 flex items-center justify-center ">
                                    <?php echo substr($_SESSION['user']['username'], 0, 1); ?>
                                </div>
                                <span><?php echo $_SESSION['user']['username']; ?></span>
                            </a>
                            <a href="actions/logout.php" class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded-md ">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                                </svg>
                            </a>
                        </div>
                    <?php else: ?>
                        
                        <a href="/cm/login.php" class="btn btn-outline">Login</a>
                <a href="/cm/login.php?action=register" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
            </div>
        </div>
    </div>

   