<?php
session_start();
include './php/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['group_message'])) {
        $group_message = $_POST['group_message'];

        $stmt = $conn->prepare("INSERT INTO group_messages (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $group_message);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        
        $stmt->close(); 
        exit;
    }
}

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
    <script>
        function fetchMessages() {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "./php/fetchMessages.php", true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("messageList").innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        setInterval(fetchMessages, 1000);
    </script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

        <h3>Public Chat</h3>
        <ul id="messageList">
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

        <form id="messageForm" action="home.php" method="post">
            <textarea id="messageInput" name="group_message" rows="4" cols="50" placeholder="Type your message here..." required></textarea>
            <input type="button" id="sendButton" value="Send Message">
        </form>
    </div>
    <div class="sidebar">
        <p><a href="./php/logout.php">Logout</a></p>
    </div>
    <script src="./js/sendGlobal.js"></script>
</body>
</html>
