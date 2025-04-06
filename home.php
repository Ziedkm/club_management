<?php
session_start();
// Make sure session is started if needed by other parts of the site,
// though not strictly required by the content below unless checking login status
// session_start();

// Include database connection and functions
require_once 'config/database.php';
// Assuming getAllClubs() and getClubMembers() are defined here or in database.php
include 'test2.php';

// --- Data Fetching for the Page ---
// Get all clubs - assuming this function exists
$all_clubs = function_exists('getAllClubs') ? getAllClubs() : [];

// Get member counts - assuming this function exists
// Doing it here avoids calling inside the loop repeatedly if getClubMembers is slow,
// but requires fetching all members for all displayed clubs upfront.
// Alternatively, call getClubMembers inside the loop if performance is acceptable.
$featured_clubs_data = [];
$count = 0;
foreach ($all_clubs as $club) {
    if ($count >= 3) break; // Limit to 3 featured clubs

    $members = function_exists('getClubMembers') ? getClubMembers($club['id']) : [];
    $memberCount = count($members);

    $featured_clubs_data[] = [
        'id' => $club['id'],
        'name' => $club['name'],
        'description' => $club['description'],
        'category' => $club['category'],
        'memberCount' => $memberCount,
    ];
    $count++;
}

?>



<!-- Main Content Area -->
<main class="main-content pb-0  px-0 py-0">

<div class="pt-10 pb-16 px-4 py-4">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Hero section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
            <div class="space-y-6 fade-up">
                <h1 class="text-4xl md:text-5xl font-bold leading-tight">
                    Discover and join university clubs in one place
                </h1>
                <p class="text-lg text-gray-600">
                    ClubNest connects you with clubs that match your interests, helps you stay updated with events, and simplifies club management for leaders.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="index.php?page=clubs" class="btn-primary py-3 px-6">
                        Explore Clubs
                    </a>
                    <a href="index.php?page=auth" class="btn-secondary py-3 px-6">
                        Sign Up Now
                    </a>
                </div>
            </div>
            <div class="fade-up" style="animation-delay: 200ms;width:90%;">
                <img src="assets/images/7826321.webp" alt="Club illustration" class="w-full" > 
            </div>
        </div>
        
        <!-- Features section -->
        <div class="mb-16">
            <div class="text-center mb-12 fade-up">
                <h2 class="text-3xl font-bold mb-4">Why use ClubNest?</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Our platform makes club management and participation easier than ever
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 100ms;">
                    <div class="bg-indigo-100 w-12 h-12 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-search text-indigo-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Discover Clubs</h3>
                    <p class="text-gray-600">
                        Browse and search through all available university clubs based on your interests.
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 200ms;">
                    <div class="bg-indigo-100 w-12 h-12 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-bell text-indigo-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Stay Updated</h3>
                    <p class="text-gray-600">
                        Receive notifications about club activities, events, and important announcements.
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="bg-white p-6 rounded-lg shadow-md border fade-up" style="animation-delay: 300ms;">
                    <div class="bg-indigo-100 w-12 h-12 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-users-cog text-indigo-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Easy Management</h3>
                    <p class="text-gray-600">
                        Club leaders can manage members, send notifications, and organize events with ease.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Featured clubs section -->
        <div>
            <div class="text-center mb-12 fade-up">
                <h2 class="text-3xl font-bold mb-4">Featured Clubs</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Check out some of our most popular university clubs
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                // Get all clubs from database
                $clubs = getAllClubs();
                
                // Display up to 3 clubs
                $count = 0;
                foreach ($clubs as $club) {
                    if ($count >= 3) break;
                    
                    // Get member count
                    $members = getClubMembers($club['id']);
                    $memberCount = count($members);
                    
                    $delay = 100 * $count;
                ?>
                <div class="bg-white rounded-lg shadow-md border overflow-hidden fade-up" style="animation-delay: <?php echo $delay; ?>ms;">
                    <div class="h-40 bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-users text-indigo-300 text-5xl"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2"><?php echo $club['name']; ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo substr($club['description'], 0, 100) . (strlen($club['description']) > 100 ? '...' : ''); ?></p>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="club-tag club-tag-primary">
                                <i class="fas fa-users mr-1"></i> <?php echo $memberCount; ?> members
                            </span>
                            <span class="club-tag club-tag-secondary">
                                <?php echo $club['category']; ?>
                            </span>
                        </div>
                        <a href="index.php?page=club-detail&id=<?php echo $club['id']; ?>" class="btn btn-primary w-full text-center">
                            View Club
                        </a>
                    </div>
                </div>
                <?php
                    $count++;
                }
                ?>
            </div>
            
            <div class="text-center mt-8">
                <a href="index.php?page=clubs" class="btn-secondary py-3 px-6 inline-flex items-center">
                    View All Clubs
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<?php
// Include footer if needed
include 'footer.php';
?>

</main>


 
</body>
</html>


<!-- You might have a footer outside the main content -->