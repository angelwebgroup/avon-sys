<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$service_id = mysqli_real_escape_string($conn, $_GET['id']);

// Delete service
$sql = "DELETE FROM services WHERE id = $service_id";

if ($conn->query($sql) === TRUE) {
    header("Location: list.php?success=Service deleted successfully");
} else {
    header("Location: list.php?error=" . urlencode($conn->error));
}
exit();
