<?php
// Database connection configuration
$host = "localhost";
$dbname = "dbr88kdeizf4xv";
$username = "uklz9ew3hrop3";
$password = "zyrbspyjlzjb";

// Create database connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set character set
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
