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

// Update invoice status to Paid
$sql = "UPDATE invoices SET status = 'Paid' WHERE id = $invoice_id";

if ($conn->query($sql) === TRUE) {
    header("Location: list.php?success=Invoice marked as paid");
} else {
    header("Location: list.php?error=" . urlencode($conn->error));
}
exit();
