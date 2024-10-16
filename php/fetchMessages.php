<?php
session_start();
include './db.php';

$sql_group_messages = "SELECT gm.message, u.username, gm.sent_at
                       FROM group_messages gm
                       JOIN users u ON gm.user_id = u.id
                       ORDER BY gm.sent_at DESC";
$result_group_messages = $conn->query($sql_group_messages);

if ($result_group_messages->num_rows > 0) {
    while ($message = $result_group_messages->fetch_assoc()) {
        $message_text = htmlspecialchars($message['message']);
        $message_text = preg_replace('/@(\w+)/', '<span class="mention">@${1}</span>', $message_text);
        echo "<li><b>" . htmlspecialchars($message['username']) . ":</b> " . $message_text . " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>";
    }
} else {
    echo "<li>No messages in the public group yet.</li>";
}
?>
