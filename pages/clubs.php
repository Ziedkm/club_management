
<?php
// Get all clubs from database
$clubs = getAllClubs();

// Check if search query is set
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Filter clubs by search query if provided
if (!empty($searchQuery)) {
    $filteredClubs = [];
    foreach ($clubs as $club) {
        if (stripos($club['name'], $searchQuery) !== false || 
            stripos($club['description'], $searchQuery) !== false ||
            stripos($club['category'], $searchQuery) !== false) {
            $filteredClubs[] = $club;
        }
    }
    $clubs = $filteredClubs;
}

// Get categories for filter
$categories = [];
foreach ($clubs as $club) {
    if (!in_array($club['category'], $categories)) {
        $categories[] = $club['category'];
    }
}
?>

<div class="pt-10 pb-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="mb-10 fade-up">
            <h1 class="text-4xl font-bold mb-4">University Clubs</h1>
            <p class="text-lg text-gray-600">
                Discover and join clubs that match your interests
            </p>
        </div>
        
        <!-- Search and filters -->
        <div class="mb-8 fade-up">
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <form action="index.php" method="GET" class="space-y-4">
                    <input type="hidden" name="page" value="clubs">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search input -->
                        <div class="md:col-span-2">
                            <label for="search" class="form-label">Search Clubs</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                                    placeholder="Search by name, description or category..." 
                                    class="form-input pl-10 py-3 w-full rounded-md"
                                >
                            </div>
                        </div>
                        
                        <!-- Category filter -->
                        <div>
                            <label for="category" class="form-label">Filter by Category</label>
                            <select 
                                id="category" 
                                name="category" 
                                class="form-input py-3 w-full rounded-md"
                            >
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option 
                                    value="<?php echo $category; ?>"
                                    <?php echo (isset($_GET['category']) && $_GET['category'] === $category) ? 'selected' : ''; ?>
                                >
                                    <?php echo $category; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Clubs grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if (count($clubs) > 0): ?>
                <?php 
                $delay = 0;
                foreach ($clubs as $club): 
                    // Get member count
                    $members = getClubMembers($club['id']);
                    $memberCount = count($members);
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
                            <a href="index.php?page=club-detail&id=<?php echo $club['id']; ?>" class="btn-primary w-full text-center">
                                View Club
                            </a>
                        </div>
                    </div>
                <?php 
                    $delay += 100;
                endforeach; 
                ?>
            <?php else: ?>
                <div class="md:col-span-3 text-center py-12">
                    <div class="text-5xl text-gray-300 mb-4">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">No clubs found</h3>
                    <p class="text-gray-600">
                        We couldn't find any clubs matching your search criteria.
                    </p>
                    <a href="index.php?page=clubs" class="btn-primary mt-4 inline-block">
                        View All Clubs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
