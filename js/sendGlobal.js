document.getElementById("sendButton").addEventListener("click", sendMessage);
document
  .getElementById("messageInput")
  .addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      sendMessage();
    }
  });

function sendMessage() {
  const chat = document.querySelector('#messageList');
    if (chat) {
        const observer = new MutationObserver(function() {
            chat.scrollTop = chat.scrollHeight;
        });

        observer.observe(chat, { childList: true });

        // Initial scroll to bottom in case the list is already populated
        chat.scrollTop = chat.scrollHeight;
    }
    
  var message = document.getElementById("messageInput").value.trim();
  var fileInput = document.getElementById("fileInput").files[0];

  // Basic validation
  if (!message && !fileInput) {
    alert("Please enter a message or select a file.");
    return;
  }

  var formData = new FormData();
  formData.append("group_message", message);

  if (fileInput) {
    formData.append("file", fileInput);
  }

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "./php/sendGlobal.php", true);

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.status === "success") {
          document.getElementById("messageInput").value = "";
          document.getElementById("fileInput").value = "";
        } else {
          alert("Error: " + response.message);
        }
      } else {
        alert("Error: Unable to send message.");
      }
    }
  };

  xhr.send(formData);
}
