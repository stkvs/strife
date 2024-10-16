<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "You must be logged in to send a message."]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $message = isset($_POST['group_message']) ? trim($_POST['group_message']) : '';
    if (empty($message)) {
        echo json_encode(["status" => "error", "message" => "Message cannot be empty."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO group_messages (user_id, message) VALUES (?, ?)");
    if ($stmt === false) {
        echo json_encode(["status" => "error", "message" => "Error preparing the statement: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("is", $user_id, $message);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Message sent successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
