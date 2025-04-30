<?php
session_start();

// --- PHP Prerequisites & Admin Check ---
require_once 'config/database.php'; // Provides $pdo
// include_once 'functions.php';

// **** CRITICAL: Authorization Check ****
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error_message'] = "Access Denied: Admin privileges required.";
    header('Location: home.php'); exit;
}
$adminUserId = $_SESSION['user']['id'];

// --- Helper Function ---
function format_time_ago($datetime, $full = false) { try { $now = new DateTime; $timestamp = strtotime($datetime); if ($timestamp === false) throw new Exception("Invalid datetime"); $ago = new DateTime('@'.$timestamp); $diff = $now->diff($ago); $string = ['y'=>'year','m'=>'month','d'=>'day','h'=>'hour','i'=>'minute','s'=>'second']; foreach ($string as $k=>&$v){if(property_exists($diff, $k)){if($diff->$k)$v=$diff->$k.' '.$v.($diff->$k>1?'s':'');else unset($string[$k]);}else unset($string[$k]);} if(!$full)$string=array_slice($string,0,1); return $string?implode(', ',$string).' ago':'just now'; } catch(Exception $e){ error_log("Time format err: ".$e->getMessage()); $ts=strtotime($datetime); return $ts?date('M j, Y g:i a',$ts):'Invalid date';} }

// --- Fetch Stats Data ---
// Initialize with default empty/zero values
$stats = [
    'total_users' => 0, 'total_clubs' => 0, 'active_clubs' => 0, 'pending_clubs' => 0,
    'total_events' => 0, 'active_events' => 0, 'pending_events' => 0,
    'users_by_role' => [], 'clubs_by_category' => [], 'events_by_category' => [],
    'top_clubs_by_members' => [], 'recent_users' => [], 'users_per_month' => [],
];
$pageError = null;

