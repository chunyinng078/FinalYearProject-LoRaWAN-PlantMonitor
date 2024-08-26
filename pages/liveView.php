<?php
// the live view page

require_once __DIR__ . "/../database/dbConnection.php";
require_once __DIR__ . "/../auth/aes.php";
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$domain = $_ENV['DOMAIN'];

// check if user is logged in
session_start();
if (!isset($_SESSION['verified']) || $_SESSION['verified'] == 'false') {
    header('location: ' . $domain . '/pages/login.php');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Live View</title>
    <link rel="icon" type="image/x-icon" href="https://lorawan-plant-monitor.online/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <script>
        // live update the data using ajax
        function liveUpdate() {
            const xhttp = new XMLHttpRequest();

            // get the selected farm
            const farmId = document.getElementById("farmId").value;
            const farmName = document.getElementById("farmId").options[document.getElementById("farmId").selectedIndex].text;

            xhttp.onload = function() {
                document.getElementById("liveData").innerHTML = this.responseText;
            }
            xhttp.open("GET", "components/liveData.php?farmId=" + farmId, true);
            xhttp.send();




            // gmt +8 time in english
            document.getElementById("title").innerHTML = "Live View, updated on: " + new Date().toLocaleString("en-US", {
                timeZone: "Asia/Hong_Kong"
            });

        }

        // update every 30 seconds
        setInterval(() => {
            liveUpdate();
        }, 30000);
    </script>
</head>

</header>

<body onload="liveUpdate()">
    <?php include __DIR__ . "/../pages/components/navbar.php"; ?>

    <div class="container">

        <!-- Title -->
        <div class="row my-3"">
            <h1 id="title">Live View, updated on:</h1>
        </div>

        <!-- search by farm -->
        <div class="my-3 col">
            <form action="liveView.php" method="get">
                <div class="row">
                    <label class="form-label" for="farmId">Search by Farm:</label>
                </div>
                <div class="row">
                    <select class="form-select" id="farmId" name="farmId"">
                    <option value="all">All</option>
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM farms");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $farms = $result->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();

                        // list farm and select the current farm
                        foreach ($farms as $farm) {
                            if (isset($_GET['farmId']) && $_GET['farmId'] == $farm['id']) {
                                echo "<option value='" . $farm['id'] . "' selected>" . aesDecrypt($farm['name']) . "</option>";
                            } else {
                                echo "<option value='" . $farm['id'] . "'>" . aesDecrypt($farm['name']) . "</option>";
                            }
                        }

                        ?>
                    </select>
                </div>
                <div class="row">
                    <button type="submit" class="btn btn-primary mt-1">Search</button>
                </div>
            </form>
        </div>

        <!-- live data -->
        <div id="liveData">

        </div>
    </div>
</body>

</html>