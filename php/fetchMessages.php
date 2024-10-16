<?php
session_start();
include './db.php';

$current_user = $_SESSION['username'];

// Get the last message timestamp or ID from the request
$last_timestamp = isset($_GET['last_timestamp']) ? $_GET['last_timestamp'] : '1970-01-01 00:00:00'; // Default to epoch if not set

// Set a time limit for long polling (e.g., 30 seconds)
$timeout = 30;
$start_time = time();

// Main loop for long polling
while (true) {
    // Fetch new messages
    $sql_group_messages = "SELECT gm.message, u.username, gm.sent_at, gm.file_path
                           FROM group_messages gm
                           JOIN users u ON gm.user_id = u.id
                           WHERE gm.sent_at > ?
                           ORDER BY gm.sent_at ASC
                           LIMIT 10"; // Limit results for performance

    $stmt = $conn->prepare($sql_group_messages);
    $stmt->bind_param("s", $last_timestamp);
    $stmt->execute();
    $result_group_messages = $stmt->get_result();

    // Check if new messages are available
    if ($result_group_messages->num_rows > 0) {
        // Prepare an array to hold messages
        $messages = [];
        $latest_timestamp = ''; // Track the latest timestamp

        // Output messages
        while ($message = $result_group_messages->fetch_assoc()) {
            $message_text = htmlspecialchars($message['message']);
            $highlight_class = '';

            // Handle mentions
            preg_match_all('/@(\w+)/', $message['message'], $mentions);
            foreach ($mentions[1] as $mention) {
                $sql_check_user = "SELECT username FROM users WHERE BINARY username = ?";
                $stmt_check = $conn->prepare($sql_check_user);
                $stmt_check->bind_param("s", $mention);
                $stmt_check->execute();
                $result_check_user = $stmt_check->get_result();

                if ($result_check_user->num_rows > 0) {
                    $message_text = preg_replace('/@' . preg_quote($mention, '/') . '/', '<span class="mention">@' . $mention . '</span>', $message_text);
                    if ($mention === $current_user) {
                        $highlight_class = 'highlight';
                    }
                }

                $stmt_check->close();
            }

            // Convert URLs to clickable links
            if (substr_count($message['message'], '.') > 2 || 
                strpos($message['message'], 'https') !== false || 
                strpos($message['message'], 'http') !== false || 
                strpos($message['message'], 'www') !== false) {
                $message_text = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="link" target="_blank">$1</a>', $message_text);
                $message_text = preg_replace('/\b(www\.[^\s]+)/', '<a href="http://$1" class="link" target="_blank">$1</a>', $message_text);
            }

            // Prepare message output
            $output = "<li class=\"$highlight_class\"><b>" . htmlspecialchars($message['username']) . ":</b> " . $message_text;

            // Check for file attachments
            if (!empty($message['file_path'])) {
                // Construct the full file path
                $full_file_path = $_SERVER['DOCUMENT_ROOT'] . '/strife/uploads/' . $message['file_path'];

                // Check if the file exists
                if (file_exists($full_file_path)) {
                    // Get file MIME type
                    $file_type = mime_content_type($full_file_path);
                    
                    // Display the file based on type
                    if (strpos($file_type, 'image') !== false) {
                        $output .= "<br><img src='/strife/uploads/" . htmlspecialchars($message['file_path']) . "' alt='image' style='max-width: 200px;' />";
                    } elseif (strpos($file_type, 'audio') !== false) {
                        $output .= "<br><audio controls><source src='/strife/uploads/" . htmlspecialchars($message['file_path']) . "' type='$file_type'></audio>";
                    } elseif (strpos($file_type, 'video') !== false) {
                        $output .= "<br><video controls style='max-width: 200px;'><source src='/strife/uploads/" . htmlspecialchars($message['file_path']) . "' type='$file_type'></video>";
                    } else {
                        $output .= "<br>Unsupported file type: " . htmlspecialchars($file_type);
                    }
                } else {
                    $output .= "<br>File does not exist.";
                }
            }

            $output .= " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li><br>"; // Add line break for spacing
            $messages[] = $output; // Add to messages array

            // Update the latest timestamp
            if ($latest_timestamp === '' || $message['sent_at'] > $latest_timestamp) {
                $latest_timestamp = $message['sent_at'];
            }
        }

        // Return messages and the latest timestamp as JSON
        header('Content-Type: application/json');
        echo json_encode(['messages' => $messages, 'latest_timestamp' => $latest_timestamp]);
        exit; // Exit after sending messages
    }

    // Check if the timeout period has passed
    if (time() - $start_time >= $timeout) {
        break; // Exit loop if timeout reached
    }

    // Sleep for a short duration before checking again
    usleep(500000); // Sleep for 500 ms
}

// If no new messages, return an empty response
header('Content-Type: application/json');
echo json_encode(['messages' => [], 'latest_timestamp' => $last_timestamp]);
