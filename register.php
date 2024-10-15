<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strife | Register</title>
</head>
<body>
    <section id="register">
        <h2>Welcome to Strife!</h2>
        <p>Please enter your details to Register.</p>
        <hr>
        <form>
            <label for="username">Username: </label>
            <input id="username" type="text" name="username" placeholder="Username">

            <label for="password">Password: </label>
            <input id="password" type="password" name="password" placeholder="Password">

            <label for="confirm-password">Confirm Password: </label>
            <input id="confirm-password" type="password" name="confirm-password" placeholder="Confirm Password">

            <button id="submit-register">Submit</button>
        </form>
        <hr>
        <h3>Already have an account?</h3>
        <p>Log in below!</p>
        <a href="login.php">Login</a>
    </section>
    <script src="./js/register.js"></script>
</body>
</html>