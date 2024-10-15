document.getElementById("sendButton").addEventListener("click", function () {
  var message = document.getElementById("messageInput").value;

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "./php/sendGlobal.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var response = JSON.parse(xhr.responseText);
      if (response.status === "success") {
        document.getElementById("messageInput").value = "";
      } else {
        alert("Error: " + response.message);
      }
    }
  };

  xhr.send("group_message=" + encodeURIComponent(message));
});