try {
    if (!isset($pdo)) { throw new Exception("DB connection unavailable."); }

    // Use prepared statements even for simple counts for consistency and potential future filtering
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
    $stats['total_clubs'] = $pdo->query("SELECT COUNT(*) FROM clubs WHERE status != 'rejected'")->fetchColumn() ?: 0;
    $stats['active_clubs'] = $pdo->query("SELECT COUNT(*) FROM clubs WHERE status = 'active'")->fetchColumn() ?: 0;
    $stats['pending_clubs'] = $pdo->query("SELECT COUNT(*) FROM clubs WHERE status = 'pending'")->fetchColumn() ?: 0;
    $stats['total_events'] = $pdo->query("SELECT COUNT(*) FROM events WHERE status != 'rejected'")->fetchColumn() ?: 0;
    $stats['active_events'] = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'active'")->fetchColumn() ?: 0;
    $stats['pending_events'] = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetchColumn() ?: 0;

    $stmtDepts = $pdo->query("SELECT department, COUNT(DISTINCT user_id) as count
                              FROM club_members
                              WHERE department IS NOT NULL AND department != ''
                              GROUP BY department
                              ORDER BY count DESC"); // Order by most common department roles first
    // Store this under a more descriptive key
    $stats['users_by_department'] = $stmtDepts->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // Clubs by Category (Active)
    $stmtClubCats = $pdo->query("SELECT category, COUNT(*) as count FROM clubs WHERE status = 'active' GROUP BY category ORDER BY count DESC");
    $stats['clubs_by_category'] = $stmtClubCats->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // Events by Category (Active)
    $stmtEventCats = $pdo->query("SELECT c.category, COUNT(e.id) as count FROM events e JOIN clubs c ON e.club_id = c.id WHERE e.status = 'active' AND c.status = 'active' GROUP BY c.category ORDER BY count DESC");
    $stats['events_by_category'] = $stmtEventCats->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // Top 5 Clubs by Members
    $stmtTopClubs = $pdo->query("SELECT c.name, COUNT(cm.user_id) as member_count FROM clubs c LEFT JOIN club_members cm ON c.id = cm.club_id WHERE c.status = 'active' GROUP BY c.id, c.name ORDER BY member_count DESC LIMIT 5");
    $stats['top_clubs_by_members'] = $stmtTopClubs->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Recent 5 Users
    $stmtRecentUsers = $pdo->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $stats['recent_users'] = $stmtRecentUsers->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // User Registrations per Month
    $stmtUsersMonth = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
    $stmtUsersMonth->execute();
    $stats['users_per_month'] = $stmtUsersMonth->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (Exception $e) {
    error_log("Admin Stats Fetch Error: " . $e->getMessage());
    $pageError = "Could not load all statistics data.";
    // Keep default $stats values on error
}

// ** UPDATED: Use users_by_department for the first chart **
$userDepartmentLabels = !empty($stats['users_by_department']) ? json_encode(array_keys($stats['users_by_department'])) : '[]';
$userDepartmentData = !empty($stats['users_by_department']) ? json_encode(array_values($stats['users_by_department'])) : '[]';
$clubCategoryLabels = !empty($stats['clubs_by_category']) ? json_encode(array_keys($stats['clubs_by_category'])) : '[]';
$clubCategoryData = !empty($stats['clubs_by_category']) ? json_encode(array_values($stats['clubs_by_category'])) : '[]';

$userTrendLabels = '[]';
$userTrendData = '[]';
if (!empty($stats['users_per_month'])) {
    $_labels = []; $_data = [];
    $usersMonthLookup = array_column($stats['users_per_month'], 'count', 'month');
    $currentDate = new DateTime(); $currentDate->modify('first day of this month');
    for ($i = 0; $i < 12; $i++) {
        $monthLabel = $currentDate->format('Y-m');
        $_labels[] = $currentDate->format('M Y');
        $_data[] = $usersMonthLookup[$monthLabel] ?? 0;
        $currentDate->modify('-1 month');
    }
    $userTrendLabels = json_encode(array_reverse($_labels));
    $userTrendData = json_encode(array_reverse($_data));
}

// --- NOW START HTML OUTPUT ---
include_once 'header.php'; // Includes layout
?>

<!-- Include Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script> <!-- Optional but good for time scales -->


<!-- Main Content Area -->
<main class="main-content admin-stats-container">
    <div class="admin-stats-wrapper">

        <!-- Page Header -->
        <div class="admin-header">
            <h1>Platform Statistics</h1>
            <p>Overview of users, clubs, and events activity.</p>
        </div>

        <!-- Error Message -->
        <?php if ($pageError): ?> <div class="message error-message" role="alert"><?php echo htmlspecialchars($pageError); ?></div><?php endif; ?>

        <!-- Stats Cards -->
        <section class="stats-cards-grid">
            <div class="stat-card card"> <div class="stat-icon users"><i class="fas fa-users"></i></div> <div class="stat-content"> <span class="stat-number"><?php echo $stats['total_users']; ?></span> <span class="stat-label">Total Users</span> </div> </div>
            <div class="stat-card card"> <div class="stat-icon clubs"><i class="fas fa-child"></i></div> <div class="stat-content"> <span class="stat-number"><?php echo $stats['active_clubs']; ?></span> <span class="stat-label">Active Clubs</span> <small>(<?php echo $stats['pending_clubs']; ?> Pending)</small> </div> </div>
            <div class="stat-card card"> <div class="stat-icon events"><i class="fas fa-calendar-check"></i></div> <div class="stat-content"> <span class="stat-number"><?php echo $stats['active_events']; ?></span> <span class="stat-label">Active Events</span> <small>(<?php echo $stats['pending_events']; ?> Pending)</small> </div> </div>
        </section>

        <!-- Charts Section -->
        <section class="stats-charts-grid">
             <div class="chart-container card">
                 <h2>User Role Distribution</h2>
                 <!-- Add height/width directly to canvas for initial sizing -->
                 <canvas id="userRoleChart" width="400" height="350"></canvas>
             </div>
             <div class="chart-container card">
                    <h2>Clubs by Category</h2>
                    <canvas id="clubCategoryChart" width="400" height="350"></canvas>
            </div>
             <div class="chart-container card full-width">
                 <h2>New User Registrations (Last 12 Months)</h2>
                  <canvas id="userTrendChart" width="800" height="300"></canvas>
            </div>
        </section>

        <!-- Lists Section -->
        <section class="stats-lists-grid">
            <div class="list-container card"><h2>Top 5 Clubs (by Members)</h2> <?php if(!empty($stats['top_clubs_by_members'])): ?><ol class="styled-list"><?php foreach($stats['top_clubs_by_members'] as $club): ?><li><span class="list-item-main"><?php echo htmlspecialchars($club['name']); ?></span><span class="list-item-count"><?php echo $club['member_count']; ?> Members</span></li><?php endforeach; ?></ol><?php else: ?><p class="empty-list-message">No active clubs.</p><?php endif; ?></div>
            <div class="list-container card"><h2>Recently Joined Users</h2> <?php if(!empty($stats['recent_users'])): ?><ul class="styled-list"><?php foreach($stats['recent_users'] as $user): ?><li><span class="list-item-main"><?php echo htmlspecialchars($user['username']); ?></span><span class="list-item-count"><?php echo format_time_ago($user['created_at']); ?></span></li><?php endforeach; ?></ul><?php else: ?><p class="empty-list-message">No recent users.</p><?php endif; ?></div>
        </section>

    </div> <!-- End Wrapper -->
    <?php include_once 'footer.php'; ?>
</main>

<!-- JavaScript for Charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Helper function to get CSS variable (safer)
    function getCssVariable(variableName) {
        return getComputedStyle(document.documentElement).getPropertyValue(variableName).trim() || null;
    }

    // Determine colors based on theme (check body class)
    const isDarkMode = document.body.classList.contains('dark');
    const primaryColor = getCssVariable('--primary-color') || 'hsl(210, 100%, 50%)';
    const textColor = isDarkMode ? (getCssVariable('--text-color') || '#ffffff') : (getCssVariable('--text-color') || '#333333');
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.1)'; // Slightly more visible grids
    const tooltipBgColor = isDarkMode ? '#2a2a6a' : '#fff';
    const tooltipFontColor = textColor; // Use main text color for tooltips

    // Chart.js Defaults
    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;
    Chart.defaults.font.family = 'Arial, sans-serif'; // Match body font if needed

    // Color Palette for Doughnut/Pie
    const chartColors = [
        'hsla(210, 100%, 60%, 0.7)', // Brighter Blue
        'hsla(160, 70%, 50%, 0.7)',  // Teal/Green
        'hsla(39, 100%, 60%, 0.7)',  // Orange
        'hsla(340, 82%, 60%, 0.7)',  // Pink/Red
        'hsla(260, 50%, 60%, 0.7)',  // Purple
        'hsla(50, 95%, 55%, 0.7)'   // Yellow/Gold
    ];
    const chartBorderColors = chartColors.map(c => c.replace(', 0.7)', ', 1)')); // Solid border

    try {
// --- User Department Chart (Doughnut) --- // Corrected comment
const userDeptCtx = document.getElementById('userRoleChart'); // Canvas ID is still userRoleChart
        const userDepartmentDataJS = <?php echo $userDepartmentData; ?>; // Correct PHP data var
        const userDepartmentLabelsJS = <?php echo $userDepartmentLabels; ?>; // Correct PHP labels var
        if (userDeptCtx && userDepartmentDataJS && userDepartmentDataJS.length > 0) {
            console.log("Initializing User Department Chart"); // Correct log
            new Chart(userDeptCtx, { // Use userDeptCtx
                type: 'doughnut',
                data: {
                    labels: userDepartmentLabelsJS, // Use correct JS labels var
                    datasets: [{
                        label: ' User Departments', // Correct dataset label
                        data: userDepartmentDataJS, // Use correct JS data var
                        backgroundColor: chartColors.slice(0, userDepartmentDataJS.length),
                        borderColor: isDarkMode ? '#1a1a4a' : '#fff',
                        borderWidth: 2, hoverOffset: 4
                    }]
                },
                options: { // Options likely okay
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: textColor, padding: 15 } }, title: { display: false }, tooltip: { backgroundColor: tooltipBgColor, titleColor: tooltipFontColor, bodyColor: tooltipFontColor, padding: 10, cornerRadius: 3 } }
                }
            });
        // **** END OF CORRECTIONS ****
        } else if (!userDeptCtx) {
            console.error("Canvas #userRoleChart not found for Department Chart");
        } else {
            console.log("No data for User Department Chart (Data array might be empty)");
            // Optionally display a message in the chart container if no data
            const chartContainer = userDeptCtx.closest('.chart-container');
            if(chartContainer) {
                 const noDataMsg = document.createElement('p');
                 noDataMsg.textContent = 'No department data available.';
                 noDataMsg.style.textAlign = 'center';
                 noDataMsg.style.marginTop = '2rem';
                 noDataMsg.style.color = textColor;
                 userDeptCtx.parentNode.insertBefore(noDataMsg, userDeptCtx.nextSibling); // Add msg after canvas
            }
        }

        // --- Club Category Chart (Bar) ---
        const clubCatCtx = document.getElementById('clubCategoryChart');
        const clubCategoryData = <?php echo $clubCategoryData; ?>;
        if (clubCatCtx && clubCategoryData && clubCategoryData.length > 0) {
            console.log("Initializing Club Category Chart");
            new Chart(clubCatCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo $clubCategoryLabels; ?>,
                    datasets: [{
                        label: 'Clubs', data: clubCategoryData,
                        backgroundColor: primaryColor.replace(')', ', 0.7)'), // Use primary with alpha
                        borderColor: primaryColor,
                        borderWidth: 1, borderRadius: 3
                    }]
                },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    scales: { x: { beginAtZero: true, grid: { color: gridColor, drawBorder: false }, ticks: { color: textColor, precision: 0 } }, y: { grid: { display: false }, ticks: { color: textColor } } },
                    plugins: { legend: { display: false }, title: { display: false }, tooltip: { backgroundColor: tooltipBgColor, titleColor: tooltipFontColor, bodyColor: tooltipFontColor, padding: 10, cornerRadius: 3 } }
                }
            });
        } else if (!clubCatCtx) { console.error("Canvas #clubCategoryChart not found"); }
          else { console.log("No data for Club Category Chart"); /* Optionally display message */ }


        // --- User Trend Chart (Line) ---
        const userTrendCtx = document.getElementById('userTrendChart');
        const userTrendData = <?php echo $userTrendData; ?>;
        if (userTrendCtx && userTrendData && userTrendData.length > 0) {
             console.log("Initializing User Trend Chart");
             new Chart(userTrendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo $userTrendLabels; ?>,
                    datasets: [{
                        label: 'New Users', data: userTrendData, fill: true,
                        borderColor: primaryColor,
                        backgroundColor: primaryColor.replace(')', ', 0.1)'), // Lighter fill
                        tension: 0.3, pointBackgroundColor: primaryColor, pointRadius: 3, pointHoverRadius: 5
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, precision: 0 } }, x: { grid: { color: gridColor }, ticks: { color: textColor } } },
                    plugins: { legend: { display: false }, title: { display: false }, tooltip: { backgroundColor: tooltipBgColor, titleColor: tooltipFontColor, bodyColor: tooltipFontColor, padding: 10, cornerRadius: 3, intersect: false, mode: 'index' } }
                }
            });
         } else if (!userTrendCtx) { console.error("Canvas #userTrendChart not found"); }
           else { console.log("No data for User Trend Chart"); /* Optionally display message */ }

    } catch (error) {
        console.error("Error initializing charts:", error);
        // Display a user-friendly message on the page if charts fail
        const chartSection = document.querySelector('.stats-charts-grid');
        if (chartSection) {
            chartSection.innerHTML = '<p class="message error-message" style="grid-column: 1 / -1;">Could not load charts.</p>';
        }
    }

    console.log("Chart initialization script finished.");
});
</script>

