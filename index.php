<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();

//get domain from env
$domain = getenv('DOMAIN');

if ($_SESSION['verified']=='verified') {
    header('location: '.$domain.'/pages/liveView.php');
    exit;
}

header('location: '.$domain.'/pages/login.php');
