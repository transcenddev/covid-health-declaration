<?php
// Database configuration
$servername = 'localhost';
$dbusername = 'root';
$dbpassword = '';
$dbname = 'COVID19RecordsDB'; // Replace with your actual database name

// Create connection
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
