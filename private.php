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

// Handle selected user for private messaging
$selected_user_id = null;
$private_messages = [];

if (isset($_POST['select_user'])) {
    $selected_user_id = $_POST['select_user'];
    // Fetch messages between users
    $sql_private_messages = "
        SELECT pm.message, u.username AS sender, pm.sent_at
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE (pm.sender_id = ? AND pm.receiver_id = ?)
        OR (pm.sender_id = ? AND pm.receiver_id = ?)
        ORDER BY pm.sent_at ASC";
    $stmt_private_messages = $conn->prepare($sql_private_messages);
    $stmt_private_messages->bind_param("iiii", $user_id, $selected_user_id, $selected_user_id, $user_id);
    $stmt_private_messages->execute();
    $result_private_messages = $stmt_private_messages->get_result();
    
    while ($row = $result_private_messages->fetch_assoc()) {
        $private_messages[] = $row;
    }
    $stmt_private_messages->close();
}

$stmt_users->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Private Messages</title>
    <link rel="stylesheet" href="style.css">
    <script src="./js/themeModal.js"></script>
    <script>
        document.getElementById('privateMessageForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            const formData = new FormData(this);
            console.log('Sending data:', Object.fromEntries(formData)); // Log the form data being sent

            fetch('./php/send_private_message.php', {
                method: 'POST',
                body: formData // Send the form data
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data); // Log the response from the server
                if (data.status === 'success') {
                    alert(data.message); // Show success message
                    // Optionally, you can fetch and display messages again if needed.
                } else {
                    alert(data.message); // Show error message
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });


        function fetchPrivateMessages(selectedUserId) {
            fetch(`./php/fetch_private_messages.php?user_id=${selectedUserId}`)
                .then(response => response.json())
                .then(data => {
                    const messageList = document.getElementById('messageList');
                    messageList.innerHTML = ''; // Clear existing messages
                    data.forEach(message => {
                        const li = document.createElement('li');
                        li.innerHTML = `<b>${message.sender}:</b> ${message.message} <i>(${message.sent_at})</i>`;
                        messageList.appendChild(li);
                    });
                })
                .catch(error => console.error('Error fetching messages:', error));
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

        <div class="messages">
            <h3>Your Private Messages</h3>
            <ul id="messageList">
                <?php
                if (count($private_messages) > 0) {
                    foreach ($private_messages as $message) {
                        echo "<li><b>" . htmlspecialchars($message['sender']) . ":</b> " . htmlspecialchars($message['message']) . " <i>(" . htmlspecialchars($message['sent_at']) . ")</i></li>";
                    }
                } else {
                    echo "<li>No private messages yet.</li>";
                }
                ?>
            </ul>
            <form id="privateMessageForm" enctype="multipart/form-data">
                <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($selected_user_id); ?>"> <!-- Replace with actual user ID -->
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
                    echo '<form method="post" action="" style="display: inline;">
                            <button type="submit" name="select_user" value="' . htmlspecialchars($user['id']) . '">' . htmlspecialchars($user['username']) . '</button>
                          </form>';
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
