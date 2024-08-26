<?php
// 2fa email verification page

require_once __DIR__ . '/../database/dbConnection.php';

session_start();

// check if user is logged in
if (!isset($_SESSION['verified'])) {
    header('location: ./login.php');
    exit;
}
if ($_SESSION['verified'] == 'verified') {
    header('location: ./liveView.php');
    exit;
}

// handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //get salt of user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // get form data
    $v_code = trim($_POST['v_code']);
    $username = $user['username'];
    $v_code = hash('sha256', $v_code . $user['salt']);

    // check if verification code is correct
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND verification_code = ?");
    $stmt->bind_param("ss", $username, $v_code);
    $stmt->execute();
    $result = $stmt->get_result();

    // if ($result->fetch_assoc()) {   // if verification code is correct
    if (true) {
        $_SESSION['verified'] = 'verified';
        header('location: ./liveView.php');
    } else {    // if verification code is incorrect
        $message = 'Invalid verification code';
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Two step verification</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <!-- center the form -->
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="col-md-6 ">
            <form action="verification.php" method="post">
                
                <!-- message -->
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

                <!-- title -->
                <h1>Two step verification</h1>

                <!-- verification code -->
                <div class="mb-3">
                    <label for="v_code" class="form-label">Enter the verification code that sent to your email:</label>
                    <input type="text" class="form-control" id="v_code" name="v_code" disabled>
                </div>

                <!-- submit form -->
                <div class="row text-end">
                    <button type="submit" class="btn btn-primary">Verify</button>
                    <p class="text-center mt-2">Just click the Verify button</p>
                </div>
            </form>
        </div>
</body>

</html>