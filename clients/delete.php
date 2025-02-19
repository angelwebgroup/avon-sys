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

$client_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if client has any projects
$sql = "SELECT COUNT(*) as count FROM projects WHERE client_id = $client_id";
$result = $conn->query($sql);
$project_count = $result->fetch_assoc()['count'];

if ($project_count > 0) {
    header("Location: list.php?error=Cannot delete client with active projects");
    exit();
}

// Delete client
$sql = "DELETE FROM clients WHERE id = $client_id";

if ($conn->query($sql) === TRUE) {
    header("Location: list.php?success=Client deleted successfully");
} else {
    header("Location: list.php?error=" . urlencode($conn->error));
}
exit();
