<?php
// Start session
session_start();
include 'db.php'; // This includes the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "You must be logged in to send a message."]);
        exit;
    }

    $user_id = $_SESSION['user_id']; // Get user ID from session
    $message = isset($_POST['group_message']) ? trim($_POST['group_message']) : ''; // Get the message and trim whitespace

    // Validate message input
    if (empty($message)) {
        echo json_encode(["status" => "error", "message" => "Message cannot be empty."]);
        exit;
    }

    // Prepare and execute the query to insert the message
    $stmt = $conn->prepare("INSERT INTO group_messages (user_id, message) VALUES (?, ?)");
    if ($stmt === false) {
        echo json_encode(["status" => "error", "message" => "Error preparing the statement: " . $conn->error]);
        exit;
    }

    // Bind parameters
    $stmt->bind_param("is", $user_id, $message);

    // Execute the statement and return success or error message
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Message sent successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>