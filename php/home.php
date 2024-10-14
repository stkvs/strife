<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html"); 
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

    // Handling private message submission
    if (isset($_POST['private_message'])) {
        $receiver_id = $_POST['receiver_id']; // Get the receiver ID from the form
        $private_message = $_POST['private_message'];

        // Insert the private message with the logged-in user ID
        $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $receiver_id, $private_message);

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

// Fetch private messages
$sql_pm = "SELECT pm.id, u1.username AS sender, u2.username AS receiver, pm.message, pm.sent_at
           FROM private_messages pm
           JOIN users u1 ON pm.sender_id = u1.id
           JOIN users u2 ON pm.receiver_id = u2.id
           WHERE pm.sender_id = '$user_id' OR pm.receiver_id = '$user_id'
           ORDER BY pm.sent_at DESC";
$result_pm = $conn->query($sql_pm);

// Fetch all users for private messaging
$sql_users = "SELECT id, username FROM users WHERE id != '$user_id'";
$result_users = $conn->query($sql_users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
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
    <form method="POST" action="home.php">
        <textarea name="group_message" rows="4" cols="50" placeholder="Type your message here..." required></textarea><br>
        <input type="submit" value="Send Message">
    </form>

    <h3>Your Private Messages</h3>
    <ul>
        <?php
        if ($result_pm->num_rows > 0) {
            while ($message = $result_pm->fetch_assoc()) {
                if ($message['sender'] == $username) {
                    echo "<li><b>You -> " . htmlspecialchars($message['receiver']) . ":</b> " . htmlspecialchars($message['message']) . " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>";
                } else {
                    echo "<li><b>" . htmlspecialchars($message['sender']) . " -> You:</b> " . htmlspecialchars($message['message']) . " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>";
                }
            }
        } else {
            echo "<li>No private messages yet.</li>";
        }
        ?>
    </ul>

    <h3>Send a Private Message</h3>
    <form method="POST" action="home.php">
        <select name="receiver_id" required>
            <option value="">Select a user</option>
            <?php
            if ($result_users->num_rows > 0) {
                while ($user = $result_users->fetch_assoc()) {
                    echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['username']) . "</option>";
                }
            }
            ?>
        </select><br>
        <textarea name="private_message" rows="4" cols="50" placeholder="Type your private message here..." required></textarea><br>
        <input type="submit" value="Send Private Message">
    </form>

    <p><a href="logout.php">Logout</a></p>
</body>
</html>
