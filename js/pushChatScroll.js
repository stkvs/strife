document.addEventListener('DOMContentLoaded', function() {
    const chat = document.querySelector('#messageList');
    if (chat) {
        const observer = new MutationObserver(function() {
            chat.scrollTop = chat.scrollHeight;
        });

        observer.observe(chat, { childList: true });

        // Initial scroll to bottom in case the list is already populated
        chat.scrollTop = chat.scrollHeight;
    }
});