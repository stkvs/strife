document.addEventListener('DOMContentLoaded', function() {
    const submitButton = document.querySelector('#submit-login');

    submitButton.addEventListener('click', (event) => {
        event.preventDefault(); // Prevent form submission

        let username = document.querySelector('#username').value;
        let password = document.querySelector('#password').value;

        fetch('./php/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('Invalid credentials!') || data.includes('User not found!')) {
                alert(data);
            } else {
                window.location.href = './global.php';
            }
        })
        .catch(error => console.error('Error:', error));
    });
    console.log('DOM fully loaded and parsed');
});