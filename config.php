<?php
define('DB_HOST', 'jpc353.encs.concordia.ca');
define('DB_USER', 'jpc353_2');
define('DB_PASS', 'CementMonsoonSyllable97');
define('DB_NAME', 'jpc353_2');

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>