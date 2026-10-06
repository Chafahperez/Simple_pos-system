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

$conn->set_charset("utf8mb4");

?>