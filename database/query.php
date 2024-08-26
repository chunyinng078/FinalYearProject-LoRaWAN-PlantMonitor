<?php
// handle queries to the database that are reusable

require_once __DIR__ . '/dbConnection.php';

// function to get all users from the database
function dbGetAllUsers()
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $users;
};

// function to get users by id from the database
function dbGetUsersByName($username)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_assoc();
    $stmt->close();
    return $users;
};

// function to get users by id from the database
function dbGetAllNodes()
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM nodes");
    $stmt->execute();
    $result = $stmt->get_result();
    $nodes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $nodes;
};

// function to get nodes by id from the database
function dbGetNodesById($id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM nodes WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $nodes = $result->fetch_assoc();
    $stmt->close();
    return $nodes;
};

// function to get nodes by name from the database
function dbGetNodesByName($name)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM nodes WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $nodes = $result->fetch_assoc();
    $stmt->close();
    return $nodes;
};

// function to delete nodes by id from the database
function dbDeleteNodeById($id)
{
    global $conn;
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $stmt = $conn->prepare("DELETE FROM nodes WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
};

// function to get all farms from the database
function dbGetAllFarms()
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM farms");
    $stmt->execute();
    $result = $stmt->get_result();
    $farms = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $farms;
};

// function to get farms by id from the database
function dbGetFarmsById($id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM farms WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $farms = $result->fetch_assoc();
    $stmt->close();
    return $farms;
};

// function to get farms by name from the database
function dbDeleteFarmById($id)
{
    global $conn;
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $stmt = $conn->prepare("DELETE FROM farms WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
};

// function to get all plants from the database
function dbGetAllPlants()
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM plants");
    $stmt->execute();
    $result = $stmt->get_result();
    $plants = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $plants;
};

// function to get plants by id from the database
function dbGetPlantsById($id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM plants WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plants = $result->fetch_assoc();
    $stmt->close();
    return $plants;
};

// function to get plants by name from the database
function dbGetPlantsByName($name)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM plants WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $plants = $result->fetch_assoc();
    $stmt->close();
    return $plants;
};

// function to delete plants by id from the database
function dbDeletePlantById($id)
{
    global $conn;
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $stmt = $conn->prepare("DELETE FROM plants WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
};

// function to get all sensors from the database
function getFarmRelatedToPlant($plantId)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM farms WHERE plant_id = ?");
    $stmt->bind_param("s", $plantId);
    $stmt->execute();
    $result = $stmt->get_result();
    $farms = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $farms;
}

// function to get all sensors from the database
function getFarmRelatedToNode($nodeId)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM nodes WHERE id = ?");
    $stmt->bind_param("s", $nodeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $node = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM farms WHERE id = ?");
    $stmt->bind_param("s", $node['farm_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $farm = $result->fetch_assoc();
    $stmt->close();
    return $farm;
}
