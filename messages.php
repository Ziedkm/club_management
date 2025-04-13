<?php
session_start(); // Start the session early to manage user authentication
date_default_timezone_set('Africa/Tunis'); // Set the default timezone to Tunisia
// --- Include Required Files ---
require_once 'config/database.php'; // Database connection ($pdo)

// --- Authentication Check ---
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirect to login if user is not authenticated
    exit;
}
$currentUserId = $_SESSION['user']['id']; // Get the current user's ID from the session

// --- Helper Function: Format Time Ago ---
function format_time_ago($datetime, $full = false) {
    try {
        $now = new DateTime;
        $timestamp = strtotime($datetime);
        if ($timestamp === false) throw new Exception("Invalid datetime");
        $ago = new DateTime('@' . $timestamp);
        $diff = $now->diff($ago);

        $string = [
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second'
        ];

        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    } catch (Exception $e) {
        error_log("Error formatting time ('{$datetime}'): " . $e->getMessage());
        return 'Invalid date';
    }
}

// --- Initialize Variables ---
$conversations = [];
$messages = [];
$selectedUser = null;
$selectedUserId = null;
$searchQuery = trim($_GET['search'] ?? '');
$searchResults = [];
$pageError = null;

try {
    if (!isset($pdo)) throw new Exception("Database connection not available.");

    // --- Fetch Conversations ---
    $convQuery = "
        SELECT 
            u.id, 
            u.username, 
            MAX(m.sent_at) AS last_message_time,
            (
                SELECT message_content 
                FROM messages 
                WHERE 
                    (sender_id = :cuid1 AND receiver_id = u.id) OR 
                    (sender_id = u.id AND receiver_id = :cuid1)
                ORDER BY sent_at DESC 
                LIMIT 1
            ) AS last_message_content,
            (
                SELECT COUNT(*) 
                FROM messages 
                WHERE receiver_id = :cuid1 AND sender_id = u.id AND is_read = FALSE
            ) AS unread_count
        FROM messages m
        JOIN users u ON u.id = IF(m.sender_id = :cuid1, m.receiver_id, m.sender_id)
        WHERE 
            (m.sender_id = :cuid1 OR m.receiver_id = :cuid1) AND 
            u.id != :cuid1
        GROUP BY u.id, u.username
        ORDER BY last_message_time DESC
    ";
    $stmtConv = $pdo->prepare($convQuery);
    $stmtConv->bindParam(':cuid1', $currentUserId, PDO::PARAM_INT);
    $stmtConv->execute();
    $conversations = $stmtConv->fetchAll(PDO::FETCH_ASSOC);

    // --- Handle Search ---
    if (!empty($searchQuery)) {
        $searchSql = "
            SELECT id, username 
            FROM users 
            WHERE username LIKE :sq AND id != :cuid2 
            LIMIT 10
        ";
        $stmtSearch = $pdo->prepare($searchSql);
        $searchTerm = '%' . $searchQuery . '%';
        $stmtSearch->bindParam(':sq', $searchTerm, PDO::PARAM_STR);
        $stmtSearch->bindParam(':cuid2', $currentUserId, PDO::PARAM_INT);
        $stmtSearch->execute();
        $searchResults = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Handle Selected Conversation ---
    if (isset($_GET['with']) && is_numeric($_GET['with'])) {
        $selectedUserId = (int)$_GET['with'];
        if ($selectedUserId === $currentUserId) {
            $selectedUserId = null; // Prevent selecting self
        } else {
            // Fetch selected user details
            $userQuery = "SELECT id, username FROM users WHERE id = :suid1";
            $stmtUser = $pdo->prepare($userQuery);
            $stmtUser->bindParam(':suid1', $selectedUserId, PDO::PARAM_INT);
            $stmtUser->execute();
            $selectedUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($selectedUser) {
                // Mark messages as read
                $updateReadSql = "
                    UPDATE messages 
                    SET is_read = TRUE 
                    WHERE receiver_id = :cuid3 AND sender_id = :suid2 AND is_read = FALSE
                ";
                $stmtUpdateRead = $pdo->prepare($updateReadSql);
                $stmtUpdateRead->bindParam(':cuid3', $currentUserId, PDO::PARAM_INT);
                $stmtUpdateRead->bindParam(':suid2', $selectedUserId, PDO::PARAM_INT);
                $stmtUpdateRead->execute();

                // Fetch messages for the conversation
                $msgQuery = "
                    SELECT m.*, u.username AS sender_username 
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE 
                        (m.sender_id = :cuid4 AND m.receiver_id = :suid3) OR 
                        (m.sender_id = :suid3 AND m.receiver_id = :cuid4)
                    ORDER BY m.sent_at ASC
                ";
                $stmtMsg = $pdo->prepare($msgQuery);
                $stmtMsg->bindParam(':cuid4', $currentUserId, PDO::PARAM_INT);
                $stmtMsg->bindParam(':suid3', $selectedUserId, PDO::PARAM_INT);
                $stmtMsg->execute();
                $messages = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $pageError = "User not found.";
                $selectedUserId = null;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error loading data: " . $e->getMessage());
    $pageError = "Error loading data.";
}

// --- Handle Sending Message ---
$sendError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
    $messageContent = trim($_POST['message_content'] ?? '');

    if (empty($messageContent)) {
        $sendError = "Message cannot be empty.";
    } elseif ($receiverId === false || $receiverId == $currentUserId) {
        $sendError = "Invalid recipient.";
    } else {
        try {
            // Check if recipient exists
            $checkUserSql = "SELECT id FROM users WHERE id = :rid1";
            $stmtCheck = $pdo->prepare($checkUserSql);
            $stmtCheck->bindParam(':rid1', $receiverId, PDO::PARAM_INT);
            $stmtCheck->execute();

            if ($stmtCheck->fetch()) {
                // Insert the message
                $insertSql = "
                    INSERT INTO messages (sender_id, receiver_id, message_content, is_read) 
                    VALUES (:sid1, :rid2, :cont, FALSE)
                ";
                $stmtInsert = $pdo->prepare($insertSql);
                $stmtInsert->bindParam(':sid1', $currentUserId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':rid2', $receiverId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':cont', $messageContent, PDO::PARAM_STR);

                if ($stmtInsert->execute()) {
                    header("Location: messages.php?with=" . $receiverId);
                    exit;
                } else {
                    $sendError = "Failed to send the message.";
                }
            } else {
                $sendError = "Recipient not found.";
            }
        } catch (Exception $e) {
            error_log("Error sending message: " . $e->getMessage());
            $sendError = "Error sending message.";
        }
    }
}

// --- Include Layout File ---
include_once 'header.php'; // Contains the HTML structure (e.g., <head>, <body>, <header>)
?>

<!-- Main Content Area -->
<main class="main-content">
    <div class="messages-container">
        <!-- Left Panel: Conversations and Search -->
        <aside class="conversations-panel">
            <!-- Search Form -->
            <div class="panel-header">
                <h2>Messages</h2>
                <form action="messages.php" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="search-input">
                    <button type="submit" class="search-button" aria-label="Search"><i class="fas fa-search"></i></button>
                    <?php if (!empty($searchQuery)): ?>
                        <a href="messages.php" class="clear-search-button" title="Clear Search" aria-label="Clear Search">×</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Conversations List -->
            <div class="conversations-list">
                <?php if (!empty($searchQuery)): ?>
                    <h3 class="list-heading">Search Results</h3>
                    <?php if (count($searchResults) > 0): ?>
                        <?php foreach ($searchResults as $user): ?>
                            <a href="messages.php?with=<?php echo $user['id']; ?>" class="conversation-item <?php echo ($selectedUserId === $user['id']) ? 'active' : ''; ?>">
                                <div class="avatar-placeholder"><?php echo strtoupper(substr(htmlspecialchars($user['username']), 0, 1)); ?></div>
                                <div class="conversation-info">
                                    <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                                    <span class="last-message-preview">Start a conversation</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-results">No users found matching "<?php echo htmlspecialchars($searchQuery); ?>".</p>
                    <?php endif; ?>
                    <hr class="separator">
                    <h3 class="list-heading">Recent Conversations</h3>
                <?php endif; ?>

                <?php if (count($conversations) > 0): ?>
                    <?php foreach ($conversations as $convo): ?>
                        <a href="messages.php?with=<?php echo $convo['id']; ?>" class="conversation-item <?php echo ($selectedUserId === $convo['id']) ? 'active' : ''; ?>">
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr(htmlspecialchars($convo['username']), 0, 1)); ?>
                                <?php if (($convo['unread_count'] ?? 0) > 0): ?>
                                    <span class="unread-badge" aria-label="<?php echo $convo['unread_count']; ?> unread"><?php echo $convo['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="conversation-info">
                                <span class="username"><?php echo htmlspecialchars($convo['username']); ?></span>
                                <span class="last-message-preview">
                                    <?php 
                                    $lm = $convo['last_message_content'] ?? 'No messages'; 
                                    echo htmlspecialchars(substr($lm, 0, 35)) . (strlen($lm) > 35 ? '...' : ''); 
                                    ?>
                                </span>
                            </div>
                            <span class="message-time">
                                <?php echo isset($convo['last_message_time']) ? format_time_ago($convo['last_message_time']) : ''; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php elseif (empty($searchQuery)): ?>
                    <p class="no-results">No recent conversations.</p>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Right Panel: Chat Area -->
        <section class="chat-panel">
            <?php if ($pageError): ?>
                <div class="chat-placeholder error-placeholder">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <p><?php echo htmlspecialchars($pageError); ?></p>
                </div>
            <?php elseif ($selectedUser): ?>
                <header class="chat-header">
                    <h3><?php echo htmlspecialchars($selectedUser['username']); ?></h3>
                </header>
                <div class="message-list" id="message-list">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-item <?php echo ($msg['sender_id'] == $currentUserId) ? 'sent' : 'received'; ?>">
                                <div class="message-bubble">
                                    <p class="message-content"><?php echo nl2br(htmlspecialchars($msg['message_content'])); ?></p>
                                    <span class="message-timestamp" title="<?php echo date('M j, Y g:i:s A', strtotime($msg['sent_at'])); ?>">
                                        <?php echo format_time_ago($msg['sent_at']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-messages">No messages yet. Send the first message!</p>
                    <?php endif; ?>
                </div>
                <footer class="message-input-area">
                    <?php if ($sendError): ?>
                        <p class="send-error" role="alert"><?php echo htmlspecialchars($sendError); ?></p>
                    <?php endif; ?>
                    <form method="POST" action="messages.php?with=<?php echo $selectedUserId; ?>" class="message-form">
                        <input type="hidden" name="receiver_id" value="<?php echo $selectedUserId; ?>">
                        <textarea name="message_content" placeholder="Type your message..." class="message-input" required rows="1" aria-label="Message Input"></textarea>
                        <button type="submit" name="send_message" class="send-button">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i> Send
                        </button>
                    </form>
                </footer>
                <script>
                    // Scroll to the bottom of the message list
                    const ml = document.getElementById('message-list');
                    if (ml) ml.scrollTop = ml.scrollHeight;

                    // Auto-resize the message input textarea
                    const tx = document.querySelector('.message-input');
                    if (tx) {
                        const ih = tx.scrollHeight;
                        tx.setAttribute('style', 'height:' + ih + 'px;overflow-y:hidden;');
                        tx.addEventListener("input", function () {
                            this.style.height = 0;
                            this.style.height = (this.scrollHeight) + 'px';
                        }, false);
                        <?php if ($selectedUserId): ?>tx.focus();<?php endif; ?>
                    }
                </script>
            <?php else: ?>
                <div class="chat-placeholder">
                    <i class="fas fa-comments" aria-hidden="true"></i>
                    <p>Select a conversation or search for a user.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>