<?php
session_start();
include './db.php';

$current_user = $_SESSION['username'];

$last_timestamp = $_GET['last_timestamp'] ?? '1970-01-01 00:00:00';

$timeout = 1;
$start_time = time();

while (true) {
    $sql_group_messages = "SELECT gm.message, u.username, gm.sent_at, gm.file_path
                           FROM group_messages gm
                           JOIN users u ON gm.user_id = u.id
                           WHERE gm.sent_at > ?
                           ORDER BY gm.sent_at DESC
                           LIMIT 25";

    $stmt = $conn->prepare($sql_group_messages);
    $stmt->bind_param("s", $last_timestamp);
    $stmt->execute();
    $result_group_messages = $stmt->get_result();

    if ($result_group_messages->num_rows > 0) {
        $messages = [];
        $latest_timestamp = ''; 

        $sql_users = "SELECT username FROM users";
        $user_result = $conn->query($sql_users);
        $users = [];
        while ($user = $user_result->fetch_assoc()) {
            $users[$user['username']] = true;
        }

        while ($message = $result_group_messages->fetch_assoc()) {
            $message_text = htmlspecialchars($message['message']);
            $highlight_class = '';

            preg_match_all('/@(\w+)/', $message['message'], $mentions);
            foreach ($mentions[1] as $mention) {
                if (isset($users[$mention])) {
                    $message_text = preg_replace('/@' . preg_quote($mention, '/') . '/', "<span class=\"mention\">@$mention</span>", $message_text);
                    if ($mention === $current_user) {
                        $highlight_class = 'highlight';
                    }
                }
            }

            $message_text = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="link" target="_blank">$1</a>', $message_text);
            $message_text = preg_replace('/\b(www\.[^\s]+)/', '<a href="http://$1" class="link" target="_blank">$1</a>', $message_text);

            $output = "<span class=\"$highlight_class\"><b>" . htmlspecialchars($message['username']) . ":</b> " . $message_text;

            if (!empty($message['file_path'])) {
                $path = '/projects/strife/uploads/';
                $full_file_path = $_SERVER['DOCUMENT_ROOT'] . $path . $message['file_path'];

                if (file_exists($full_file_path)) {
                    $file_type = mime_content_type($full_file_path);
                    
                    if (strpos($file_type, 'image') !== false) {
                        $output .= "<br><img src='$path" . htmlspecialchars($message['file_path']) . "' alt='image' style='max-width: 200px;' />";
                    } elseif (strpos($file_type, 'audio') !== false) {
                        $output .= "<br><audio controls><source src='$path" . htmlspecialchars($message['file_path']) . "' type='$file_type'></audio>";
                    } elseif (strpos($file_type, 'video') !== false) {
                        $output .= "<br><video controls style='max-width: 200px;'><source src='$path" . htmlspecialchars($message['file_path']) . "' type='$file_type'></video>";
                    } else {
                        $output .= "<br>Unsupported file type: " . htmlspecialchars($file_type);
                    }
                } else {
                    $output .= "<br>File does not exist.";
                }
            }

            $output .= " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></span>"; 
            $messages[] = $output; 

            if ($latest_timestamp === '' || $message['sent_at'] > $latest_timestamp) {
                $latest_timestamp = $message['sent_at'];
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['messages' => array_reverse($messages), 'latest_timestamp' => $latest_timestamp]);
        exit; 
    }

    if (time() - $start_time >= $timeout) {
        break; 
    }

    usleep(5000);
}

header('Content-Type: application/json');
echo json_encode(['messages' => [], 'latest_timestamp' => $last_timestamp]);
