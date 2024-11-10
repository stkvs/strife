document.addEventListener('DOMContentLoaded', function() {
    const messageForm = document.getElementById('privateMessageForm');
    const messageInput = messageForm.querySelector('textarea[name="private_message"]');
    const receiverIdInput = messageForm.querySelector('input[name="receiver_id"]');
    const fileInput = messageForm.querySelector('input[name="file"]');
    const userButtons = document.querySelectorAll('button[name="select_user"]');
    let lastTimestamp = '1970-01-01 00:00:00';

    userButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const selectedUserId = this.value;
            receiverIdInput.value = selectedUserId; 
            lastTimestamp = '1970-01-01 00:00:00'; // Reset timestamp when a new user is selected
            fetchPrivateMessages(selectedUserId);
            startMessagePolling(selectedUserId);
        });
    });

    messageForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        console.log('Sending data:', Object.fromEntries(formData));

        fetch('./php/send_private_message.php', {
            method: 'POST',
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (data.status === 'success') {
                fetchPrivateMessages(receiverIdInput.value);
                messageInput.value = '';
                fileInput.value = ''; 
            } else {
                alert(data.message); 
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
        var xhr = new XMLHttpRequest();
        xhr.open("GET", `./php/fetch_private_messages.php?user_id=${selectedUserId}&last_timestamp=${encodeURIComponent(lastTimestamp)}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const messages = response.messages;
                lastTimestamp = response.latest_timestamp;

                const messageList = document.getElementById("messageList");

                messages.forEach(msg => {
                    const newMessage = document.createElement("li");
                    newMessage.innerHTML = msg;
                    messageList.appendChild(newMessage);
                });
                messageList.scrollTop = messageList.scrollHeight;
            }
        };
        xhr.send();
    }

    function startMessagePolling(selectedUserId) {
        if (window.messagePollingInterval) {
            clearInterval(window.messagePollingInterval);
        }
        window.messagePollingInterval = setInterval(() => {
            fetchPrivateMessages(selectedUserId);
        }, 1000); // Poll every 1 second
    }
});