<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$sql_private_messages = "
    SELECT pm.message, u.username AS sender, pm.sent_at
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
    $private_messages[] = $row;
}

echo json_encode($private_messages);
$stmt_private_messages->close();
$conn->close();
