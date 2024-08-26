<?php
// farm setting page

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';

session_start();

// check if farmid is set
if (isset($_GET['farmId'])) {
    //get single farm
    $farm = dbGetFarmsById(trim($_GET['farmId']));
} else {
    header('location: /pages/farms.php');
}

// handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $type = trim($_POST['type']);
    $password = trim($_POST['password']);

    // get user
    $user = dbGetUsersByName($_SESSION['username']);

    $message = '';
    $error = false;

    // check if password is correct
    if ($user['password'] == hash('sha256', $password . $user['salt'])) {

        if ($type == 'farm') { // if type is farm
            $farmId = trim($_GET['farmId']);
            $farmName = trim($_POST['farmName']);
            $farmDescription = trim($_POST['farmDescription']);
            $plantId = trim($_POST['plantId']);

            $farmName = aesEncrypt($farmName);
            $farmDescription = aesEncrypt($farmDescription);

            //select all farm
            $farms = dbGetAllFarms();

            //check if farm exist and check if record is not itself
            $farmNameExist = false;
            foreach ($farms as $f) {
                if (aesDecrypt($f['name']) == aesDecrypt($farmName) && $f['id'] != $farmId) {
                    $farmNameExist = true;
                    break;
                }
            }

            // check if farm exist
            if ($farmNameExist) {
                $message = 'Farm name exist';
                $error = true;
            } else {    // farm name does not exist, update data to database
                $conn->query("SET FOREIGN_KEY_CHECKS=0");
                $stmt = $conn->prepare("UPDATE farms SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("sss", $farmName, $farmDescription, $farmId);
                $stmt->execute();
                $stmt->close();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");

                $message = 'Farm details updated';
            }
        } elseif ($type == 'delete') {  // if type is delete

            //check if farm has nodes
            $stmt = $conn->prepare("SELECT * FROM nodes WHERE farm_id = ?");
            $stmt->bind_param("i", trim($_POST['farmId']));
            $stmt->execute();
            $result = $stmt->get_result();
            $nodes = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if ($nodes) {   // farm has nodes
                // prepare error message
                $nodeString = '';
                foreach ($nodes as $node) {
                    $nodeString .= aesDecrypt($node['name']) . ', ';
                }
                $nodeString = substr($nodeString, 0, -2);
                $message = 'Farm has node(s): ' . $nodeString;
                $error = true;
            } else {    // farm has no nodes
                dbDeleteFarmById(trim($_POST['farmId']));   // delete farm
                header('location: /pages/farms.php?delete=success');
            }
        }

        // update farm
        $farm = dbGetFarmsById(trim($_GET['farmId']));
    } else {
        $message = 'Incorrect password';
        $error = true;
    }
}

//get all plants
$plants = dbGetAllPlants();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Farm Setting</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include __DIR__ . '/../pages/components/navbar.php'; ?>

    <div class="container">

        <!-- back button -->
        <div class="row my-3">
            <div class="col">
                <a href="/pages/farms.php" class="btn btn-primary">Back</a>
            </div>
        </div>

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
        <div class="row mb-3">
            <h1>Farm Setting</h1>
        </div>

        <!-- update node name form -->
        <div class="row mb-5">
            <form action="farmSetting.php?farmId=<?php echo $_GET['farmId']; ?>" method="post">
                <h2>Update Farm</h2>

                <!-- farm name -->
                <div class="col mb-3">
                    <label for="farmName" class="form-label">Farm Name:</label>
                    <input type="text" class="form-control" id="farmName" name="farmName" value="<?php echo aesDecrypt($farm['name']); ?>" minlength="5" required>
                </div>

                <!-- farm description -->
                <div class="col mb-3">
                    <label for="farmDescription" class="form-label">Farm Description:</label>
                    <input type="text" class="form-control" id="farmDescription" name="farmDescription" value="<?php echo aesDecrypt($farm['description']); ?>" minlength="5" required>
                </div>

                <!-- select plant -->
                <div class="col mb-3">
                    <label for="plantId" class="form-label">Select Plant:</label>
                    <select class="form-select" id="plantId" name="plantId" disabled>
                        <?php foreach ($plants as $plant) { ?>
                            <option value="<?php echo $plant['id']; ?>" <?php if ($farm['plant_id'] == $plant['id']) {
                                                                            echo 'selected';
                                                                        } ?>><?php echo aesDecrypt($plant['name']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <!-- current password -->
                <div class="col mb-3">
                    <label for="password" class="form-label">Current Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <input type="hidden" name="type" value="farm">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>

        <!-- delete record -->
        <div class="row mb-5">
            <form action="farmSetting.php?farmId=<?php echo $_GET['farmId']; ?>" method="post">
                <h2>Delete farm</h2>

                <!-- current password -->
                <div class="col mb-3">
                    <label for="password" class="form-label">Current Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <input type="hidden" name="farmId" value="<?php echo $_GET['farmId']; ?>">
                    <input type="hidden" name="type" value="delete">
                    <!-- hints -->
                    <div class="alert alert-danger" role="alert">
                        <ul class="text-start">
                            <li>The record would be deleted</li>
                        </ul>
                    </div>
                    <button type="submit" class="btn btn-danger">Delete Farm</button>
                </div>

            </form>
        </div>
    </div>
</body>

</html>