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

$invoice_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get invoice status
$sql = "SELECT status FROM invoices WHERE id = $invoice_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $invoice = $result->fetch_assoc();
    
    // Only allow deletion of pending invoices
    if ($invoice['status'] == 'Paid') {
        header("Location: list.php?error=Cannot delete paid invoices");
        exit();
    }
    
    // Delete invoice
    $sql = "DELETE FROM invoices WHERE id = $invoice_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: list.php?success=Invoice deleted successfully");
    } else {
        header("Location: list.php?error=" . urlencode($conn->error));
    }
} else {
    header("Location: list.php?error=Invoice not found");
}

exit();
