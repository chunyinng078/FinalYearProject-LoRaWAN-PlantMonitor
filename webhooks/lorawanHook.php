<?php
// webhook for the things stack server

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';
require_once __DIR__ . '/../mail/mail.php';
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// check if the request has the correct username and password
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('HTTP/1.0 401 Unauthorized');
    echo 'PLEASE PROVIDE USERNAME AND PASSWORD';
    exit;
} else {

    // get the username and password from the .env file
    $username = trim($_ENV['LORAWAN_HOOK_USERNAME']);
    $password = trim($_ENV['LORAWAN_HOOK_PASSWORD']);

    // check if the username and password are correct
    if (trim($_SERVER['PHP_AUTH_USER']) == $username && trim($_SERVER['PHP_AUTH_PW']) == $password) {

        //get the data from the request 
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        $deviceId = $data['end_device_ids']['device_id'];
        $receivedAt = date('Y-m-d H:i:s', strtotime($data['received_at']));
        $payload = $data['uplink_message']['decoded_payload'];

        // extract the payload
        $humidity = $payload['humidity'];
        $temperature = $payload['temperature'];
        $soilMoisture = $payload['soil_moisture'];
        $lux = $payload['lux'];

        // check if payload is empty
        if ((!is_numeric($humidity) || !is_numeric($temperature) || !is_numeric($soilMoisture) || !is_numeric($lux))) {
            exit;
        } else {
            // encrypt the data
            $humidityDB = aesEncrypt($payload['humidity']);
            $temperatureDB = aesEncrypt($payload['temperature']);
            $soilMoistureDB = aesEncrypt($payload['soil_moisture']);
            $luxDB = aesEncrypt($payload['lux']);

            // insert the data into the database
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            $stmt = $conn->prepare("INSERT INTO sensors_data(temperature,humidity,soil_moisture,lux,node_id,time) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $temperatureDB, $humidityDB, $soilMoistureDB, $luxDB, $deviceId, $receivedAt);
            $stmt->execute();
            $stmt->close();
            $conn->query("SET FOREIGN_KEY_CHECKS=1");

            //get farms
            $farm = getFarmRelatedToNode($deviceId);

            //get plants
            $plant = dbGetPlantsById($farm['plant_id']);

            $message = '';
            // check if the data exceeds the thresholds
            if ($temperature >= aesDecrypt($plant['high_temperature'])) {
                $message .= 'Temperature is above or equal to the upper threshold ' . aesDecrypt($plant['high_temperature']) . ', it is: ' . $temperature . ' degree Celsius <br>';
            }
            if ($humidity >= aesDecrypt($plant['high_humidity'])) {
                $message .= 'Humidity is above or equal to the upper threshold ' . aesDecrypt($plant['high_humidity']) . ', it is: ' . $humidity . ' % <br>';
            }
            if ($soilMoisture <= aesDecrypt($plant['high_soil_moisture'])) {
                $message .= 'Soil moisture is above or equal to the upper threshold ' . aesDecrypt($plant['high_soil_moisture']) . ', it is: ' . $soilMoisture . ' <br>';
            }
            if ($lux >= aesDecrypt($plant['high_lux'])) {
                $message .= 'Lux is above or equal to the upper threshold ' . aesDecrypt($plant['high_lux']) . ', it is: ' . $lux . ' lx <br>';
            }

            // check if the data is below the thresholds
            if ($temperature <= aesDecrypt($plant['low_temperature'])) {
                $message .= 'Temperature is below or equal to the lower threshold ' . aesDecrypt($plant['low_temperature']) . ', it is: ' . $temperature . ' degree Celsius <br>';
            }
            if ($humidity <= aesDecrypt($plant['low_humidity'])) {
                $message .= 'Humidity is below or equal to the lower threshold ' . aesDecrypt($plant['low_humidity']) . ', it is: ' . $humidity . ' % <br>';
            }
            if ($soilMoisture >= aesDecrypt($plant['low_soil_moisture'])) {
                $message .= 'Moisture is below or equal to the lower threshold ' . aesDecrypt($plant['low_soil_moisture']) . ', it is: ' . $soilMoisture . ' <br>';
            }

            // send email if the data exceeds the thresholds
            if ($message != '') {

                // get farm name and node name 
                $farm = getFarmRelatedToNode($deviceId);
                $farmName = aesDecrypt($farm['name']);

                // get node name
                $node = dbGetNodesById($deviceId);
                $nodeName = aesDecrypt($node['name']);

                // get plant name
                $plantName = aesDecrypt($plant['name']);

                // add farm name and node name to the message
                $message = 'Plant: ' . $plantName . '<br> Farm: ' . $farmName . '<br> Node: ' . $nodeName . '<br> ' . $message;

                // get the email of the node
                $email = aesDecrypt($node['notification_email']);

                // send email
                sendEmail('Thresholds Exceeded', $message, $email, true);
            }
        }


        exit;
    } else {
        header('HTTP/1.0 401 Unauthorized');
        echo 'USERNAME OR PASSWORD NOT CORRECT';
        exit;
    }
}

//  09F6,025D,012C,D6D8