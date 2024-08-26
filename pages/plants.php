<?php
// plant management page 

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';

session_start();

// handle form submission
$message = '';
$error = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // get form data
    $plantName = trim($_POST['plantName']);
    $plantDescription = trim($_POST['plantDescription']);
    $temperatureFrom = trim($_POST['temperatureFrom']);
    $temperatureTo = trim($_POST['temperatureTo']);
    $humidityForm = trim($_POST['humidityForm']);
    $humidityTo = trim($_POST['humidityTo']);
    $soilMoistureForm = trim($_POST['soilMoistureForm']);
    $soilMoistureTo = trim($_POST['soilMoistureTo']);
    $luxForm = trim($_POST['luxForm']);
    $luxTo = trim($_POST['luxTo']);

    // encrypt data
    $plantName = aesEncrypt($plantName);
    $plantDescription = aesEncrypt($plantDescription);
    $temperatureFrom = aesEncrypt($temperatureFrom);
    $temperatureTo = aesEncrypt($temperatureTo);
    $humidityForm = aesEncrypt($humidityForm);
    $humidityTo = aesEncrypt($humidityTo);
    $soilMoistureForm = aesEncrypt($soilMoistureForm);
    $soilMoistureTo = aesEncrypt($soilMoistureTo);
    $luxForm = aesEncrypt($luxForm);
    $luxTo = aesEncrypt($luxTo);

    // get plant
    $plant = dbGetAllPlants();

    // loop through plants to check if plant exist
    $plantExist = false;
    foreach ($plant as $p) {
        if (aesDecrypt($p['name']) == trim($_POST['plantName'])) {
            $plantExist = true;
            break;
        }
    }


    // check if plant exist
    if ($plantExist) {   // if plant exist
        $message = 'Plant name exist';
        $error = true;
    } else {    // plant does not exist, insert data to database
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $stmt = $conn->prepare("INSERT INTO plants (name, description, low_temperature, high_temperature, low_humidity, high_humidity, low_soil_moisture, high_soil_moisture, low_lux, high_lux) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $plantName, $plantDescription, $temperatureFrom, $temperatureTo, $humidityForm, $humidityTo, $soilMoistureForm, $soilMoistureTo, $luxForm, $luxTo);
        $stmt->execute();
        $stmt->close();
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $message = 'Plant created';
    }
}

// set success message if plant is deleted
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['delete']) && $_GET['delete'] == 'success') {
        $message = 'Plant deleted';
    }
}

// get all plants
$plants = dbGetAllPlants();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Plants</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
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
            <h1>Plants</h1>
        </div>

        <!-- create plant form -->
        <div class="row mb-5">
            <form action="plants.php" method="post">
                <h2>Create plant</h2>

                <!-- plants name -->
                <div class="row">
                    <div class="col mb-3">
                        <label for="plantName" class="form-label">Plant Name:</label>
                        <input placeholder="e.g., banana" type="text" class="form-control" id="plantName" name="plantName" minlength="3" required>
                    </div>
                </div>

                <!-- plants description -->
                <div class="row">
                    <div class="col mb-3">
                        <label for="plantDescription" class="form-label">Description:</label>
                        <input placeholder="e.g., new type of banana" type="text" class="form-control" id="plantDescription" name="plantDescription" minlength="5" required>
                    </div>
                </div>

                <!-- temperture -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="temperatureFrom" class="form-label">Temperature From (0°C-50°C):</label>
                        <input placeholder="e.g., 20" type="number" min="0" max="50" class="form-control" id="temperatureFrom" name="temperatureFrom" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="temperatureTo" class="form-label">Temperature To (0°C-50°C):</label>
                        <input placeholder="e.g., 30" type="number" min="0" max="50" class="form-control" id="temperatureTo" name="temperatureTo" required>
                    </div>
                </div>

                <!-- humidity -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="humidityForm" class="form-label">Humidity From (20%-90%):</label>
                        <input placeholder="e.g., 50" type="number" min="20" max="90" class="form-control" id="humidityForm" name="humidityForm" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="humidityTo" class="form-label">Humidity To (20%-90%):</label>
                        <input placeholder="e.g., 70" type="number" min="20" max="90" class="form-control" id="humidityTo" name="humidityTo" required>
                    </div>
                </div>

                <!-- soil moisture -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="soilMoistureForm" class="form-label">Soil moisture From (0-1023):</label>
                        <span data-bs-toggle="tooltip" title="the lower the value, the wetter the soil">❔</span>
                        <input placeholder="e.g., 700" type="number" min="0" max="1023" class="form-control" id="soilMoistureForm" name="soilMoistureForm" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="soilMoistureTo" class="form-label">Soil Moisture To (0-1023):</label>
                        <span data-bs-toggle="tooltip" title="the higher the value, the drier the soil">❔</span>
                        <input placeholder="e.g., 300" type="number" min="0" max="1023" class="form-control" id="soilMoistureTo" name="soilMoistureTo" required>
                    </div>
                </div>

                <!-- lux -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="luxForm" class="form-label">Lux From (0lx-65534lx):</label>
                        <input placeholder="e.g., 300" type="number" min="0" max="65534" class="form-control" id="luxForm" name="luxForm" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="luxTo" class="form-label">Lux To (0lx-65534lx):</label>
                        <input placeholder="e.g., 1000" type="number" min="0" max="65534" class="form-control" id="luxTo" name="luxTo" required>
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
                    <button type="submit" class="btn btn-primary">Create new plant</button>
                </div>
            </form>
        </div>

        <!-- list all plants -->
        <div class="row mb-5">

            <!-- title -->
            <h2>Plants list</h2>

            <!-- table -->
            <div class="col">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">Plant ID</th>
                            <th scope="col">Plant Name</th>
                            <th scope="col">Description</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // list plants
                        foreach ($plants as $plant) { ?>
                            <tr>
                                <td><?php echo $plant['id']; ?></td>
                                <td><?php echo aesDecrypt($plant['name']); ?></td>
                                <td><?php echo aesDecrypt($plant['description']); ?></td>
                                <!-- button to update -->
                                <td><a href="plantSetting.php?plantId=<?php echo $plant['id']; ?>" class="btn btn-primary">Update</a></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
</body>

</html>