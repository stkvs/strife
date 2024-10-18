let lastTimestamp = '1970-01-01 00:00:00';

function fetchMessages() {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", `./php/fetchMessages.php?last_timestamp=${encodeURIComponent(lastTimestamp)}`, true);
    xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        const messages = response.messages;
        lastTimestamp = response.latest_timestamp;

        const messageList = document.getElementById("messageList");

        messages.forEach(msg => {
        const newMessage = document.createElement("li");
        newMessage.innerHTML = msg;
        messageList.insertBefore(newMessage, messageList.firstChild);
        });
    }
    };
    xhr.send();
}

setInterval(fetchMessages, 1000);