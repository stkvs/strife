<?php
session_start();
include './db.php';

$current_user = $_SESSION['username'];

$sql_group_messages = "SELECT gm.message, u.username, gm.sent_at, gm.file_path
                       FROM group_messages gm
                       JOIN users u ON gm.user_id = u.id
                       WHERE u.id IS NOT NULL
                       ORDER BY gm.sent_at DESC";

$result_group_messages = $conn->query($sql_group_messages);

if ($result_group_messages->num_rows > 0) {
    while ($message = $result_group_messages->fetch_assoc()) {
        $message_text = htmlspecialchars($message['message']);
        
        $highlight_class = '';
        
        preg_match_all('/@(\w+)/', $message['message'], $mentions);
        
        foreach ($mentions[1] as $mention) {
            $sql_check_user = "SELECT username FROM users WHERE BINARY username = ?";
            $stmt = $conn->prepare($sql_check_user);
            $stmt->bind_param("s", $mention);
            $stmt->execute();
            $result_check_user = $stmt->get_result();
            
            if ($result_check_user->num_rows > 0) {
                $message_text = preg_replace('/@' . preg_quote($mention, '/') . '/', '<span class="mention">@' . $mention . '</span>', $message_text);
                if ($mention === $current_user) {
                    $highlight_class = 'highlight';
                }
            }
            
            $stmt->close();
        }
        
        if (substr_count($message['message'], '.') > 2 || 
            strpos($message['message'], 'https') !== false || 
            strpos($message['message'], 'http') !== false || 
            strpos($message['message'], 'www') !== false) {
            $message_text = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="link" target="_blank">$1</a>', $message_text);
            $message_text = preg_replace('/\b(www\.[^\s]+)/', '<a href="http://$1" class="link" target="_blank">$1</a>', $message_text);
        }

        echo "<li class=\"$highlight_class\"><b>" . htmlspecialchars($message['username']) . ":</b> " . $message_text . " ";

        if (!empty($message['file_path'])) {
            $full_file_path = $_SERVER['DOCUMENT_ROOT'] . '/strife/uploads/' . $message['file_path'];
            
            echo "Attempting to load file: " . htmlspecialchars($full_file_path) . "<br>";

            if (file_exists($full_file_path)) {
                $file_type = mime_content_type($full_file_path);
                
                if (strpos($file_type, 'image') !== false) {
                    echo "<br><img src='" . htmlspecialchars($message['file_path']) . "' alt='image' style='max-width: 200px;'/>";
                } elseif (strpos($file_type, 'audio') !== false) {
                    echo "<br><audio controls><source src='" . htmlspecialchars($message['file_path']) . "' type='$file_type'></audio>";
                } elseif (strpos($file_type, 'video') !== false) {
                    echo "<br><video controls style='max-width: 200px;'><source src='" . htmlspecialchars($message['file_path']) . "' type='$file_type'></video>";
                }
            } else {
                echo "<br>File does not exist: " . htmlspecialchars($full_file_path);
            }
        }

        echo " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>";
    }
} else {
    echo "<li>No messages in the public group yet.</li>";
}

$files = scandir($_SERVER['DOCUMENT_ROOT'] . '/strife/uploads/');
echo "<br>Available files: " . implode(", ", $files) . "<br>";
