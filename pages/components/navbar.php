<?php
// the navbar component

// check if the user is logged in
session_start();
if (!isset($_SESSION['verified'])) {
    header('location: /pages/login.php');
    exit;
}
?>

<!-- nav bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light p-3 border-bottom border-dark">
    <a class="navbar-brand" href="/pages/liveView.php">Live View</a>
    <div id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="/pages/search.php">Search</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/pages/nodes.php">Nodes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/pages/farms.php">Farms</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/pages/plants.php">Plants</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/pages/account.php">Account</a>
            </li>
            <li class="nav-item">
                <a href="/auth/logout.php" class="btn btn-danger nav-link logout">Logout</a>
            </li>
        </ul>
    </div>
</nav>