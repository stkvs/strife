<?php
session_start();
include './db.php';

$current_user = $_SESSION['username'];
?>

<?php
$sql_group_messages = "SELECT gm.message, u.username, gm.sent_at
                       FROM group_messages gm
                       JOIN users u ON gm.user_id = u.id
                       WHERE u.id IS NOT NULL
                       ORDER BY gm.sent_at DESC";
$result_group_messages = $conn->query($sql_group_messages);

if ($result_group_messages->num_rows > 0) {
    while ($message = $result_group_messages->fetch_assoc()) {
        $message_text = htmlspecialchars($message['message']);
        
        // Initialize highlight_class
        $highlight_class = '';
        
        // Find all mentions in the message
        preg_match_all('/@(\w+)/', $message['message'], $mentions);
        
        foreach ($mentions[1] as $mention) {
            // Check if the mentioned user exists in the database
            $sql_check_user = "SELECT username FROM users WHERE BINARY username = ?";
            $stmt = $conn->prepare($sql_check_user);
            $stmt->bind_param("s", $mention);
            $stmt->execute();
            $result_check_user = $stmt->get_result();
            
            if ($result_check_user->num_rows > 0) {
                $message_text = preg_replace('/@' . preg_quote($mention, '/') . '/', '<span class="mention">@' . $mention . '</span>', $message_text);
                if ($mention === $current_user) {
                    // Replace the mention with highlighted span if user is the current user
                    $highlight_class = 'highlight';
                }
            }
            
            $stmt->close();
        }
        
        echo "<li class=\"$highlight_class\"><b>" . htmlspecialchars($message['username']) . ":</b> " . $message_text . " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>";
    }
} else {
    echo "<li>No messages in the public group yet.</li>";
}
?>
