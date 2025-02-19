<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // You'll need to install TCPDF using Composer

use TCPDF;

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$invoice_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get invoice details
$sql = "SELECT i.*, c.name as client_name, c.email as client_email, c.address as client_address,
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

// Get company details from settings
$sql = "SELECT * FROM settings LIMIT 1";
$settings = $conn->query($sql)->fetch_assoc();

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($settings['company_name']);
$pdf->SetTitle('Invoice #' . $invoice['invoice_number']);

// Remove header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Company logo (if exists)
// $pdf->Image('path/to/logo.png', 15, 15, 50);

// Company information
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, $settings['company_name'], 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $settings['company_address'], 0, 1, 'R');
$pdf->Cell(0, 6, $settings['company_email'], 0, 1, 'R');
$pdf->Cell(0, 6, $settings['company_phone'], 0, 1, 'R');

$pdf->Ln(10);

// Invoice details
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'INVOICE', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Invoice #: ' . $invoice['invoice_number'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Date: ' . date('F d, Y', strtotime($invoice['issue_date'])), 0, 1, 'L');
$pdf->Cell(0, 6, 'Due Date: ' . date('F d, Y', strtotime($invoice['due_date'])), 0, 1, 'L');

$pdf->Ln(10);

// Client information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Bill To:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $invoice['client_name'], 0, 1, 'L');
$pdf->MultiCell(0, 6, $invoice['client_address'], 0, 'L');
$pdf->Cell(0, 6, $invoice['client_email'], 0, 1, 'L');

$pdf->Ln(10);

// Invoice items header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(90, 8, 'Description', 1, 0, 'L', true);
$pdf->Cell(30, 8, 'Rate', 1, 0, 'R', true);
$pdf->Cell(30, 8, 'Quantity', 1, 0, 'R', true);
$pdf->Cell(30, 8, 'Amount', 1, 1, 'R', true);

// Invoice items
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(90, 8, $invoice['project_name'] ? $invoice['project_name'] : 'General Services', 1, 0, 'L');
$pdf->Cell(30, 8, '$' . number_format($invoice['amount'], 2), 1, 0, 'R');
$pdf->Cell(30, 8, '1', 1, 0, 'R');
$pdf->Cell(30, 8, '$' . number_format($invoice['amount'], 2), 1, 1, 'R');

// Totals
$pdf->Ln(5);
$pdf->Cell(120, 8, '', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 8, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(30, 8, '$' . number_format($invoice['amount'], 2), 0, 1, 'R');

$tax_amount = $invoice['amount'] * ($settings['tax_rate'] / 100);
$pdf->Cell(120, 8, '', 0, 0);
$pdf->Cell(30, 8, 'Tax (' . $settings['tax_rate'] . '%):', 0, 0, 'R');
$pdf->Cell(30, 8, '$' . number_format($tax_amount, 2), 0, 1, 'R');

$total = $invoice['amount'] + $tax_amount;
$pdf->Cell(120, 8, '', 0, 0);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(30, 8, 'Total:', 0, 0, 'R');
$pdf->Cell(30, 8, '$' . number_format($total, 2), 0, 1, 'R');

// Notes
if ($invoice['notes']) {
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 8, 'Notes:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 6, $invoice['notes'], 0, 'L');
}

// Payment terms
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'Payment Terms:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, 'Please make payment within ' . $settings['payment_terms'] . ' days.', 0, 'L');

// Output the PDF
$pdf->Output('Invoice_' . $invoice['invoice_number'] . '.pdf', 'D');
