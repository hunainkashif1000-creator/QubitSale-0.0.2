
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==== DATABASE DETAILS ==== */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "qubitsale_db";
/* =========================== */

// Check if mysqli exists
if (!function_exists('mysqli_connect')) {
    die("MySQLi extension is NOT enabled.");
}

// Create connection
$conn = mysqli_connect($host, $user, $pass, $db);


?>
