
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubNest - University Club Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-2xl font-bold text-indigo-600">ClubNest</a>
                
                <div class="hidden md:flex space-x-6">
                    <a href="index.php" class="hover:text-indigo-600">Home</a>
                    <a href="index.php?page=clubs" class="hover:text-indigo-600">Clubs</a>
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="index.php?page=dashboard" class="hover:text-indigo-600">Dashboard</a>
                    <?php endif; ?>
                </div>
                
                <div>
                    <?php if (isset($_SESSION['user'])): ?>
                        <div class="flex items-center space-x-4">
                            <a href="index.php?page=profile" class="flex items-center space-x-2">
                                <div class="h-8 w-8 rounded-full bg-indigo-200 flex items-center justify-center">
                                    <?php echo substr($_SESSION['user']['username'], 0, 1); ?>
                                </div>
                                <span><?php echo $_SESSION['user']['username']; ?></span>
                            </a>
                            <a href="actions/logout.php" class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded-md">
                                Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="index.php?page=auth" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                            Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
