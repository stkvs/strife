<?php
session_start();
include './php/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handling group message submission
    if (isset($_POST['group_message'])) {
        $group_message = $_POST['group_message'];

        // Insert the group message with the logged-in user ID
        $stmt = $conn->prepare("INSERT INTO group_messages (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $group_message);

        if ($stmt->execute()) {
            header("Location: home.php");
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all group messages
$sql_group_messages = "SELECT gm.message, u.username, gm.sent_at
                       FROM group_messages gm
                       JOIN users u ON gm.user_id = u.id
                       ORDER BY gm.sent_at DESC";
$result_group_messages = $conn->query($sql_group_messages);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Group Messages</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

    <h3>Public Group Messages</h3>
    <ul>
        <?php
        if ($result_group_messages->num_rows > 0) {
            while ($message = $result_group_messages->fetch_assoc()) {
                echo "<li><b>" . htmlspecialchars($message['username']) . ":</b> " . htmlspecialchars($message['message']) . " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>";
            }
        } else {
            echo "<li>No messages in the public group yet.</li>";
        }
        ?>
    </ul>

    <h3>Send a Message to the Group</h3>
    <form>
        <textarea id="messageInput" name="group_message" rows="4" cols="50" placeholder="Type your message here..." required></textarea><br>
        <input type="button" id="sendButton" value="Send Message">
    </form>


    <p><a href="./php/logout.php">Logout</a></p>

    <script src="./js/sendGlobal.js"></script>
</body>
</html>