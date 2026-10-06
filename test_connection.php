<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "pos_system";
$port = 3307;

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database,
    $port
);

if ($conn->connect_error) {
    die("FAILED: " . $conn->connect_error);
}

echo "SUCCESS: PHP connected to MariaDB!";
echo "<br>";

$result = $conn->query(
    "SELECT USER(), CURRENT_USER(), @@hostname, @@port"
);

$row = $result->fetch_assoc();

echo "USER(): " . $row["USER()"] . "<br>";
echo "CURRENT_USER(): " . $row["CURRENT_USER()"] . "<br>";
echo "HOSTNAME: " . $row["@@" . "hostname"] . "<br>";
echo "PORT: " . $row["@@" . "port"] . "<br>";

?>