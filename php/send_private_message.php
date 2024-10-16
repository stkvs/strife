<?php
session_start();
include 'db.php'; // Ensure this path is correct

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "You must be logged in to send a message."]);
        exit;
    }

    // Get the logged-in user's ID and the message details
    $user_id = $_SESSION['user_id'];
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0; // Ensure receiver_id is set
    $message = isset($_POST['private_message']) ? trim($_POST['private_message']) : '';

    // Validate the message and receiver ID
    if (empty($message) || empty($receiver_id)) {
        echo json_encode(["status" => "error", "message" => "Message or receiver ID is missing."]);
        exit;
    }

    // Handle file upload if there's any
    $file_path = null; // Initialize file_path to null

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Define allowed file types
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'audio/mpeg', 'video/mp4', 'audio/mp4'];
        $file_type = $_FILES['file']['type'];

        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(["status" => "error", "message" => "File type not allowed."]);
            exit;
        }

        // Define upload directory
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
                echo json_encode(["status" => "error", "message" => "Failed to create upload directory."]);
                exit;
            }
        }

        // Move uploaded file to the designated directory
        $file_name = basename($_FILES['file']['name']);
        $file_path = $upload_dir . uniqid() . "_" . $file_name;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            echo json_encode(["status" => "error", "message" => "File upload failed."]);
            exit;
        }
    }

    // Prepare and execute database insertion for private messages
    $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message, file_path) VALUES (?, ?, ?, ?)");

    if ($stmt === false) {
        echo json_encode(["status" => "error", "message" => "Error preparing the statement: " . $conn->error]);
        exit;
    }

    // Check if $file_path is null and bind accordingly
    if ($file_path === null) {
        $stmt->bind_param("iis", $user_id, $receiver_id, $message);
    } else {
        $stmt->bind_param("iiss", $user_id, $receiver_id, $message, $file_path);
    }

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Message sent successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error executing query: " . $stmt->error]);
        error_log("Database Insert Error: " . $stmt->error); // Log the error
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
