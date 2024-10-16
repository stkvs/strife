<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strife | Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <section id="login">
        <h2>Welcome to Strife!</h2>
        <p>Please enter your details to log in.</p>
        <hr>
        <form>
            <label for="username">Username: </label>
            <input id="username" type="text" name="username" placeholder="Username" required pattern="^\S+$" title="Username cannot contain spaces">

            <label for="password">Password: </label>
            <input id="password" type="password" name="password" placeholder="Password" required>

            <button id="submit-login" type="submit">Submit</button>
        </form>
        <hr>
        <h3>Don't have an account?</h3>
        <p>Create one here!</p>
        <a href="./register.php">Register</a>
    </section>
    <script src="./js/login.js"></script>
</body>
</html>
