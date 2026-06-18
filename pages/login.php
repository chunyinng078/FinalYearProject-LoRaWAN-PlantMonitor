<?php
// handle login

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mail/mail.php';
require_once __DIR__ . '/../auth/aes.php';

// check if logged in
session_start();
if (isset($_SESSION['verified']) && $_SESSION['verified'] == 'verified') {
    header('location: ./liveView.php');
}

// handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // get form data
    $username = trim($_POST['username']);

    // get salt
    $stmt = $conn->prepare("SELECT salt FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $salt = $result->fetch_assoc();
    $stmt->close();

    // hash password
    $password = hash('sha256', trim($_POST['password']) . $salt['salt']);

    // get user
    // $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    // $stmt->bind_param("ss", $username, $password);
    $stmt = $conn->prepare("SELECT * FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // if the user exist and the password is correct
    // if ($user) {
    if (true) {

        // set session
        session_start();
        $_SESSION['verified'] = 'false';
        $_SESSION['username'] = $user['username'];

        // generate a random number and send it to the user's email
        $randomNumber = rand(100000, 999999);
        sendEmail('Your verification code for lorawan plant monitor', 'The code is <b>' . $randomNumber . '</b>', aesDecrypt($user['email']), false);
        $randomNumber = hash('sha256', $randomNumber . $user['salt']);

        // update the verification code in the database
        $stmt = $conn->prepare("UPDATE users SET verification_code = ? WHERE username = ?");
        $stmt->bind_param("ss", $randomNumber, $username);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        header('location: ./verification.php');
    } else {
        $message = 'Invalid username or password';
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <!-- center the form -->
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">

        <!-- login form -->
        <div class="col-md-6">
            <form action="login.php" method="post">

                <!-- show message -->
                <div class="row">
                    <?php
                    if (isset($message) && $message != '') {
                        if ($error) {
                            echo '<div class="alert alert-danger" role="alert">' . $message . '</div>';
                        } else {
                            echo '<div class="alert alert-primary" role="alert">' . $message . '</div>';
                        }
                    }
                    ?>
                </div>

                <div class="my-3 text-decoration-underline">
                    <h1>Welcome to the </h1>
                    <h1>LoRaWAN Plant Montior System</h1>
                </div>

                <!-- title -->
                <h2>Login</h2>


                <!-- username -->
                <div class="row mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" disabled>
                </div>

                <!-- password -->
                <div class="row mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" disabled>
                </div>

                <!-- submit form -->
                <div class="row text-end">
                    <button type="submit" class="btn btn-primary">Login</button>
                    <!-- hint to show that no need to enter form -->
                    <p class="text-center mt-2">Just click the Login button</p>
                    <div class="text-center">
                        <a href="https://www.youtube.com/" target="_blank" class="me-3">Demo Video 1</a>
                        <a href="https://www.youtube.com/" target="_blank">Demo Video 2</a>
                    </div>
                </div>

            </form>
        </div>
    </div>
</body>

</html>
