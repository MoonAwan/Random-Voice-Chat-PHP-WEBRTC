<?php
// db_connect.php

// --- Database Configuration ---
// Replace with your actual database credentials
$dbHost = 'host';
$dbUser = 'user';
$dbPass = 'pass';
$dbName = 'db_name';

// Create a database connection
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check connection
if ($conn->connect_error) {
    // In a real app, you'd handle this more gracefully, perhaps by logging
    // the error and showing a generic error page.
    // For this application, we'll stop execution.
    http_response_code(500);
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
