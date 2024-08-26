<?php
// farm management page

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';

session_start();

// handle form submission
$message = '';
$error = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // get form data
    $farmName = trim($_POST['farmName']);
    $farmDescription = trim($_POST['farmDescription']);
    $plantId = trim($_POST['plantId']);

    // encrypt data
    $farmName = aesEncrypt($farmName);
    $farmDescription = aesEncrypt($farmDescription);

    //get all farms
    $farms = dbGetAllFarms();

    //check if farm exist
    $farmNameExist = false;
    foreach ($farms as $f) {
        if (aesDecrypt($f['name']) == aesDecrypt($farmName)) {
            $farmNameExist = true;
            break;
        }
    }

    // check if farm exist
    if ($farmNameExist) {   // farm name exist
        $message = 'Farm Exist';
        $error = true;
    } else {    // farm name does not exist, insert form to database
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $stmt = $conn->prepare("INSERT INTO farms (name, description, plant_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $farmName, $farmDescription, $plantId);
        $stmt->execute();
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $message = 'Farm created';

    }
}

// show message if node is deleted
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['delete']) && $_GET['delete'] == 'success') {
        $message = 'Node celeted';
    }
}

// get all farms
$farms = dbGetAllFarms();
// get all plants
$plants = dbGetAllPlants();

// add plant name to farm
foreach ($farms as $key => $farm) {
    foreach ($plants as $plant) {
        if ($farm['plant_id'] == $plant['id']) {
            $farms[$key]['plantName'] = $plant['name'];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Farms</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include __DIR__ . '/../pages/components/navbar.php'; ?>

    <div class="container">
        <div class="row">
            <?php
            // show message
            if (isset($message) && $message != '') {
                if ($error) {
                    echo '<div class="alert alert-danger" role="alert">' . $message . '</div>';
                } else {
                    echo '<div class="alert alert-primary" role="alert">' . $message . '</div>';
                }
            }
            ?>
        </div>

        <!-- Title -->
        <div class="row my-3">
            <h1>Farms</h1>
        </div>

        <!-- create farm form -->
        <div class="row mb-5">
            <form action="farms.php" method="post">
                <h2>Create farm</h2>

                <!-- farm name -->
                <div class="col mb-3">
                    <label for="farmName" class="form-label">Farm Name:</label>
                    <input placeholder="e.g., north banana farm" type="text" class="form-control" id="farmName" name="farmName" minlength="5" required>
                </div>

                <!-- farm description -->
                <div class="col mb-3">
                    <label for="farmDescription" class="form-label">Farm Description:</label>
                    <input placeholder="e.g., here plant the new type of banana" type="text" class="form-control" id="farmDescription" name="farmDescription" minlength="5" required>
                </div>

                <!-- select plant -->
                <div class="col mb-3">
                    <label for="plantId" class="form-label">Select Plant:</label>
                    <select class="form-select" id="plantId" name="plantId" required>
                        <?php foreach ($plants as $plant) { ?>
                            <option value="<?php echo $plant['id']; ?>"><?php echo aesDecrypt($plant['name']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col text-end">
                    <button type="submit" class="btn btn-primary">Create new farm</button>
                </div>
            </form>
        </div>

        <!-- show all farms -->
        <div class="row mb-5">
            <h2>Farms list</h2>
            <div class="col">

                <!-- table -->
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">Farms ID</th>
                            <th scope="col">Farm Name</th>
                            <th scope="col">Description</th>
                            <th scope="col">Plant</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($farms as $farm) { ?>
                            <tr>
                                <td><?php echo $farm['id']; ?></td>
                                <td><?php echo aesDecrypt($farm['name']); ?></td>
                                <td><?php echo aesDecrypt($farm['description']); ?></td>
                                <td><?php echo aesDecrypt($farm['plantName']); ?></td>
                                <!-- button to update -->
                                <td><a href="farmSetting.php?farmId=<?php echo $farm['id']; ?>" class="btn btn-primary">Update</a></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
</body>

</html>