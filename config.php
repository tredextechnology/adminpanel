<?php
$servername = "localhost:3306";  // Update with your server details
$username = "root";         // Update with your username
$password = "root";             // Update with your password
$dbname = "candidate_details";  // Update with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
