<!-- show graph -->
<div class="alert alert-info" role="alert">
    <ul>
        <li>Click on the metrics on the graph header to filter graph's data</li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12 w-100 h-100"> 
        <canvas id="dataChart"></canvas>
    </div>
</div>

<script>
    var ctx = document.getElementById('dataChart').getContext('2d');
    ctx.canvas.width = 1000;
    ctx.canvas.height = 700;
    var dataChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                <?php foreach ($data as $record) {
                    //remove seconds
                    $record['time'] = substr($record['time'], 0, -3);
                    echo "'" . $record['time'] . "',";
                } ?>
            ],
            datasets: [{
                    label: 'Temperature in °C',
                    data: [
                        <?php foreach ($data as $record) {
                            echo $record['temperature'] . ",";
                        } ?>
                    ],
                    borderColor: 'rgba(255,100, 100, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Humidity in %',
                    data: [
                        <?php foreach ($data as $record) {
                            echo $record['humidity'] . ",";
                        } ?>
                    ],
                    borderColor: 'rgba(100, 100, 255, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Soil moisture',
                    data: [
                        <?php foreach ($data as $record) {
                            echo $record['soil_moisture'] . ",";
                        } ?>
                    ],
                    borderColor: 'rgba(255, 200, 80, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Lux in lx',
                    data: [
                        <?php foreach ($data as $record) {
                            echo $record['lux'] . ",";
                        } ?>
                    ],
                    borderColor: 'rgba(0, 200, 200, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        autoSkip: true,
                        maxTicksLimit: 10 // maximum number of x axis ticks
                    }
                },
            }
        }
    });
</script>