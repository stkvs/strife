<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$group_id = 1;

$stmt_check_group = $conn->prepare("SELECT id FROM groups WHERE id = ?");
$stmt_check_group->bind_param("i", $group_id);
$stmt_check_group->execute();
$result_check_group = $stmt_check_group->get_result();

// Handle sending a new group message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['group_message'])) {
    if ($result_check_group->num_rows > 0) {
        $group_message = $_POST['group_message'];

        $stmt = $conn->prepare("INSERT INTO group_messages (user_id, group_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $group_id, $group_message);

        if ($stmt->execute()) {
            header("Location: home.php"); 
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "Group does not exist.";
    }
}

$sql_group_messages = "SELECT gm.message, u.username, gm.sent_at
                       FROM group_messages gm
                       JOIN users u ON gm.user_id = u.id
                       WHERE gm.group_id = $group_id
                       ORDER BY gm.sent_at DESC";
$result_group_messages = $conn->query($sql_group_messages);

$sql_pm = "SELECT pm.id, u1.username AS sender, u2.username AS receiver, pm.message, pm.sent_at
           FROM private_messages pm
           JOIN users u1 ON pm.sender_id = u1.id
           JOIN users u2 ON pm.receiver_id = u2.id
           WHERE pm.sender_id = '$user_id' OR pm.receiver_id = '$user_id'
           ORDER BY pm.sent_at DESC";
$result_pm = $conn->query($sql_pm);

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
    <h2>Welcome, <?php echo $username; ?>!</h2>

    <h3>Public Group Messages</h3>
    <ul>
        <?php
        if ($result_group_messages->num_rows > 0) {
            while ($message = $result_group_messages->fetch_assoc()) {
                echo "<li><b>" . $message['username'] . ":</b> " . $message['message'] . " <i>(" . $message['sent_at'] . ")</i></li>";
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
                    echo "<li><b>You -> " . $message['receiver'] . ":</b> " . $message['message'] . " <i>(" . $message['sent_at'] . ")</i></li>";
                } else {
                    echo "<li><b>" . $message['sender'] . " -> You:</b> " . $message['message'] . " <i>(" . $message['sent_at'] . ")</i></li>";
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
                    echo "<option value='" . $user['id'] . "'>" . $user['username'] . "</option>";
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
