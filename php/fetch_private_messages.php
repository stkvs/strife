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
}

echo json_encode($private_messages);
$stmt_private_messages->close();
$conn->close();
