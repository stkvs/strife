document.getElementById("sendButton").addEventListener("click", sendMessage);
document
  .getElementById("messageInput")
  .addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      sendMessage();
    }
  });

function sendMessage() {
  var message = document.getElementById("messageInput").value;
  var fileInput = document.getElementById("fileInput").files[0];

  var formData = new FormData();
  formData.append("group_message", message);

  if (fileInput) {
    formData.append("file", fileInput);
  }

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "./php/sendGlobal.php", true);

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var response = JSON.parse(xhr.responseText);
      if (response.status === "success") {
        document.getElementById("messageInput").value = "";
        document.getElementById("fileInput").value = ""; // Clear the file input
      } else {
        alert("Error: " + response.message);
      }
    }
  };

  xhr.send(formData);
}
