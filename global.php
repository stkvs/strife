<?php
session_start();
include './php/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$is_admin = false;
$is_mod = false;

$sql_check_roles = "SELECT role FROM admins WHERE username = ?";
$stmt_check_roles = $conn->prepare($sql_check_roles);
$stmt_check_roles->bind_param("s", $username);
$stmt_check_roles->execute();
$result_check_roles = $stmt_check_roles->get_result();

while ($row = $result_check_roles->fetch_assoc()) {
    if ($row['role'] == 'admin') {
        $is_admin = true;
    } elseif ($row['role'] == 'mod') {
        $is_mod = true;
    }
}

$stmt_check_roles->close();

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
    <script src="./js/globalFetch.js"></script>
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
                    echo "<li><b>" . htmlspecialchars($message['username']) . ":</b> ";
                    
                    // Check if it's a text message
                    if (!empty($message['message'])) {
                        echo htmlspecialchars($message['message']);
                    }

                    // Check if it's a file (image, audio, video)
                    if (!empty($message['file_path'])) {
                        $file_type = mime_content_type($message['file_path']);
                        if (strpos($file_type, 'image') !== false) {
                            echo "<br><img src='" . htmlspecialchars($message['file_path']) . "' alt='image' style='max-width: 200px;'/>";
                        } elseif (strpos($file_type, 'audio') !== false) {
                            echo "<br><audio controls><source src='" . htmlspecialchars($message['file_path']) . "' type='" . htmlspecialchars($file_type) . "'></audio>";
                        } elseif (strpos($file_type, 'video') !== false) {
                            echo "<br><video controls style='max-width: 200px;'><source src='" . htmlspecialchars($message['file_path']) . "' type='" . htmlspecialchars($file_type) . "'></video>";
                        }
                    }

                    echo " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>";
                }
            } else {
                echo "<li>No messages in the public group yet.</li>";
            }
            ?>
        </ul>


        <form id="messageForm" action="home.php" method="post" enctype="multipart/form-data">
            <textarea id="messageInput" name="group_message" rows="4" cols="50" placeholder="Type your message here..."></textarea>
            <input type="file" id="fileInput" name="file" accept="image/*,audio/*,video/*">
            <input type="button" id="sendButton" value="Send Message">
        </form>

    </div>
    <div class="sidebar">
        <p><a href="private.php">Private Messages</a></p>
        <p><a href="./php/logout.php">Logout</a></p>
        <button class="settings">ads</button>
    </div>

    <script src="./js/themeModal.js"></script>

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
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_to_kick'])) {
                    $user_to_kick = $_POST['user_to_kick'];

                    $stmt_kick_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt_kick_user->bind_param("i", $user_to_kick);

                    $conn->query("SET FOREIGN_KEY_CHECKS=0");

                    if ($stmt_kick_user->execute()) {
                        $message = "User successfully removed.";
                    } else {
                        $message = "Error removing user: " . $conn->error;
                    }

                    $conn->query("SET FOREIGN_KEY_CHECKS=1");

                    $stmt_kick_user->close();
                }
                ?>

                <form id="kickForm" action="" method="post">
                    <select name="user_to_kick" id="user_to_kick">
                        <?php
                        $sql_users = "SELECT id, username FROM users";
                        $result_users = $conn->query($sql_users);

                        if ($result_users->num_rows > 0) {
                            while ($user = $result_users->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($user['id']) . "'>" . htmlspecialchars($user['username']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No users available</option>";
                        }
                        ?>
                    </select>
                    <input type="submit" id="kickButton" value="Kick User">
                </form>

                <?php   
                if (isset($message)) {
                    echo "<p>$message</p>";
                }
                ?>

        </div>
    </div>
    <script>
        var modal = document.getElementById("adminModal");

        window.onload = function() {
            modal.style.display = "block";
        }
    </script>
    <script src="./js/pushChatScroll.js"></script>
    <?php endif; ?>

    <?php if ($is_mod && !$is_admin): ?>
    <!-- Modal for Mods -->
    <div id="modModal" class="modal">
        <div class="modal-content">
            <h2>Mod Panel</h2>
            <p>Welcome, Mod <?php echo htmlspecialchars($username); ?>!</p>
            <hr>
            <h3>Mod Actions</h3>
            <p>Kick a user</p>
            <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_to_kick'])) {
                    $user_to_kick = $_POST['user_to_kick'];

                    // Check if the user to kick is an admin
                    $stmt_check_admin = $conn->prepare("SELECT role FROM admins WHERE username = (SELECT username FROM users WHERE id = ?)");
                    $stmt_check_admin->bind_param("i", $user_to_kick);
                    $stmt_check_admin->execute();
                    $result_check_admin = $stmt_check_admin->get_result();

                    if ($result_check_admin->num_rows > 0) {
                        $row = $result_check_admin->fetch_assoc();
                        if ($row['role'] == 'admin') {
                            $message = "You cannot kick an admin.";
                        } else {
                            $stmt_kick_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                            $stmt_kick_user->bind_param("i", $user_to_kick);

                            $conn->query("SET FOREIGN_KEY_CHECKS=0");

                            if ($stmt_kick_user->execute()) {
                                $message = "User successfully removed.";
                            } else {
                                $message = "Error removing user: " . $conn->error;
                            }

                            $conn->query("SET FOREIGN_KEY_CHECKS=1");

                            $stmt_kick_user->close();
                        }
                    } else {
                        // User is not an admin, proceed to kick
                        $stmt_kick_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                        $stmt_kick_user->bind_param("i", $user_to_kick);

                        $conn->query("SET FOREIGN_KEY_CHECKS=0");

                        if ($stmt_kick_user->execute()) {
                            $message = "User successfully removed.";
                        } else {
                            $message = "Error removing user: " . $conn->error;
                        }

                        $conn->query("SET FOREIGN_KEY_CHECKS=1");

                        $stmt_kick_user->close();
                    }

                    $stmt_check_admin->close();
                }
            ?>

            <form id="kickForm" action="" method="post">
                <select name="user_to_kick" id="user_to_kick">
                    <?php
                    $sql_users = "SELECT id, username FROM users";
                    $result_users = $conn->query($sql_users);

                    if ($result_users->num_rows > 0) {
                        while ($user = $result_users->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($user['id']) . "'>" . htmlspecialchars($user['username']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>No users available</option>";
                    }
                    ?>
                </select>
                <input type="submit" id="kickButton" value="Kick User">
            </form>

            <?php   
            if (isset($message)) {
                echo "<p>$message</p>";
            }
            ?>

        </div>
    </div>
    <script>
        var modal = document.getElementById("modModal");

        window.onload = function() {
            modal.style.display = "block";
        }
    </script>
    <?php endif; ?>

    <script src="./js/sendGlobal.js"></script>
</body>
</html>
