<?php
$hostname = "dragonpaydb.cv4sk0iecdmp.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = 'Jlc31Louie';
$dbname = "transactions";

$conn = new mysqli($hostname, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>