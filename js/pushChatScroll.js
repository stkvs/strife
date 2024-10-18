document.addEventListener('DOMContentLoaded', function() {
    const chat = document.querySelector('#messageList');
    if (chat) {
        setTimeout(function() {
            chat.scrollTop = chat.scrollHeight;
        }, 2000); 
    }
});