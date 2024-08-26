<?php
// show the live data in a table

require_once __DIR__ . '/../../database/dbConnection.php';
require_once __DIR__ . '/../../database/query.php';
require_once __DIR__ . '/../../auth/aes.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
$domain = $_ENV['DOMAIN'];

// check if user is logged in
session_start();
if (!isset($_SESSION['verified']) || $_SESSION['verified'] == 'false') {
    header('location: ' . $domain . '/pages/login.php');
}

// get the farm id
$farmId = $_GET['farmId'];

// get first 20 records
if ($farmId == 'all' || !isset($farmId)) {
    $stmt = $conn->prepare("SELECT * FROM sensors_data order by id desc limit 20");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // get record related to the farm where the record only have node id, and a node is link to a farm
    $stmt = $conn->prepare("SELECT * FROM sensors_data where node_id in (select id from nodes where farm_id = ?) order by id desc limit 20");
    $stmt->bind_param('i', $farmId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}


// get all nodes
$nodes = dbGetAllNodes();

// get all farms
$farms = dbGetAllFarms();

// add node name to record by node id
foreach ($data as &$record) {
    foreach ($nodes as $node) {
        if ($record['node_id'] == $node['id']) {
            $record['name'] = $node['name'];
        }
    }
}
unset($record);

// add farm name to record by node id
foreach ($data as &$records) {
    $farm = getFarmRelatedToNode($records['node_id']);
    $records['farm'] = $farm['name'];
}
unset($records);

// decrypt thresholds values
$nodes['low_temperature'] = aesDecrypt($nodes['low_temperature']);
$nodes['high_temperature'] = aesDecrypt($nodes['high_temperature']);
$nodes['low_humidity'] = aesDecrypt($nodes['low_humidity']);
$nodes['high_humidity'] = aesDecrypt($nodes['high_humidity']);
$nodes['low_soil_moisture'] = aesDecrypt($nodes['low_soil_moisture']);
$nodes['high_soil_moisture'] = aesDecrypt($nodes['high_soil_moisture']);
$nodes['low_lux'] = aesDecrypt($nodes['low_lux']);
$nodes['high_lux'] = aesDecrypt($nodes['high_lux']);
?>

<?php
// if there is data
if (!empty($data)) {
?>
    <!-- hints -->
    <div class="alert alert-info" role="alert">
        <ul>
            <li>Lastest 20 records was shown here</li>
            <li>Blue background color indicates that the value is <= the lower threshold value (the value is too less)</li>
            <li>Red background color indicates that the value is >= the upper threshold value (the value is too much)</li>
        </ul>
    </div>
    <table class="table table-striped" id="liveData">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Node</th>
                <th scope="col">Farm</th>
                <th scope="col">Temperature in °C</th>
                <th scope="col">Humidity in %</th>
                <th scope="col">Soil moisture</th>
                <th scope="col">Lux in lx</th>
                <th scope="col">Time</th>
            </tr>
        </thead>
        <?php foreach ($data as $record) { ?>
            <tr>
                <?php

                // get farm by id
                $farm = getFarmRelatedToNode($record['node_id']);

                // get plant by id
                $plant = dbGetPlantsById($farm['plant_id']);

                // decrypt sensor data
                $temperature = aesDecrypt($record['temperature']);
                $humidity = aesDecrypt($record['humidity']);
                $soilMoisture = aesDecrypt($record['soil_moisture']);
                $lux = aesDecrypt($record['lux']);

                // set color based on threshold
                $temperatureColor = '';
                $humidityColor = '';
                $soilMoistureColor = '';
                $luxColor = '';

                // setup the color
                $warningColorAbove = '#FDA5A5';
                $warningColorBelow = '#C3E5FF';

                // compare the value with the threshold
                if ($temperature >= aesDecrypt($plant['high_temperature'])) {
                    $temperatureColor = 'background-color: ' . $warningColorAbove . ';';
                } elseif ($temperature <= aesDecrypt($plant['low_temperature'])) {
                    $temperatureColor = 'background-color: ' . $warningColorBelow . ';';
                }
                if ($humidity >= aesDecrypt($plant['high_humidity'])) {
                    $humidityColor = 'background-color: ' . $warningColorAbove . ';';
                } elseif ($humidity <= aesDecrypt($plant['low_humidity'])) {
                    $humidityColor = 'background-color: ' . $warningColorBelow . ';';
                }
                if ($soilMoisture <= aesDecrypt($plant['high_soil_moisture'])) {
                    $soilMoistureColor = 'background-color: ' . $warningColorAbove . ';';
                } elseif ($soilMoisture >= aesDecrypt($plant['low_soil_moisture'])) {
                    $soilMoistureColor = 'background-color: ' . $warningColorBelow . ';';
                }
                if ($lux >= aesDecrypt($plant['high_lux'])) {
                    $luxColor = 'background-color: ' . $warningColorAbove . ';';
                } elseif ($lux <= aesDecrypt($plant['low_lux'])) {
                    $luxColor = 'background-color: ' . $warningColorBelow . ';';
                }
                ?>

                <!-- print the table rows -->
                <td><?php echo $record['id']; ?></td>
                <td><?php echo aesDecrypt($record['name']); ?></td>
                <td><?php echo aesDecrypt($record['farm']); ?></td>
                <td style="<?php echo $temperatureColor ?>"><?php echo aesDecrypt($record['temperature']); ?></td>
                <td style="<?php echo $humidityColor ?>"><?php echo aesDecrypt($record['humidity']); ?></td>
                <td style="<?php echo $soilMoistureColor ?>"><?php echo aesDecrypt($record['soil_moisture']); ?></td>
                <td style="<?php echo $luxColor ?>"><?php echo aesDecrypt($record['lux']); ?></td>
                <td><?php echo $record['time']; ?></td>
            </tr>
        <?php } ?>
    </table>



<?php } ?>

<?php
// if there is no data
if (empty($data)) { ?>
    <div class="alert alert-warning" role="alert">
        No data available
    </div>
<?php } ?>