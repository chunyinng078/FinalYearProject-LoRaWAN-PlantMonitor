<?php
// nodes management page

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';

session_start();

// handle form submission
$message = '';
$error = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // get form data
    $nodeId = trim($_POST['nodeId']);
    $nodeName = trim($_POST['nodeName']);
    $nodeDescription = trim($_POST['nodeDescription']);
    $notificationEmail = trim($_POST['notificationEmail']);

    // encrypt data
    $nodeName = aesEncrypt($nodeName);
    $nodeDescription = aesEncrypt($nodeDescription);
    $notificationEmail = aesEncrypt($notificationEmail);


    // get nodes
    $nodes = dbGetAllNodes();

    // check if node id exist
    $node = false;
    foreach ($nodes as $n) {
        if ($n['id'] == $nodeId) {
            $node = true;
            break;
        }
    }

    // check if node name exist
    $nodeNameExist = false;
    foreach ($nodes as $n) {
        if (aesDecrypt($n['name']) == aesDecrypt($nodeName)) {
            $nodeNameExist = true;
            break;
        }
    }

    // check if node exist
    if ($node) {    // if node exist
        $message = 'Node Id exist';
        $error = true;
    } elseif ($nodeNameExist) {    // if node name exist
        $message = 'Node Name exist';
        $error = true;
    } else {    // node does not exist, insert data to database
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $stmt = $conn->prepare("INSERT INTO nodes (id, name, description, notification_email, farm_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nodeId, $nodeName, $nodeDescription, $notificationEmail, $_POST['farm']);
        $stmt->execute();
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $message = 'Node created';
    }
}

// set success message if node is deleted
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['delete']) && $_GET['delete'] == 'success') {
        $message = 'Node deleted';
    }
}

// get all nodes and farms
$nodes = dbGetAllNodes();
$farms = dbGetAllFarms();

// link farm name to node
foreach ($nodes as $key => $node) {
    foreach ($farms as $farm) {
        if ($node['farm_id'] == $farm['id']) {
            $nodes[$key]['farm'] = $farm['name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Nodes</title>
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
        <div class="row my-3">
            <h1>Nodes</h1>
        </div>

        <!-- create node form -->
        <div class="row mb-5">
            <form action="nodes.php" method="post" onsubmit="return emailValidation();">

                <!-- title -->
                <h2>Create Node</h2>

                <!-- node id -->
                <div class="col mb-3">
                    <label for="nodeId" class="form-label">Node Id:</label>
                    <input placeholder="e.g., eui-1234567" type="text" class="form-control" id="nodeId" name="nodeId" minlength="5" required>
                </div>

                <!-- hints -->
                <div class="alert alert-info" role="alert">
                    <ul>
                        <li>You also have to register the node on the The Things Stack</li>
                        <li>The id would be same as the id on the The Things Stack</li>
                    </ul>
                </div>


                <!-- node name -->
                <div class="col mb-3">
                    <label for="nodeName" class="form-label">Node Name:</label>
                    <input placeholder="e.g., west-banana-farm-01" type="text" class="form-control" id="nodeName" name="nodeName" minlength="5" required>
                </div>

                <!-- node description -->
                <div class="col mb-3">
                    <label for="nodeDescription" class="form-label">Node Description:</label>
                    <input placeholder="e.g., place in middle of the farm" type="text" class="form-control" id="nodeDescription" name="nodeDescription" minlength="5" required>
                </div>

                <!-- farm -->
                <div class="col mb-3">
                    <label for="farm" class="form-label">Farm:</label>
                    <select class="form-select" id="farm" name="farm" required>
                        <?php foreach ($farms as $farm) { ?>
                            <option value="<?php echo $farm['id']; ?>"><?php echo aesDecrypt($farm['name']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <!-- notification email address -->
                <div class="col mb-3">
                    <label for="notificationEmail" class="form-label">Notification Email:</label>
                    <input placeholder="e.g., name@domain.com" type="text" class="form-control" id="notificationEmail" name="notificationEmail" minlength="5" required>
                </div>

                <!-- hints -->
                <div class="alert alert-info" role="alert">
                    <ul>
                        <li>If you have multiple email separate it with ',', e.g. a@a.com,b@b.com</li>
                        <li>Email would be sent to the address(es) when the plant's environment exceeds thresholds</li>
                    </ul>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <button type="submit" class="btn btn-primary">Create new node</button>
                </div>
            </form>
        </div>

        <!-- list all nodes  -->
        <div class="row mb-5">

            <!-- title -->
            <h2>Nodes list</h2>

            <!-- table -->
            <div class="col">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">Node ID</th>
                            <th scope="col">Node Name</th>
                            <th scope="col">Node Description</th>
                            <th scope="col">Farm</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // print all nodes
                        foreach ($nodes as $node) { ?>
                            <tr>
                                <td><?php echo $node['id']; ?></td>
                                <td><?php echo aesDecrypt($node['name']); ?></td>
                                <td><?php echo aesDecrypt($node['description']); ?></td>
                                <td><?php echo aesDecrypt($node['farm']); ?></td>
                                <!-- button to update -->
                                <td><a href="nodeSetting.php?nodeId=<?php echo $node['id']; ?>" class="btn btn-primary">Update</a></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
</body>

</html>