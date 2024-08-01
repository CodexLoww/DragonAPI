<?php
$hostname = "dragonpaytest.mysql.database.azure.com";
$username = "user";
$password = '+W-wcE"}bjd,)9)';
$dbname = "transactions";

$conn = new mysqli($hostname, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>