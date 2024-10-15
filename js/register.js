document.addEventListener('DOMContentLoaded', function() {
    const submitButton = document.querySelector('#submit-register');

    submitButton.addEventListener('click', (event) => {
        event.preventDefault(); // Prevent form submission

        let username = document.querySelector('#username').value;
        let password = document.querySelector('#password').value;
        let confirm_password = document.querySelector('#confirm-password').value;

        if (password === confirm_password) {
            fetch('./php/register.php', {
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
        } else {
            alert('Incorrect Credentials', 'Please enter the right details.');
        }
    });
    console.log('DOM fully loaded and parsed');
});