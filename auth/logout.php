<?php
// handle logout

session_start();
session_destroy();
header('location: /pages/login.php');
exit;
