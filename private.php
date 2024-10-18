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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageForm = document.getElementById('privateMessageForm');
            const messageInput = messageForm.querySelector('textarea[name="private_message"]');
            const receiverIdInput = messageForm.querySelector('input[name="receiver_id"]');
            const userButtons = document.querySelectorAll('button[name="select_user"]');

            userButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const selectedUserId = this.value;
                    receiverIdInput.value = selectedUserId; // Set the receiver ID in the form
                    fetchPrivateMessages(selectedUserId);
                });
            });

            messageForm.addEventListener('submit', function(event) {
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
                        // alert(data.message); // Show success message
                        fetchPrivateMessages(receiverIdInput.value); // Fetch and display messages again
                        messageInput.value = ''; // Clear the message input
                    } else {
                        alert(data.message); // Show error message
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });

            messageInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    messageForm.dispatchEvent(new Event('submit'));
                }
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
                        messageList.scrollTop = messageList.scrollHeight; // Scroll to the bottom
                    })
                    .catch(error => console.error('Error fetching messages:', error));
            }
        });
    </script>
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