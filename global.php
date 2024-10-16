<?php
session_start();
include './php/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check if the user is an admin
$is_admin = false;
$sql_check_admin = "SELECT * FROM admins WHERE username = ? AND role = 'admin'";
$stmt_check_admin = $conn->prepare($sql_check_admin);
$stmt_check_admin->bind_param("s", $username);
$stmt_check_admin->execute();
$result_check_admin = $stmt_check_admin->get_result();

if ($result_check_admin->num_rows > 0) {
    $is_admin = true;
}

$stmt_check_admin->close();

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

    <?php if ($is_admin): ?>
    <!-- Modal for Admins -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <h2>Admin Panel</h2>
            <p>Welcome, Admin <?php echo htmlspecialchars($username); ?>!</p>
            <hr>
            <h3>Admin Actions</h3>
            <p>Kick a user</p>
            <?php
                // Assuming you already have a connection to the database using $conn

                // Check if the form has been submitted (i.e., if it's a POST request)
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_to_kick'])) {
                    // Sanitize the input
                    $user_to_kick = $_POST['user_to_kick'];

                    // Prepare the SQL query to delete the user from the 'users' table
                    $stmt_kick_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt_kick_user->bind_param("i", $user_to_kick);

                    // Execute the query and check for success
                    if ($stmt_kick_user->execute()) {
                        $message = "User successfully removed.";
                    } else {
                        $message = "Error removing user: " . $conn->error;
                    }

                    // Close the prepared statement
                    $stmt_kick_user->close();
                }
                ?>

                <!-- HTML Form -->
                <form id="kickForm" action="" method="post">
                    <select name="user_to_kick" id="user_to_kick">
                        <?php
                        // Fetch users from the database and populate the dropdown
                        $sql_users = "SELECT id, username FROM users";
                        $result_users = $conn->query($sql_users);

                        if ($result_users->num_rows > 0) {
                            // Loop through the users and create an option element for each one
                            while ($user = $result_users->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($user['id']) . "'>" . htmlspecialchars($user['username']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No users available</option>";
                        }
                        ?>
                    </select>
                    <!-- Submit button -->
                    <input type="submit" id="kickButton" value="Kick User">
                </form>

                <?php
                // Display the message if a user was removed or if there was an error
                if (isset($message)) {
                    echo "<p>$message</p>";
                }
                ?>

        </div>
    </div>
    <script>
        var modal = document.getElementById("adminModal");

        // Open the modal
        window.onload = function() {
            modal.style.display = "block";
        }
    </script>
    <?php endif; ?>

    <script src="./js/sendGlobal.js"></script>
</body>
</html>
