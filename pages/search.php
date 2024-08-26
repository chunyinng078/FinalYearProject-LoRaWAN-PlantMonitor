<?php
// searching page

require_once __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../database/query.php';
require_once __DIR__ . '/../auth/aes.php';

// retrieve data of all nodes, plants, and farms
$nodes = dbGetAllNodes();
$plants = dbGetAllPlants();
$farms = dbGetAllFarms();

//get plants for form
$formPlants = dbGetAllPlants();

//get farms for form
$formFarms = dbGetAllFarms();

//get nodes for form
$formNodes = dbGetAllNodes();

// get now date time
$now = date("Y-m-d") . "T" . date("H:i");

// handle form submission
if (
    $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['nodeId']) && isset($_GET['temperatureFrom']) &&
    isset($_GET['temperatureTo']) && isset($_GET['humidityFrom']) &&
    isset($_GET['humidityTo']) && isset($_GET['soilMoistureFrom']) && isset($_GET['soilMoistureTo']) &&
    isset($_GET['luxFrom']) && isset($_GET['luxTo']) && isset($_GET['timeFrom']) && isset($_GET['timeTo'])
    && isset($_GET['sortBy']) && isset($_GET['sortOrder']) && isset($_GET['resultType']) && isset($_GET['export'])
) {

    // get form data
    $nodeId = $_GET['nodeId'];
    $farmId = $_GET['farmId'];
    $plantId = $_GET['plantId'];
    $temperatureFrom = $_GET['temperatureFrom'];
    $temperatureTo = $_GET['temperatureTo'];
    $humidityFrom = $_GET['humidityFrom'];
    $humidityTo = $_GET['humidityTo'];
    $soilMoistureFrom = $_GET['soilMoistureFrom'];
    $soilMoistureTo = $_GET['soilMoistureTo'];
    $luxFrom = $_GET['luxFrom'];
    $luxTo = $_GET['luxTo'];
    $timeFrom = $_GET['timeFrom'];
    $timeTo = $_GET['timeTo'];
    $resultType = $_GET['resultType'];
    $sortBy = $_GET['sortBy'];
    $sortOrder = $_GET['sortOrder'];
    $warning = $_GET['warning'];

    // get data from database by time
    $stmt = $conn->prepare("SELECT * FROM sensors_data WHERE time >= ? AND time <= ?");
    $stmt->bind_param("ss", $timeFrom, $timeTo);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // decrypt data for filter later on
    foreach ($data as $key => $record) {
        $data[$key]['temperature'] = aesDecrypt($record['temperature']);
        $data[$key]['humidity'] = aesDecrypt($record['humidity']);
        $data[$key]['soil_moisture'] = aesDecrypt($record['soil_moisture']);
        $data[$key]['lux'] = aesDecrypt($record['lux']);
    }

    // filter base on temperature, humidity, moisture, lux
    $data = array_filter($data, function ($record) use ($temperatureFrom, $temperatureTo, $humidityFrom, $humidityTo, $soilMoistureFrom, $soilMoistureTo, $luxFrom, $luxTo) {
        return $record['temperature'] >= $temperatureFrom && $record['temperature'] <= $temperatureTo
            && $record['humidity'] >= $humidityFrom && $record['humidity'] <= $humidityTo
            && $record['soil_moisture'] >= $soilMoistureFrom && $record['soil_moisture'] <= $soilMoistureTo
            && $record['lux'] >= $luxFrom && $record['lux'] <= $luxTo;
    });

    // filter base on node id
    if ($nodeId != 'all') {
        $data = array_filter($data, function ($record) use ($nodeId) {
            return $record['node_id'] == $nodeId;
        });
    }

    // filter base on farm
    if ($farmId != 'all') {
        $data = array_filter($data, function ($record) use ($farmId) {
            $farm = getFarmRelatedToNode($record['node_id']);
            return $farm['id'] == $farmId;
        });
    }

    // filter base on plant
    if ($plantId != 'all') {
        $farms = getFarmRelatedToPlant($plantId);
        $farmIds = [];
        foreach ($farms as $farm) {
            $farmIds[] = $farm['id'];
        }
        $data = array_filter($data, function ($record) use ($farmIds) {
            $farm = getFarmRelatedToNode($record['node_id']);
            return in_array($farm['id'], $farmIds);
        });
    }

    // filter base on warning
    if ($warning != 'all') {
        $data = array_filter($data, function ($record) use ($warning) {
            $farm = getFarmRelatedToNode($record['node_id']);
            $plant = dbGetPlantsById($farm['plant_id']);
            $temperature = $record['temperature'];
            $humidity = $record['humidity'];
            $soilMoisture = $record['soil_moisture'];
            $lux = $record['lux'];

            if ($warning == 'yes') {
                return $temperature >= aesDecrypt($plant['high_temperature']) || $temperature <= aesDecrypt($plant['low_temperature'])
                    || $humidity >= aesDecrypt($plant['high_humidity']) || $humidity <= aesDecrypt($plant['low_humidity'])
                    || $soilMoisture <= aesDecrypt($plant['high_soil_moisture']) || $soilMoisture >= aesDecrypt($plant['low_soil_moisture'])
                    || $lux >= aesDecrypt($plant['high_lux']) || $lux <= aesDecrypt($plant['low_lux']);
            } else {
                return $temperature < aesDecrypt($plant['high_temperature']) && $temperature > aesDecrypt($plant['low_temperature'])
                    && $humidity < aesDecrypt($plant['high_humidity']) && $humidity > aesDecrypt($plant['low_humidity'])
                    && $soilMoisture > aesDecrypt($plant['high_soil_moisture']) && $soilMoisture < aesDecrypt($plant['low_soil_moisture'])
                    && $lux < aesDecrypt($plant['high_lux']) && $lux > aesDecrypt($plant['low_lux']);
            }
        });
    }

    // add node name to records
    foreach ($data as &$records) {
        foreach ($nodes as $node) {
            if ($records['node_id'] == $node['id']) {
                $records['name'] = aesDecrypt($node['name']);
            }
        }
    }
    unset($records);

    //add farm name to records
    foreach ($data as &$records) {
        $farm = getFarmRelatedToNode($records['node_id']);
        $records['farm'] = aesDecrypt($farm['name']);
    }
    unset($records);

    // sort if is table 
    if ($resultType == 'table') {
        // sort data 
        usort($data, function ($a, $b) use ($sortBy, $sortOrder) {
            if ($sortOrder == 'asc') {
                return $a[$sortBy] <=> $b[$sortBy];
            } else {
                return $b[$sortBy] <=> $a[$sortBy];
            }
        });
    }


    // pagination
    $maxRecordsOfPages = 20;
    $recordCounts = count($data);
    $totalPages = ceil($recordCounts / $maxRecordsOfPages);
    $currentPage = 1;
    if (isset($_GET['page'])) {
        $currentPage = $_GET['page'];
    }
    $offset = ($currentPage - 1) * $maxRecordsOfPages;
    $paginatedData = array_slice($data, $offset, $maxRecordsOfPages);

    //export as csv
    if ($_GET['export'] == 'yes') {

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sensor_data.csv');

        // create file 
        $output = fopen('php://output', 'w');

        // column headings
        fputcsv($output, array('ID', 'Temperature', 'Humidity', 'Soil Moisture', 'Lux', 'Time', 'Node_ID', 'Node_Name', 'Farm_Name'));

        // insert records to csv
        foreach ($data as $record) {
            fputcsv($output, $record);
        }

        // output the file
        fclose($output);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Search</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include __DIR__ . '/../pages/components/navbar.php'; ?>

    <div class="container">

        <!-- title -->
        <div class="row my-3">
            <h1 id='title'>Records Searching</h1>
        </div>

        <!-- searching filter -->
        <div class="row mb-3">
            <div class="col-md-6 offset-md-3">
                <form action="search.php" method="GET">

                    <!-- filter plant -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="plantId" class="form-label">Plant:</label>
                            <select class="form-select" id="plantId" name="plantId">
                                <option value="all">All</option>
                                <?php foreach ($formPlants as $plant) { ?>
                                    <option value="<?php echo $plant['id']; ?>" <?php if (isset($_GET['plantId']) && $_GET['plantId'] == $plant['id']) { ?> selected="selected" <?php } ?>>
                                        <?php echo aesDecrypt($plant['name']); ?>
                                    <?php } ?>
                            </select>
                        </div>

                        <!-- filter farm -->
                        <div class="col-md-6 mb-3">
                            <label for="farmId" class="form-label">Farm:</label>
                            <select class="form-select" id="farmId" name="farmId">
                                <option value="all">All</option>
                                <?php foreach ($formFarms as $farm) { ?>
                                    <option value="<?php echo $farm['id']; ?>" <?php if (isset($_GET['farmId']) && $_GET['farmId'] == $farm['id']) { ?> selected="selected" <?php } ?>>
                                        <?php echo aesDecrypt($farm['name']); ?>
                                    <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <!-- filter node -->
                        <div class="col-md-6 mb-3">
                            <label for="nodeId" class="form-label">Node:</label>
                            <select class="form-select" id="nodeId" name="nodeId">
                                <option value="all">All</option>
                                <?php
                                foreach ($formNodes as $node) {
                                ?>
                                    <option value="<?php echo $node['id']; ?>" <?php if (isset($_GET['nodeId']) && $_GET['nodeId'] == $node['id']) { ?> selected="selected" <?php } ?>>
                                        <?php echo aesDecrypt($node['name']); ?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>

                        <!-- filter warning -->
                        <div class="col-md-6 mb-3">
                            <label for="warning" class="form-label">Warning:</label>
                            <span data-bs-toggle="tooltip" title="if the metrics are above thresholds">❔</span>
                            <select class="form-select" id="warning" name="warning">
                                <option value="all" <?php if (isset($_GET['warning']) && $_GET['warning'] == 'all') { ?> selected <?php } ?>>Not matter</option>
                                <option value="yes" <?php if (isset($_GET['warning']) && $_GET['warning'] == 'yes') { ?> selected <?php } ?>>Yes</option>
                                <option value="no" <?php if (isset($_GET['warning']) && $_GET['warning'] == 'no') { ?> selected <?php } ?>>No</option>
                            </select>
                        </div>
                    </div>

                    <!-- filter temperature -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="temperatureFrom" class="form-label">Temperature From (0°C-50°C):</label>
                            <input type="number" min="0" max="50" class="form-control" id="temperatureFrom" name="temperatureFrom" <?php if (isset($_GET['temperatureFrom'])) { ?> value="<?php echo $_GET['temperatureFrom']; ?>" <?php } else { ?> value="0" <?php } ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="temperatureTo" class="form-label">Temperature To (0°C-50°C):</label>
                            <input type="number" min="0" max="50" class="form-control" id="temperatureTo" name="temperatureTo" <?php if (isset($_GET['temperatureTo'])) { ?> value="<?php echo $_GET['temperatureTo']; ?>" <?php } else { ?> value="50" <?php } ?>>
                        </div>
                    </div>

                    <!-- filter humidity -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="humidityFrom" class="form-label">Humidity From (20%-90%):</label>
                            <input type="number" min="20" max="90" class="form-control" id="humidityFrom" name="humidityFrom" <?php if (isset($_GET['humidityFrom'])) { ?> value="<?php echo $_GET['humidityFrom']; ?>" <?php } else { ?> value="20" <?php } ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="humidityTo" class="form-label">Humidity To (20%-90%):</label>
                            <input type="number" min="20" max="90" class="form-control" id="humidityTo" name="humidityTo" <?php if (isset($_GET['humidityTo'])) { ?> value="<?php echo $_GET['humidityTo']; ?>" <?php } else { ?> value="90" <?php } ?>>
                        </div>
                    </div>

                    <!-- filter soil moisture -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="soilMoistureFrom" class="form-label">Soil moisture From (0-1023):</label>
                            <span data-bs-toggle="tooltip" title="the lower the value, the wetter the soil">❔</span>
                            <input type="number" min="0" max="1023" class="form-control" id="soilMoistureFrom" name="soilMoistureFrom" <?php if (isset($_GET['soilMoistureFrom'])) { ?> value="<?php echo $_GET['soilMoistureFrom']; ?>" <?php } else { ?> value="0" <?php } ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="soilMoistureTo" class="form-label">Soil moisture To (0-1023):</label>
                            <span data-bs-toggle="tooltip" title="the higher the value, the drier the soil">❔</span>
                            <input type="number" min="0" max="1023" class="form-control" id="soilMoistureTo" name="soilMoistureTo" <?php if (isset($_GET['soilMoistureTo'])) { ?> value="<?php echo $_GET['soilMoistureTo']; ?>" <?php } else { ?> value="1023" <?php } ?>>
                        </div>
                    </div>

                    <!-- filter lux -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="luxFrom" class="form-label">Lux From (0lx-65534lx):</label>
                            <input type="number" min="0" max="65534" class="form-control" id="luxFrom" name="luxFrom" <?php if (isset($_GET['luxFrom'])) { ?> value="<?php echo $_GET['luxFrom']; ?>" <?php } else { ?> value="0" <?php } ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="luxTo" class="form-label">Lux To (0lx-65534lx):</label>
                            <input type="number" min="0" max="65534" class="form-control" id="luxTo" name="luxTo" <?php if (isset($_GET['luxTo'])) { ?> value="<?php echo $_GET['luxTo']; ?>" <?php } else { ?> value="65534" <?php } ?>>
                        </div>
                    </div>

                    <!-- filter time -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="timeFrom" class="form-label">Time From:</label>
                            <input type="datetime-local" class="form-control" id="timeFrom" name="timeFrom" <?php if (isset($_GET['timeFrom'])) { ?> value="<?php echo $_GET['timeFrom']; ?>" <?php } else { ?> value="2024-01-01T00:00" <?php } ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="timeTo" class="form-label">Time To:</label>
                            <input type="datetime-local" class="form-control" id="timeTo" name="timeTo" <?php if (isset($_GET['timeTo'])) { ?> value="<?php echo $_GET['timeTo']; ?>" <?php } else { ?> value="<?php echo $now ?>" <?php } ?>>
                        </div>
                    </div>

                    <!-- sort by  -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sortBy" class="form-label">Sort By:</label>
                            <select class="form-select" id="sortBy" name="sortBy">
                                <option value="id" <?php if ($sortBy == 'id') { ?> selected <?php } ?>>ID</option>
                                <option value="name" <?php if ($sortBy == 'name') { ?> selected <?php } ?>>Node Name</option>
                                <option value="farm" <?php if ($sortBy == 'farm') { ?> selected <?php } ?>>Farm Name</option>
                                <option value="temperature" <?php if ($sortBy == 'temperature') { ?> selected <?php } ?>>Temperature</option>
                                <option value="humidity" <?php if ($sortBy == 'humidity') { ?> selected <?php } ?>>Humidity</option>
                                <option value="soil_moisture" <?php if ($sortBy == 'soil_moisture') { ?> selected <?php } ?>>Soil moisture</option>
                                <option value="lux" <?php if ($sortBy == 'lux') { ?> selected <?php } ?>>Lux </option>
                                <option value="time" <?php if ($sortBy == 'time') { ?> selected <?php } ?>>Time</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sortOrder" class="form-label">Sort Order:</label>
                            <select class="form-select" id="sortOrder" name="sortOrder">
                                <option value="asc" <?php if ($sortOrder == 'asc') { ?> selected <?php } ?>>Ascending</option>
                                <option value="desc" <?php if ($sortOrder == 'desc') { ?> selected <?php } ?>>Descending</option>
                            </select>
                        </div>
                    </div>

                    <!-- result in table or graph -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="resultType" class="form-label">Result:</label>
                            <select class="form-select" id="resultType" name="resultType">
                                <option value="table" <?php if ($resultType == 'table') { ?> selected <?php } ?>>Table</option>
                                <option value="graph" <?php if ($resultType == 'graph') { ?> selected <?php } ?>>Graph</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="export" class="form-label">Export as CSV:</label>
                            <select class="form-select" id="export" name="export">
                                <option value="no" selected>No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>
                    </div>

                    <div class="col text-end">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <button type="button" class="btn btn-danger" onclick="window.location.href='search.php'">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- search result -->
        <div class="row mb-3">
            <div class="row">
                <div class="col">
                    <?php
                    // if there is data and result type is table
                    if (isset($_GET['resultType']) && $resultType == 'table' && !empty($data)) {
                    ?>
                        <!-- hints -->
                        <div class="alert alert-info" role="alert">
                            <ul>
                                <li>Blue background color indicates that the value is <= the lower threshold value (the value is too less)</li>
                                <li>Red background color indicates that the value is >= the upper threshold value (the value is too much)</li>
                            </ul>
                        </div>

                        <div class="row">
                            <table class="table table-striped mb-3">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Node</th>
                                        <th scope="col">Farm</th>
                                        <th scope="col">Temperature in °C</th>
                                        <th scope="col">Humidity in %</th>
                                        <th scope="col">Soil Moisture</th>
                                        <th scope="col">Lux in lx</th>
                                        <th scope="col">Time</th>
                                    </tr>
                                </thead>
                                <?php foreach ($paginatedData as $record) { ?>
                                    <tr>
                                        <?php

                                        // get farm 
                                        $farm = getFarmRelatedToNode($record['node_id']);

                                        // get plant
                                        $plant = dbGetPlantsById($farm['plant_id']);

                                        // set color based on threshold
                                        $temperature = $record['temperature'];
                                        $humidity = $record['humidity'];
                                        $soilMoisture = $record['soil_moisture'];
                                        $lux = $record['lux'];

                                        $temperatureColor = '';
                                        $humidityColor = '';
                                        $soilMoistureColor = '';
                                        $luxColor = '';

                                        // set warning color
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

                                        <td><?php echo $record['id']; ?></td>
                                        <td><?php echo $record['name']; ?></td>
                                        <td><?php echo $record['farm']; ?></td>
                                        <td style="<?php echo $temperatureColor ?>"><?php echo $record['temperature']; ?></td>
                                        <td style="<?php echo $humidityColor ?>"><?php echo $record['humidity']; ?></td>
                                        <td style="<?php echo $soilMoistureColor ?>"><?php echo $record['soil_moisture']; ?></td>
                                        <td style="<?php echo $luxColor ?>"><?php echo $record['lux']; ?></td>
                                        <td><?php echo $record['time']; ?></td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>

                        <!-- pagination -->
                        <div class="row">
                            <!-- next page and previous page -->
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php

                                    // get all GET parameters
                                    $params = $_GET;

                                    // generate the href link for pagination button
                                    $href = 'search.php?';
                                    foreach ($params as $key => $value) {
                                        if ($key != 'page') {
                                            $href .= $key . '=' . $value . '&';
                                        }
                                    }

                                    //remove get parameter 'export', so will not keep downloading
                                    $href = str_replace('export=yes', 'export=no', $href);

                                    //previous page
                                    if ($currentPage > 1) {
                                    ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo $href; ?>page=<?php echo $currentPage - 1; ?>">Previous</a>
                                        </li>
                                    <?php } ?>

                                    <!-- next page -->
                                    <?php if ($currentPage < $totalPages) { ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo $href; ?>page=<?php echo $currentPage + 1; ?>">Next</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </nav>

                            <!-- pagination -->
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php
                                    // To first page
                                    if ($currentPage > 1) {
                                    ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo $href; ?>page=1">First</a>
                                        </li>
                                    <?php }

                                    // Define the range of pages to show
                                    $range = 2; // Number of pages to show on either side of the current page
                                    $start = max(1, $currentPage - $range);
                                    $end = min($totalPages, $currentPage + $range);

                                    // Show ellipsis before the start
                                    if ($start > 1) {
                                        echo '<li class="page-item"><span class="page-link">...</span></li>';
                                    }

                                    // Page numbers
                                    for ($i = $start; $i <= $end; $i++) {
                                    ?>
                                        <li class="page-item <?php if ($i == $currentPage) { ?> active <?php } ?>">
                                            <a class="page-link" href="<?php echo $href; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php }

                                    // Show ellipsis after the end
                                    if ($end < $totalPages) {
                                        echo '<li class="page-item"><span class="page-link">...</span></li>';
                                    }

                                    // To last page
                                    if ($currentPage < $totalPages) {
                                    ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo $href; ?>page=<?php echo $totalPages; ?>">Last</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </nav>
                        </div>
                    <?php } ?>

                    <?php if (isset($_GET['resultType']) && $resultType == 'graph' && !empty($data)) {
                        // include the graph component
                        include __DIR__ . '/../pages/components/graph.php';
                    } ?>

                    <?php
                    // if there is not data
                    if ((empty($data) && !isset($_GET['resultType'])) || (empty($data) && $resultType == 'table') || (empty($data) && $resultType == 'graph')) { ?>
                        <div class="alert alert-warning" role="alert">
                            No data available
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
</body>

</html>