<?php

$servername = "localhost";
$username = "id13382127_bunnbebe";
$password = "nccnpUncw123!";
$database = "id13382127_capacitybuildingapp";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