<!-- Specific CSS for Admin Stats -->
<style>
    /* General Admin Layout */
    .admin-stats-container .admin-stats-wrapper { max-width: 1200px; margin: 1rem auto; padding: 0 1rem; }
    .admin-header { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); } body.dark .admin-header { border-bottom-color: #333366; }
    .admin-header h1 { font-size: 2rem; font-weight: 600; color: var(--text-color); } .admin-header p { font-size: 1rem; color: #666; } body.dark .admin-header p { color: #bbb; }
    .message { margin-bottom: 1.5rem; } /* Add margin to messages */

    /* Stat Cards - Refined */
    .stats-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem; }
    .stat-card.card { display: flex; align-items: center; padding: 1rem 1.25rem; gap: 1rem; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .stat-card.card:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.08); } body.dark .stat-card.card:hover { box-shadow: 0 6px 12px rgba(0,0,0,0.2); }
    .stat-icon { font-size: 1.75rem; width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon.users { background-color: hsla(210, 100%, 50%, 0.1); color: hsl(210, 80%, 50%); } body.dark .stat-icon.users { background-color: hsla(210, 100%, 70%, 0.15); color: hsl(210, 80%, 70%); }
    .stat-icon.clubs { background-color: hsla(145, 63%, 49%, 0.1); color: hsl(145, 55%, 40%); } body.dark .stat-icon.clubs { background-color: hsla(145, 63%, 70%, 0.15); color: hsl(145, 55%, 65%); }
    .stat-icon.events { background-color: hsla(39, 100%, 50%, 0.1); color: hsl(39, 85%, 45%); } body.dark .stat-icon.events { background-color: hsla(39, 100%, 70%, 0.15); color: hsl(39, 85%, 65%); }
    .stat-content { flex-grow: 1; }
    .stat-number { display: block; font-size: 2rem; font-weight: 700; /* Bolder */ color: var(--text-color); line-height: 1.1; }
    .stat-label { display: block; font-size: 0.9rem; color: #555; margin-top: 3px; font-weight: 500;} body.dark .stat-label { color: #bbb;}
    .stat-content small { display: block; font-size: 0.8rem; color: #777; margin-top: 4px; } body.dark .stat-content small { color: #aaa; }

    /* Charts - Refined */
    .stats-charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); /* Default 2 columns */ gap: 1.5rem; margin-bottom: 2.5rem; }
    .chart-container.card { padding: 1.5rem; display: flex; flex-direction: column; } /* Use card styles */
    .chart-container h2 { font-size: 1.15rem; font-weight: 600; margin-bottom: 1.25rem; text-align: center; color: var(--text-color); flex-shrink: 0; }
    .chart-container.full-width { grid-column: 1 / -1; } /* Make line chart span full width */
    .chart-container canvas {
        max-width: 100%;
        width: 100% !important; /* Override potential inline styles */
        height: 300px !important; /* Default height, adjust as needed */
        flex-grow: 1; /* Allow canvas container to grow if needed */
        min-height: 250px; /* Ensure minimum height */
    }
    /* Specific height adjustments if needed */
    #userRoleChart, #clubCategoryChart { height: 300px !important; }
    #userTrendChart { height: 280px !important; }

    /* Lists - Refined */
    .stats-lists-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .list-container.card { padding: 1.5rem; }
    .list-container h2 { font-size: 1.15rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-color); }
    .styled-list { list-style: none; padding: 0; margin: 0; }
    .styled-list li { display: flex; justify-content: space-between; align-items: center; padding: 0.7rem 0; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; } body.dark .styled-list li { border-bottom-color: #333366; }
    .styled-list li:last-child { border-bottom: none; }
    .list-item-main { color: var(--text-color); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 1rem;}
    .list-item-count { color: #555; font-size: 0.9rem; white-space: nowrap; padding-left: 1rem; font-weight: 500; } body.dark .list-item-count { color: #bbb; }
    .empty-list-message { padding: 1rem 0; text-align: center; color: #888; font-size: 0.9rem; } body.dark .empty-list-message { color: #aaa; }

    /* Responsive Adjustments */
     @media (max-width: 992px) { /* Adjust breakpoint if needed */
         .stats-charts-grid { grid-template-columns: 1fr; } /* Stack charts earlier */
         .chart-container.full-width { grid-column: auto; } /* Reset full span */
     }
     @media (max-width: 640px) {
        .admin-header h1 { font-size: 1.5rem; }
        .stats-cards-grid { grid-template-columns: 1fr; }
        .stats-lists-grid { grid-template-columns: 1fr; }
        .chart-container h2, .list-container h2 { font-size: 1.1rem; }
        .chart-container canvas { height: 250px !important; min-height: 200px; }
        #userTrendChart { height: 220px !important; }
     }

</style>

</body>
</html>