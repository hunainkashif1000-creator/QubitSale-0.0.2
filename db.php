
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==== DATABASE DETAILS ==== */
$host = "sql112.infinityfree.com";
$user = "if0_41890867";
$pass = "VqPJfCytFgIcrAA";
$db   = "if0_41890867_Qubitsale";
/* =========================== */

// Check if mysqli exists
if (!function_exists('mysqli_connect')) {
    die("MySQLi extension is NOT enabled.");
}

// Create connection
$conn = mysqli_connect($host, $user, $pass, $db);


?>