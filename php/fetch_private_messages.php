<?php
session_start();
include 'db.php';

define('DECRYPTION_KEY', 'fdadsihuiads');

function decryptMessage($encryptedMessage, $key) {
    $data = base64_decode($encryptedMessage);
    $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
    $encryptedMessage = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
    return openssl_decrypt($encryptedMessage, 'aes-256-cbc', $key, 0, $iv);
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$last_timestamp = isset($_GET['last_timestamp']) ? $_GET['last_timestamp'] : '1970-01-01 00:00:00';

$timeout = 10; // Timeout in seconds
$start_time = time();

while (true) {
    $sql_private_messages = "
        SELECT pm.message, u.username AS sender, UNIX_TIMESTAMP(pm.sent_at) AS sent_at, pm.file_path
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE ((pm.sender_id = ? AND pm.receiver_id = ?)
        OR (pm.sender_id = ? AND pm.receiver_id = ?))
        AND pm.sent_at > ?
        ORDER BY pm.sent_at ASC";

    $stmt_private_messages = $conn->prepare($sql_private_messages);
    $stmt_private_messages->bind_param("iiiis", $user_id, $selected_user_id, $selected_user_id, $user_id, $last_timestamp);
    $stmt_private_messages->execute();
    $result_private_messages = $stmt_private_messages->get_result();

    if ($result_private_messages->num_rows > 0) {
        $private_messages = [];
        $latest_timestamp = $last_timestamp;

        while ($row = $result_private_messages->fetch_assoc()) {
            $row['message'] = decryptMessage($row['message'], DECRYPTION_KEY);
            
            $message_text = htmlspecialchars($row['message']);
            $output = "<li><b>" . htmlspecialchars($row['sender']) . ":</b> " . $message_text;

            if (!empty($row['file_path'])) {
                $path = '/strife/uploads/';
                $full_file_path = $_SERVER['DOCUMENT_ROOT'] . $path . $row['file_path'];

                if (file_exists($full_file_path)) {
                    $file_type = mime_content_type($full_file_path);
                    
                    if (strpos($file_type, 'image') !== false) {
                        $output .= "<br><img src='$path" . htmlspecialchars($row['file_path']) . "' alt='image' style='max-width: 200px;' />";
                    } elseif (strpos($file_type, 'audio') !== false) {
                        $output .= "<br><audio controls><source src='$path" . htmlspecialchars($row['file_path']) . "' type='$file_type'></audio>";
                    } elseif (strpos($file_type, 'video') !== false) {
                        $output .= "<br><video controls style='max-width: 200px;'><source src='$path" . htmlspecialchars($row['file_path']) . "' type='$file_type'></video>";
                    } else {
                        $output .= "<br>Unsupported file type: " . htmlspecialchars($file_type);
                    }
                } else {
                    $output .= "<br>File does not exist.";
                }
            }

            $output .= " <i>(" . date('Y-m-d H:i:s', $row['sent_at']) . ")</i></li>"; 
            $private_messages[] = $output;

            if ($row['sent_at'] > $latest_timestamp) {
                $latest_timestamp = date('Y-m-d H:i:s', $row['sent_at']);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['messages' => $private_messages, 'latest_timestamp' => $latest_timestamp]);
        $stmt_private_messages->close();
        $conn->close();
        exit;
    }

    if (time() - $start_time >= $timeout) {
        break;
    }

    usleep(500000); // Sleep for 0.5 seconds
}

header('Content-Type: application/json');
echo json_encode(['messages' => [], 'latest_timestamp' => $last_timestamp]);
$stmt_private_messages->close();
$conn->close();