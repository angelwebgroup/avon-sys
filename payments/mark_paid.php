<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $payment_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Get payment details
    $sql = "SELECT * FROM recurring_payments WHERE id = $payment_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $payment = $result->fetch_assoc();
        $today = date('Y-m-d');
        
        // Calculate next due date based on frequency
        switch($payment['frequency']) {
            case 'Monthly':
                $next_due_date = date('Y-m-d', strtotime('+1 month', strtotime($payment['next_due_date'])));
                break;
            case 'Quarterly':
                $next_due_date = date('Y-m-d', strtotime('+3 months', strtotime($payment['next_due_date'])));
                break;
            case 'Yearly':
                $next_due_date = date('Y-m-d', strtotime('+1 year', strtotime($payment['next_due_date'])));
                break;
            default:
                $next_due_date = $payment['next_due_date'];
        }
        
        // Update payment status
        $sql = "UPDATE recurring_payments 
                SET status = 'Paid', 
                    last_paid_date = '$today', 
                    next_due_date = '$next_due_date'
                WHERE id = $payment_id";
        
        if ($conn->query($sql) === TRUE) {
            // Create an invoice for this payment
            $project_sql = "SELECT p.*, c.name as client_name 
                          FROM projects p 
                          JOIN clients c ON p.client_id = c.id 
                          WHERE p.id = " . $payment['project_id'];
            $project_result = $conn->query($project_sql);
            $project = $project_result->fetch_assoc();
            
            // Get invoice prefix from settings
            $settings_sql = "SELECT invoice_prefix FROM settings LIMIT 1";
            $settings_result = $conn->query($settings_sql);
            $settings = $settings_result->fetch_assoc();
            $invoice_prefix = $settings['invoice_prefix'] ?? 'INV-';
            
            // Generate invoice number
            $invoice_number = $invoice_prefix . date('Ymd') . '-' . sprintf('%04d', $payment_id);
            
            // Create invoice
            $sql = "INSERT INTO invoices (client_id, project_id, invoice_number, issue_date, due_date, amount, status, notes) 
                    VALUES (
                        {$project['client_id']}, 
                        {$payment['project_id']}, 
                        '$invoice_number', 
                        '$today', 
                        '$today', 
                        {$payment['amount']}, 
                        'Paid', 
                        'Recurring payment for {$project['name']} - {$payment['frequency']} payment'
                    )";
            
            $conn->query($sql);
        }
    }
}

header("Location: recurring.php");
exit();
