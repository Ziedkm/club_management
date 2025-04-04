
<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'clubnest';
$username = 'root';
$password = '';

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Display error message if connection fails
    die("Database connection failed: " . $e->getMessage());
}

// Function to get all clubs
function getAllClubs()
{
    global $pdo;
    $query = "SELECT * FROM clubs";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to get club by ID
function getClubById($clubId)
{
    global $pdo;
    $query = "SELECT * FROM clubs WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $clubId);
    $stmt->execute();
    return $stmt->fetch();
}

// Function to get club members
function getClubMembers($clubId)
{
    global $pdo;
    $query = "SELECT u.id, u.username, cm.role 
              FROM club_members cm 
              JOIN users u ON cm.user_id = u.id 
              WHERE cm.club_id = :club_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':club_id', $clubId);
    $stmt->execute();
    return $stmt->fetchAll();
}

// User authentication functions
function loginUser($email, $password)
{
    global $pdo;

    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch();

    if ($user && $password === $user['password']) { // In production, use password_verify()
        // Store user in session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        return true;
    }

    return false;
}

function registerUser($username, $email, $password)
{
    global $pdo;

    // Check if email already exists
    $query = "SELECT * FROM users WHERE email = :email OR username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        return false; // User already exists
    }

    // Insert new user
    $query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'student')";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password); // In production, use password_hash()

    return $stmt->execute();
}

// Function to join a club
function joinClub($userId, $clubId)
{
    global $pdo;
    $query = "INSERT INTO club_members (user_id, club_id, role) VALUES (:user_id, :club_id, 'member')";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':club_id', $clubId);
    return $stmt->execute();
}

// Function to leave a club
function leaveClub($userId, $clubId)
{
    global $pdo;
    $query = "DELETE FROM club_members WHERE user_id = :user_id AND club_id = :club_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':club_id', $clubId);
    return $stmt->execute();
}

// Function to check if user is member of a club
function isClubMember($userId, $clubId)
{
    global $pdo;
    $query = "SELECT * FROM club_members WHERE user_id = :user_id AND club_id = :club_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':club_id', $clubId);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Function to check if user is club leader
function isClubLeader($userId, $clubId)
{
    global $pdo;
    $query = "SELECT * FROM club_members WHERE user_id = :user_id AND club_id = :club_id AND role = 'leader'";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':club_id', $clubId);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Function to send notification
function sendNotification($userId, $title, $message)
{
    global $pdo;
    $query = "INSERT INTO notifications (user_id, title, message, created_at) 
              VALUES (:user_id, :title, :message, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':message', $message);
    return $stmt->execute();
}

// Function to get user notifications
function getUserNotifications($userId)
{
    global $pdo;
    $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
