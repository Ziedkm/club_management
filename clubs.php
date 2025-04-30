<?php
// Start session if needed for other parts (like user login status for 'Join' buttons, etc.)
session_start();

// Include database connection and functions
require_once 'config/database.php'; // Adjust path if necessary
// Assuming getAllClubs() and getClubMembers() are defined here or in database.php
// Use include_once to prevent multiple inclusions if called elsewhere
include_once 'header.php'; // Adjust path if necessary

// --- Data Fetching and Filtering ---

// 1. Get ALL clubs from the function
$all_clubs_from_db = [];
if (function_exists('getAllClubs')) {
    $all_clubs_from_db = getAllClubs(); // Get all, including pending/rejected
} else {
    error_log("getAllClubs function not found.");
}

// **** NEW: Filter for ACTIVE clubs ONLY ****
$all_clubs = []; // This will hold only active clubs for display
foreach ($all_clubs_from_db as $club) {
    // Check if the status column exists and is 'active'
    // Use null coalescing in case 'status' column doesn't exist yet or is null
    if (($club['status'] ?? 'pending') === 'active') {
        $all_clubs[] = $club;
    }
}
// Now $all_clubs contains only active ones


// 2. Generate list of unique categories from ACTIVE clubs
$all_categories = [];
foreach ($all_clubs as $club) { // Loop through the already filtered $all_clubs
    $category = $club['category'] ?? null;
    if ($category && !in_array($category, $all_categories)) {
        $all_categories[] = $category;
    }
}
sort($all_categories);


// 3. Get filter parameters from URL
$searchQuery = trim($_GET['search'] ?? '');
$selectedCategory = trim($_GET['category'] ?? '');

// 4. Start with ACTIVE clubs and apply filters sequentially
$display_clubs = $all_clubs; // Start with the active clubs list

// Apply search filter (logic remains the same)
if (!empty($searchQuery)) {
    $filteredBySearch = [];
    foreach ($display_clubs as $club) {
        $nameMatch = stripos($club['name'] ?? '', $searchQuery) !== false;
        $descMatch = stripos($club['description'] ?? '', $searchQuery) !== false;
        $catMatch = stripos($club['category'] ?? '', $searchQuery) !== false;
        if ($nameMatch || $descMatch || $catMatch) {
            $filteredBySearch[] = $club;
        }
    }
    $display_clubs = $filteredBySearch;
}

// Apply category filter (logic remains the same)
if (!empty($selectedCategory)) {
    $filteredByCategory = [];
    foreach ($display_clubs as $club) {
        if (($club['category'] ?? '') === $selectedCategory) {
            $filteredByCategory[] = $club;
        }
    }
    $display_clubs = $filteredByCategory;
}

?>


    <!-- Main Content Area -->
    <main class="main-content pb-0 pt-10 px-0 py-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pb-10 lg:px-8">

            <!-- Page Header -->
            <div class="mb-10 text-center md:text-left">
                <h1 class="text-3xl md:text-4xl font-bold mb-2">University Clubs</h1>
                <p class="text-lg">
                    Discover and join clubs that match your interests.
                </p>
            </div>

            <!-- Search and Filters Form -->
            <div class="mb-8">
                <div class="bg-white p-4 sm:p-6 rounded-lg shadow border border-gray-200">
                    <form action="clubs.php" method="GET" class="space-y-4">
                        <!-- Hidden input to keep track of the page context -->
                        <input type="hidden" name="page" value="clubs">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <!-- Search Input -->
                            <div class="md:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Clubs</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input
                                        type="text"
                                        id="search"
                                        name="search"
                                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                                        placeholder="Search by name, description, category..."
                                        class="form-input block w-full pl-10 pr-3 py-2 sm:text-sm appearance-none border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                            </div>

                            <!-- Category Filter -->
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-1" style="color: black ;">Filter by Category</label>
                                <select
                                    id="category"
                                    name="category"
                                    class="form-input block w-full py-2 px-3 sm:text-sm appearance-none border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    onchange="this.form.submit()"
                                    style="background-color: white; color: black;"
                                    >
                                    <option value="">All Categories</option>
                                    <?php foreach ($all_categories as $category): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($category); ?>"
                                        <?php echo ($selectedCategory === $category) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-filter mr-2"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Clubs Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($display_clubs) > 0): ?>
                    <?php foreach ($display_clubs as $club): ?>
                        <?php
                            // Get member count - Ensure getClubMembers function exists and works
                            $memberCount = 0;
                            if (function_exists('getClubMembers')) {
                                $members = getClubMembers($club['id']); // Assuming ID exists
                                $memberCount = count($members);
                            }
                        ?>
                        
                        <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden flex flex-col">
                            <!-- Placeholder for Club Image/Logo -->
                           
                            <div class="h-40 bg-blue-100 flex items-center justify-center">
                                <!-- You can replace this with an actual image if available -->
                                <?php if (empty($club['logo_path'])): ?>
                                <i class="fas fa-users text-blue-300 text-5xl"></i>
                                <?php else: ?>
                                    <?php /* in future update we add logo path to the database and use it here:*/
                                    $logoPath = $club['logo_path'];
                                    echo '<img src="'.htmlspecialchars($logoPath).'" alt="'.htmlspecialchars($club['name']).' Logo" class="h-full w-full object-cover">';
                                  ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Club Content -->
                            <div class="p-5 flex flex-col flex-grow">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($club['name'] ?? 'Unnamed Club'); ?></h3>
                                <p class="text-sm text-gray-600 mb-4 flex-grow">
                                    <?php
                                    $description = $club['description'] ?? '';
                                    echo htmlspecialchars(substr($description, 0, 100)) . (strlen($description) > 100 ? '...' : '');
                                    ?>
                                </p>
                                <!-- Tags -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-users mr-1"></i> <?php echo $memberCount; ?> members
                                    </span>
                                    <?php if (!empty($club['category'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <?php echo htmlspecialchars($club['category']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <!-- View Button -->
                                <a href="club-detail.php?page=club-detail&id=<?php echo $club['id'] ?? ''; ?>" class="mt-auto block w-full text-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    View Club
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- No Clubs Found Message -->
                    <div class="sm:col-span-2 lg:col-span-3 text-center py-12">
                        <div class="text-6xl text-gray-300 mb-4">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No clubs found</h3>
                        <p class="text-gray-500 mb-4">
                            We couldn't find any clubs matching your current filters.
                        </p>
                        <a href="clubs.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Clear Filters & View All
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div> <!-- End container -->
        <?php
// Include footer if needed
include 'footer.php';
?>
    </main>



    <!-- Add any necessary JS files here -->
    <!-- <script src="scripts.js"></script> -->

</body>
</html>