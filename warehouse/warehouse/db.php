<?php
$servername = "localhost";
$username = "root";
$password = ""; // Replace with your MySQL root password if any
$dbname = "olywarehouse";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>