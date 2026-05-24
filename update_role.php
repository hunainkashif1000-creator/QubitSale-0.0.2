<?php
include 'db.php';

if(isset($_POST['id'], $_POST['role'])){
    $id   = (int)$_POST['id'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si", $role, $id);
    $stmt->execute();
}

header("Location: user_list.php");
exit;
