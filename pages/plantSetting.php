<?php
// plant setting page

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';

session_start();

// check if plantId is set
if (isset($_GET['plantId'])) {
    $plant = dbGetPlantsById($_GET['plantId']);
} else {
    header('location: /pages/plants.php');
}

// handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // get form data
    $type = trim($_POST['type']);
    $password = trim($_POST['password']);

    // get user
    $user = dbGetUsersByName($_SESSION['username']);

    // check if password is correct
    $message = '';
    $error = false;
    if ($user['password'] == hash('sha256', $password . $user['salt'])) {

        // if type is plant
        if ($type == 'plant') {

            // get form data
            $plantId = $_GET['plantId'];
            $plantName = aesEncrypt($_POST['plantName']);
            $plantDescription = aesEncrypt($_POST['plantDescription']);
            $temperatureForm = aesEncrypt($_POST['temperatureForm']);
            $temperatureTo = aesEncrypt($_POST['temperatureTo']);
            $humidityForm = aesEncrypt($_POST['humidityForm']);
            $humidityTo = aesEncrypt($_POST['humidityTo']);
            $soilMoistureForm = aesEncrypt($_POST['soilMoistureForm']);
            $soilMoistureTo = aesEncrypt($_POST['soilMoistureTo']);
            $luxForm = aesEncrypt($_POST['luxForm']);
            $luxTo = aesEncrypt($_POST['luxTo']);

            //check if plant name exist
            $stmt = $conn->prepare("SELECT * FROM plants WHERE name = ? AND id != ?");
            $stmt->bind_param("ss", $plantName, $plantId);
            $stmt->execute();
            $result = $stmt->get_result();
            $plantNameExist = $result->fetch_assoc();
            $stmt->close();

            // check if plant name exist
            if ($plantNameExist) {  // plant name exist
                $message = 'Plant name exist';
                $error = true;
            } else {    // plant name does not exist, update data to database
                $conn->query("SET FOREIGN_KEY_CHECKS=0");
                $stmt = $conn->prepare("UPDATE plants SET name = ?, description = ?, low_temperature = ?, high_temperature = ?, low_humidity = ?, high_humidity = ?, low_soil_moisture = ?, high_soil_moisture = ?, low_lux = ?, high_lux = ? WHERE id = ?");
                $stmt->bind_param("ssssssssssi", $plantName, $plantDescription, $temperatureForm, $temperatureTo, $humidityForm, $humidityTo, $soilMoistureForm, $soilMoistureTo, $luxForm, $luxTo, $plantId);
                $stmt->execute();
                $stmt->close();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");

                $message = 'Plant Updated';
            }
        } elseif ($type == 'delete') {  // if type is delete

            // get data of if plant is used in any farm
            $stmt = $conn->prepare("SELECT * FROM farms WHERE plant_id = ?");
            $stmt->bind_param("i", trim($_GET['plantId']));
            $stmt->execute();
            $result = $stmt->get_result();
            $farms = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // check if plant is used in any farm
            if ($farms) {   // plant is used in farm
                $farmString = '';
                foreach ($farms as $farm) {
                    $farmString .= aesDecrypt($farm['name']) . ', ';
                }
                $farmString = substr($farmString, 0, -2);
                $message = 'Plant is used in  farm(s) ' . $farmString;
                $error = true;
            } else {    // plant is not used in any farm, delete plant
                dbDeletePlantById(trim($_GET['plantId']));
                header('location: /pages/plants.php?delete=success');
            }
        }

        //update the $plant
        $plant = dbGetPlantsById(trim($_GET['plantId']));
    } else {
        $message = 'Incorrect password';
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Plant Setting</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include __DIR__ . '/../pages/components/navbar.php'; ?>

    <div class="container">

        <!-- back button -->
        <div class="row mt-3 mb-3">
            <div class="col">
                <a href="/pages/plants.php" class="btn btn-primary">Back</a>
            </div>
        </div>

        <!-- ,essage -->
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
            <h1>Plant Setting</h1>
        </div>

        <!-- update plant form -->
        <div class="row mb-5">
            <form action="plantSetting.php?plantId=<?php echo $_GET['plantId']; ?>" method="post">
                <h2>Update Plant</h2>

                <!-- plantName -->
                <div class="row">
                    <div class="col mb-3">
                        <label for="plantName" class="form-label">Plant Name:</label>
                        <input type="text" class="form-control" id="plantName" name="plantName" value="<?php echo aesDecrypt($plant['name']); ?>" minlength="3" required>
                    </div>
                </div>

                <!-- plantDescription -->
                <div class="row">
                    <div class="col mb-3">
                        <label for="plantDescription" class="form-label">Plant Description:</label>
                        <input type="text" class="form-control" id="plantDescription" name="plantDescription" value="<?php echo aesDecrypt($plant['description']); ?>" minlength="5" required>
                    </div>
                </div>

                <!-- temperture -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="temperatureForm" class="form-label">Temperature From (0°C-50°C):</label>
                        <input type="number" class="form-control" id="temperatureForm" name="temperatureForm" value="<?php echo aesDecrypt($plant['low_temperature']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="temperatureTo" class="form-label">Temperature To (0°C-50°C):</label>
                        <input type="number" class="form-control" id="temperatureTo" name="temperatureTo" value="<?php echo aesDecrypt($plant['high_temperature']); ?>" required>
                    </div>
                </div>

                <!-- humidity -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="humidityForm" class="form-label">Humidity From (20%-90%):</label>
                        <input type="number" class="form-control" id="humidityForm" name="humidityForm" value="<?php echo aesDecrypt($plant['low_humidity']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="humidityTo" class="form-label">Humidity To (20%-90%):</label>
                        <input type="number" class="form-control" id="humidityTo" name="humidityTo" value="<?php echo aesDecrypt($plant['high_humidity']); ?>" required>
                    </div>
                </div>

                <!-- soil moisture -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="soilMoistureForm" class="form-label">Soil moisture From (0-1023):</label>
                        <span data-bs-toggle="tooltip" title="the lower the value, the wetter the soil">❔</span>
                        <input type="number" class="form-control" id="soilMoistureForm" name="soilMoistureForm" value="<?php echo aesDecrypt($plant['low_soil_moisture']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="soilMoistureTo" class="form-label">Soil Moisture To (0-1023):</label>
                        <span data-bs-toggle="tooltip" title="the higher the value, the drier the soil">❔</span>
                        <input type="number" class="form-control" id="soilMoistureTo" name="soilMoistureTo" value="<?php echo aesDecrypt($plant['high_soil_moisture']); ?>" required>
                    </div>
                </div>

                <!-- lux -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="luxForm" class="form-label">Lux From (0lx-65534lx):</label>
                        <input type="number" class="form-control" id="luxForm" name="luxForm" value="<?php echo aesDecrypt($plant['low_lux']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="luxTo" class="form-label">Lux To (0lx-65534lx):</label>
                        <input type="number" class="form-control" id="luxTo" name="luxTo" value="<?php echo aesDecrypt($plant['high_lux']); ?>" required>
                    </div>
                </div>

                <!-- current password -->
                <div class="row">
                    <div class="col mb-3">
                        <label for="password" class="form-label">Current Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <!-- hints -->
                <div class="alert alert-info" role="alert">
                    <ul>
                        <li>The Temperature, Humidity, Soil moisture, and Lux values would be used as thresholds of plants</li>
                        <li>Notification email will be sent if thresholds were exceeded (<= From value (too less) or >= To Value (too much) , where soil moisture is >= From value (too less) or <= To Value (too much))</li>
                        <li>When <= From value of lux will no get notified, because lux value will easily exceed threshold at night</li>
                        <li>Higher soil moisture value mean more dry, vice versa.</li>
                    </ul>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <input type="hidden" name="type" value="plant">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>

            </form>
        </div>

        <!-- delete record -->
        <div class="row mb-5">
            <form action="plantSetting.php?plantId=<?php echo $_GET['plantId']; ?>" method="post">

                <!-- title -->
                <h2>Delete plant</h2>

                <!-- current password -->
                <div class="row">
                    <div class="col mb-3">
                        <label for="password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <!-- submit form -->
                <div class="col text-end">
                    <input type="hidden" name="plantId" value="<?php echo $_GET['plantId']; ?>">
                    <input type="hidden" name="type" value="delete">
                    <!-- hints -->
                    <div class="alert alert-danger" role="alert">
                        <ul class="text-start">
                            <li>The record would be deleted</li>
                        </ul>
                    </div>
                    <button type="submit" class="btn btn-danger">Delete Plant</button>
                </div>

            </form>

        </div>
    </div>
</body>

</html>