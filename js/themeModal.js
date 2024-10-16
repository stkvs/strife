const button = document.querySelector('.settings');

button.addEventListener('click', () => {
    const modal = document.createElement('div');
    modal.classList.add('settingsModal');
    
    modal.innerHTML = `
        <div class="theme-options">
            <label for="background">Background Color:</label>
            <input type="color" id="background" name="background">
            
            <label for="sidebar">Sidebar Color:</label>
            <input type="color" id="sidebar" name="sidebar">
            
            <label for="buttons">Buttons Color:</label>
            <input type="color" id="buttons" name="buttons">
            
            <label for="mention">Mention Color:</label>
            <input type="color" id="mention" name="mention">
            
            <label for="mentionborder">Mention Border Color:</label>
            <input type="color" id="mentionborder" name="mentionborder">
            
            <button id="applyTheme">Apply Theme</button>
        </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('applyTheme').addEventListener('click', () => {
        const background = document.getElementById('background').value;
        const sidebar = document.getElementById('sidebar').value;
        const buttons = document.getElementById('buttons').value;
        const mention = document.getElementById('mention').value;
        const mentionborder = document.getElementById('mentionborder').value;

        document.body.style.backgroundColor = background;
        document.querySelector('.sidebar').style.backgroundColor = sidebar;
        document.querySelectorAll('input[type=button]').forEach(button => button.style.backgroundColor = buttons);
        document.querySelectorAll('.mention').forEach(mention => mention.style.backgroundColor = mention);
        document.querySelectorAll('.highlight').forEach(mention => mention.style.borderColor = mentionborder);
    });
});
