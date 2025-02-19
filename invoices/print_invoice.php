<?php
session_start();
require_once '../config/database.php';

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

// Get company settings
$sql = "SELECT * FROM settings LIMIT 1";
$settings = $conn->query($sql)->fetch_assoc();

// Set headers for PDF-like display
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice['invoice_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
        }
        .invoice-header {
            text-align: right;
            margin-bottom: 40px;
        }
        .invoice-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        .company-details {
            margin-bottom: 30px;
        }
        .client-details {
            margin-bottom: 30px;
        }
        .invoice-items {
            margin-bottom: 30px;
        }
        .invoice-total {
            text-align: right;
            margin-top: 20px;
        }
        .notes {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print/Download Buttons -->
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <button onclick="window.location.href='list.php'" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </button>
        </div>

        <!-- Invoice Content -->
        <div class="invoice-header">
            <h1><?php echo htmlspecialchars($settings['company_name']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($settings['company_address'])); ?><br>
               Email: <?php echo htmlspecialchars($settings['company_email']); ?><br>
               Phone: <?php echo htmlspecialchars($settings['company_phone']); ?></p>
        </div>

        <div class="invoice-title">
            INVOICE
        </div>

        <div class="row">
            <div class="col-md-6 client-details">
                <h5>Bill To:</h5>
                <p><strong><?php echo htmlspecialchars($invoice['client_name']); ?></strong><br>
                   <?php echo nl2br(htmlspecialchars($invoice['client_address'])); ?><br>
                   Email: <?php echo htmlspecialchars($invoice['client_email']); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
                   <strong>Issue Date:</strong> <?php echo date('F d, Y', strtotime($invoice['issue_date'])); ?><br>
                   <strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($invoice['due_date'])); ?></p>
            </div>
        </div>

        <div class="invoice-items">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $invoice['project_name'] ? htmlspecialchars($invoice['project_name']) : 'Professional Services'; ?></td>
                        <td class="text-end">$<?php echo number_format($invoice['amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="invoice-total">
            <table class="table table-borderless w-50 ms-auto">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-end">$<?php echo number_format($invoice['amount'], 2); ?></td>
                </tr>
                <?php if (isset($settings['tax_rate']) && $settings['tax_rate'] > 0): ?>
                <tr>
                    <td><strong>Tax (<?php echo $settings['tax_rate']; ?>%):</strong></td>
                    <td class="text-end">$<?php echo number_format($invoice['amount'] * $settings['tax_rate'] / 100, 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>Total:</strong></td>
                    <td class="text-end">
                        <strong>$<?php echo number_format($invoice['amount'] * (1 + ($settings['tax_rate'] ?? 0) / 100), 2); ?></strong>
                    </td>
                </tr>
            </table>
        </div>

        <?php if ($invoice['notes']): ?>
        <div class="notes">
            <h5>Notes:</h5>
            <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="notes">
            <h5>Payment Terms:</h5>
            <p>Please make payment within <?php echo $settings['payment_terms'] ?? 30; ?> days.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
