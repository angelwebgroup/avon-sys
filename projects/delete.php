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

$project_id = mysqli_real_escape_string($conn, $_GET['id']);

// Start transaction
$conn->begin_transaction();

try {
    // Delete recurring payments
    $sql = "DELETE FROM recurring_payments WHERE project_id = $project_id";
    $conn->query($sql);
    
    // Delete invoices
    $sql = "DELETE FROM invoices WHERE project_id = $project_id";
    $conn->query($sql);
    
    // Delete project
    $sql = "DELETE FROM projects WHERE id = $project_id";
    $conn->query($sql);
    
    // Commit transaction
    $conn->commit();
    header("Location: list.php?success=Project deleted successfully");
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    header("Location: list.php?error=" . urlencode($e->getMessage()));
}

exit();
