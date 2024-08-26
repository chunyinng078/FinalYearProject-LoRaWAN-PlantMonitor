<?php
// handle database connection

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// setup database connection
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];
$servername = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];

// connect to the database
$conn = new mysqli($servername, $username, $password, $dbname, $port);
