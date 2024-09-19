<?php
$servername = "sql208.infinityfree.com";
$username = "if0_35769164"; 
$password = "leinyajjayniel"; 
$dbname = "if0_35769164_dental_clinic"; 
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
session_start();
$conn->set_charset("utf8mb4");
?>
