<?php
session_start();
include './php/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch all users for messaging
$sql_users = "SELECT id, username FROM users WHERE id != ?";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->bind_param("i", $user_id);
$stmt_users->execute();
$result_users = $stmt_users->get_result();

$stmt_users->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Private Messages</title>
    <link rel="stylesheet" href="style.css">
    <script src="./js/themeModal.js"></script>
    <script src="./js/private.js"></script>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

        <div class="messages">
            <h3>Your Private Messages</h3>
            <ul id="messageList" style="flex-direction: column;">
                <!-- Messages will be dynamically loaded here -->
            </ul>
            <form id="privateMessageForm" enctype="multipart/form-data">
                <input type="hidden" name="receiver_id" value=""> <!-- This will be set dynamically -->
                <textarea name="private_message" required placeholder="Type your message..."></textarea>
                <input type="file" name="file"> <!-- Optional file upload -->
                <button type="submit">Send Message</button>
            </form>
        </div>

        <div class="sidebar">
            <h3>Select User:</h3>
            <?php
            if ($result_users->num_rows > 0) {
                while ($user = $result_users->fetch_assoc()) {
                    echo '<button type="button" name="select_user" value="' . htmlspecialchars($user['id']) . '">' . htmlspecialchars($user['username']) . '</button>';
                }
            } else {
                echo "<p>No users available.</p>";
            }
            ?>
            <p><a href="global.php">Home</a></p>
            <p><a href="./php/logout.php">Logout</a></p>
        </div>
    </div>
</body>
</html>