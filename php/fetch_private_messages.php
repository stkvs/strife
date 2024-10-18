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

$sql_private_messages = "
    SELECT pm.message, u.username AS sender, UNIX_TIMESTAMP(pm.sent_at) AS sent_at
    FROM private_messages pm
    JOIN users u ON pm.sender_id = u.id
    WHERE (pm.sender_id = ? AND pm.receiver_id = ?)
    OR (pm.sender_id = ? AND pm.receiver_id = ?)
    ORDER BY pm.sent_at ASC";

$stmt_private_messages = $conn->prepare($sql_private_messages);
$stmt_private_messages->bind_param("iiii", $user_id, $selected_user_id, $selected_user_id, $user_id);
$stmt_private_messages->execute();
$result_private_messages = $stmt_private_messages->get_result();

$private_messages = [];
while ($row = $result_private_messages->fetch_assoc()) {
    $row['message'] = decryptMessage($row['message'], DECRYPTION_KEY);
    $private_messages[] = $row;

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

    $output = "<li class=\"$highlight_class\"><b>" . htmlspecialchars($message['username']) . ":</b> " . $message_text;

    if (!empty($message['file_path'])) {
        $full_file_path = $_SERVER['DOCUMENT_ROOT'] . '/students/2024/spencer/strife/uploads/' . $message['file_path'];

        if (file_exists($full_file_path)) {
            $file_type = mime_content_type($full_file_path);
            
            if (strpos($file_type, 'image') !== false) {
                $output .= "<br><img src='/students/2024/spencer/strife/uploads/" . htmlspecialchars($message['file_path']) . "' alt='image' style='max-width: 200px;' />";
            } elseif (strpos($file_type, 'audio') !== false) {
                $output .= "<br><audio controls><source src='/students/2024/spencer/strife/uploads/" . htmlspecialchars($message['file_path']) . "' type='$file_type'></audio>";
            } elseif (strpos($file_type, 'video') !== false) {
                $output .= "<br><video controls style='max-width: 200px;'><source src='/students/2024/spencer/strife/uploads/" . htmlspecialchars($message['file_path']) . "' type='$file_type'></video>";
            } else {
                $output .= "<br>Unsupported file type: " . htmlspecialchars($file_type);
            }
        } else {
            $output .= "<br>File does not exist.";
        }
    }

    $output .= " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>"; 
    $messages[] = $output; 

}

echo json_encode($private_messages);
$stmt_private_messages->close();
$conn->close();
