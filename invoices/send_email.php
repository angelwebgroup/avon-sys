<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // You'll need to install PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$invoice_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get invoice details
$sql = "SELECT i.*, c.name as client_name, c.email as client_email,
        p.name as project_name 
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id 
        WHERE i.id = $invoice_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Invoice not found");
}

$invoice = $result->fetch_assoc();

// Get company settings
$sql = "SELECT * FROM settings LIMIT 1";
$settings = $conn->query($sql)->fetch_assoc();

try {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host = $settings['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $settings['smtp_username'];
    $mail->Password = $settings['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $settings['smtp_port'];

    // Recipients
    $mail->setFrom($settings['company_email'], $settings['company_name']);
    $mail->addAddress($invoice['client_email'], $invoice['client_name']);
    $mail->addReplyTo($settings['company_email'], $settings['company_name']);

    // Generate PDF invoice
    ob_start();
    include 'generate_pdf.php';
    $pdf_content = ob_get_clean();
    
    // Attach PDF
    $mail->addStringAttachment($pdf_content, 'Invoice_' . $invoice['invoice_number'] . '.pdf');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' from ' . $settings['company_name'];
    
    // Email body
    $body = "
    <p>Dear {$invoice['client_name']},</p>
    
    <p>Please find attached invoice #{$invoice['invoice_number']} for " . 
    ($invoice['project_name'] ? "the project '{$invoice['project_name']}'" : "our services") . ".</p>
    
    <p><strong>Invoice Details:</strong><br>
    Invoice Number: {$invoice['invoice_number']}<br>
    Issue Date: " . date('F d, Y', strtotime($invoice['issue_date'])) . "<br>
    Due Date: " . date('F d, Y', strtotime($invoice['due_date'])) . "<br>
    Amount: $" . number_format($invoice['amount'], 2) . "</p>
    
    <p>Payment is due by " . date('F d, Y', strtotime($invoice['due_date'])) . ". " . 
    "Please make payment within {$settings['payment_terms']} days.</p>";

    if ($invoice['notes']) {
        $body .= "<p><strong>Notes:</strong><br>" . nl2br(htmlspecialchars($invoice['notes'])) . "</p>";
    }

    $body .= "
    <p>If you have any questions, please don't hesitate to contact us.</p>
    
    <p>Best regards,<br>
    {$settings['company_name']}<br>
    {$settings['company_email']}<br>
    {$settings['company_phone']}</p>";

    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);

    // Send email
    $mail->send();
    
    // Update invoice status to indicate email was sent
    $sql = "UPDATE invoices SET email_sent = 1, email_sent_date = NOW() WHERE id = $invoice_id";
    $conn->query($sql);
    
    header("Location: list.php?email_sent=1");
    exit();
    
} catch (Exception $e) {
    header("Location: list.php?email_error=" . urlencode($mail->ErrorInfo));
    exit();
}
