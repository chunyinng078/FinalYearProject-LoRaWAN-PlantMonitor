<?php
// node setting page

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';

session_start();

// check if nodeId is set
if (isset($_GET['nodeId'])) {
    //get node by id
    $node = dbGetNodesById(trim($_GET['nodeId']));

    //get farms
    $farms = dbGetAllFarms();
} else {
    header('location: /pages/nodes.php');
}

// handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // get form data
    $type = trim($_POST['type']);
    $password = trim($_POST['password']);

    // get user
    $user = dbGetUsersByName($_SESSION['username']);

    //check if password is correct
    $message = '';
    $error = false;
    if ($user['password'] == hash('sha256', $password . $user['salt'])) {

        if ($type == 'node') {  // if type is node
            // get form data
            $nodeId = trim($_GET['nodeId']);
            $nodeName = trim($_POST['nodeName']);
            $nodeDescription = trim($_POST['nodeDescription']);
            $farm = trim($_POST['farm']);
            $notificationEmail = trim($_POST['notificationEmail']);

            // encrypt data
            $nodeName = aesEncrypt($nodeName);
            $nodeDescription = aesEncrypt($nodeDescription);
            $notificationEmail = aesEncrypt($notificationEmail);

            // get nodes
            $nodes = dbGetAllNodes();

            // check if node name exist except for the current node
            $nodeNameExist = false;
            foreach ($nodes as $n) {
                if (aesDecrypt($n['name']) == aesDecrypt($nodeName) && $n['id'] != $nodeId) {
                    $nodeNameExist = true;
                    break;
                }
            }

            // check if node exist
            if ($nodeNameExist) {   // node name exist
                $message = 'Node Name Exist';
                $error = true;
            } else {    // node name does not exist, update data to database

                // check if the farm is changed in form
                if ($node['farm_id'] != $farm) {
                    // delete all the related record
                    $conn->query("SET FOREIGN_KEY_CHECKS=0");
                    $stmt = $conn->prepare("DELETE FROM sensors_data WHERE node_id = ?");
                    $stmt->bind_param("s", $nodeId);
                    $stmt->execute();
                    $stmt->close();
                    $conn->query("SET FOREIGN_KEY_CHECKS=1");
                }

                $conn->query("SET FOREIGN_KEY_CHECKS=0");
                $stmt = $conn->prepare(" UPDATE nodes SET name = ?, description = ?, farm_id = ?, notification_email = ? WHERE id = ?");
                $stmt->bind_param("sssss", $nodeName, $nodeDescription, $farm, $notificationEmail, $nodeId);
                $stmt->execute();
                $stmt->close();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $message = 'Node Updated';
            }
        } elseif ($type == 'delete') {  // if type is delete

            // delete all the related record
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            $stmt = $conn->prepare("DELETE FROM sensors_data WHERE node_id = ?");
            $stmt->bind_param("s", trim($_GET['nodeId']));
            $stmt->execute();
            $stmt->close();
            $conn->query("SET FOREIGN_KEY_CHECKS=1");

            // delete the node
            dbDeleteNodeById(trim($_GET['nodeId']));
            header('location: /pages/nodes.php?delete=success');
        }

        //update the $node
        $node = dbGetNodesById(trim($_GET['nodeId']));
    } else {
        $message = 'Incorrect password';
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Node Setting</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <script>
        // email validation to check if the email is valid which is email or emails separated by ','
        function emailValidation() {
            var email = document.getElementById('notificationEmail').value;
            var regex = new RegExp('^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Z|a-z]{2,}(,[ ]?[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Z|a-z]{2,})*$', 'i');
            if (!email.match(regex)) {
                alert('Invalid email');
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <?php include __DIR__ . '/../pages/components/navbar.php'; ?>

    <div class="container">

        <!-- back button -->
        <div class="row my-3">
            <div class="col">
                <a href="/pages/nodes.php" class="btn btn-primary">Back</a>
            </div>
        </div>

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
        <div class="row mb-3">
            <h1>Node Setting</h1>
        </div>

        <!-- update node name form -->
        <div class="row mb-5">
            <form action="nodeSetting.php?nodeId=<?php echo $_GET['nodeId']; ?>" method="post" onsubmit="return emailValidation();">
                <h2>Update Node</h2>

                <!-- node name -->
                <div class="col mb-3">
                    <label for="nodeName" class="form-label">Node Name:</label>
                    <input type="text" class="form-control" id="nodeName" name="nodeName" value="<?php echo aesDecrypt($node['name']); ?>" minlength="5" required>
                </div>

                <!-- node description -->
                <div class="col mb-3">
                    <label for="nodeDescription" class="form-label">Node Description:</label>
                    <input type="text" class="form-control" id="nodeDescription" name="nodeDescription" value="<?php echo aesDecrypt($node['description']); ?>" minlength="5" required>
                </div>

                <!-- select farm -->
                <div class="col mb-3">
                    <label for="farm" class="form-label">Farm:</label>
                    <select class="form-select" id="farm" name="farm" required>
                        <?php
                        foreach ($farms as $farm) { ?>
                            <option value="<?php echo $farm['id']; ?>" <?php if ($node['farm_id'] == $farm['id']) {
                                                                            echo 'selected';
                                                                        } ?>><?php echo aesDecrypt($farm['name']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <!-- hints -->
                <div class="alert alert-danger" role="alert">
                    <ul class="text-start">
                        <li>If you change the farm, the related sensor data/records would be deleted</li>
                        <li>If you do so, you were suggested to backup the data first</li>
                    </ul>
                </div>

                <!-- notification email -->
                <div class="col mb-3">
                    <label for="notificationEmail" class="form-label">Notification Email:</label>
                    <input type="text" class="form-control" id="notificationEmail" name="notificationEmail" value="<?php echo aesDecrypt($node['notification_email']); ?>" minlength="5" required>
                </div>

                <!-- hints -->
                <div class="alert alert-info" role="alert">
                    <ul>
                        <li>If you have multiple email separate it with ',', e.g. a@a.com,b@b.com</li>
                        <li>Email would be sent to the address(es) when the plant's environment exceeds thresholds</li>
                    </ul>
                </div>

                <!-- current password -->
                <div class="col mb-3">
                    <label for="password" class="form-label">Current Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <input type="hidden" name="type" value="node">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>

        <!-- delete record -->
        <div class="row mb-5">
            <form action="nodeSetting.php?nodeId=<?php echo $_GET['nodeId']; ?>" method="post">

                <!-- title -->
                <h2>Delete node</h2>

                <!-- current password -->
                <div class="row">
                    <div class="col mb-3">
                        <label for="password" class="form-label">Current Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <input type="hidden" name="nodeId" value="<?php echo $_GET['nodeId']; ?>">
                    <input type="hidden" name="type" value="delete">
                    <!-- hints -->
                    <div class="alert alert-danger" role="alert">
                        <ul class="text-start">
                            <li>You were suggested to back up the data first before you delete</li>
                            <li>If you delete the node, the related sensor data/records would be deleted</li>
                        </ul>
                    </div>
                    <button type="submit" class="btn btn-danger">Delete Node</button>
                </div>

            </form>
        </div>
    </div>
</body>

</html>