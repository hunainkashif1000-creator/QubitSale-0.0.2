<?php
// session must be started only once
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}
