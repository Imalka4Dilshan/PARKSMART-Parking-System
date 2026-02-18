<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "parking_db";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL timezone to match PHP timezone
$conn->query("SET time_zone = '+05:30'");
?>
