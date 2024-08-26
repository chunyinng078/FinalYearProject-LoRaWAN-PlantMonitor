<?php
// account management page

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';

session_start();

// handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // get form data
    $type = trim($_POST['type']);
    $currentPassword = trim($_POST['currentPassword']);

    // get user
    $user = dbGetUsersByName($_SESSION['username']);

    $message = '';
    $error = false;

    // check if password is correct
    if ($user['password'] == hash('sha256', $currentPassword . $user['salt'])) {
        if ($type == 'email') { // update email
            $email = trim($_POST['email']);
            $email = aesEncrypt($email);
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE username = ?");
            $stmt->bind_param("ss", $email, $_SESSION['username']);
            $stmt->execute();
            $stmt->close();
            $message = 'Email updated';
        } else if ($type == 'password') {   // update password
            $newPassword = trim($_POST['newPassword']);
            $confirmPassword = trim($_POST['confirmPassword']);
            if ($newPassword == $confirmPassword) {
                $hashedPassword = hash('sha256', $newPassword . $user['salt']);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->bind_param("ss", $hashedPassword, $_SESSION['username']);
                $stmt->execute();
                $stmt->close();
                $message = 'Password updated';
            } else {
                $message = 'Passwords do not match';
                $error = true;
            }
        }
    } else {
        $message = 'Incorrect password';
        $error = true;
    }
}

// get email
$stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Account</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include __DIR__ . '/../pages/components/navbar.php'; ?>
    <div class="container">

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

        <!-- title -->
        <div class="row my-3">
            <h1>Account</h1>
        </div>

        <!-- the update email form -->
        <div class="row mb-5">
            <form action="account.php" method="post">
                <h2>Update Email</h2>

                <!-- the email address -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email address:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo aesDecrypt($user['email']); ?>" minlength="5" required>
                </div>


                <!-- hints -->
                <div class="alert alert-info" role="alert">
                    <ul>
                        <li>Email would be sent to the address for verification when user login</li>
                    </ul>
                </div>

                <!-- the current password -->
                <div class="mb-3">
                    <label for="currentPassword" class="form-label">Current Password:</label>
                    <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <input type="hidden" name="type" value="email">
                    <button type="submit" class="btn btn-primary">Update email address</button>
                </div>
            </form>
        </div>

        <!-- the update password form -->
        <div class="row">
            <form action="account.php" method="post">
                <h2>Update Password</h2>

                <!-- the current password -->
                <div class="mb-3">
                    <label for="currentPassword" class="form-label">Current Password:</label>
                    <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                </div>

                <!-- the new password -->
                <div class="mb-3">
                    <label for="newPassword" class="form-label">New Password:</label>
                    <input type="password" class="form-control" id="newPassword" name="newPassword" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$" title="At least 10 chararcter which includes a upper case letter, a lower case letter, a number and a special character" required>
                </div>

                <!-- the confirm password -->
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm Password:</label>
                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$" required>
                </div>

                <!-- hints -->
                <div class="alert alert-info" role="alert">
                    <ul>
                        <li>New password should be at least 10 chararcter which includes a upper case letter, a lower case letter, a number and a special character</li>
                    </ul>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <input type="hidden" name="type" value="password">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
</body>

</html>