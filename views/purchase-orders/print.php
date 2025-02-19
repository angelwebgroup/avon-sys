<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/PurchaseOrderController.php';

// Initialize auth
$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/login.php");
    exit();
}

// Get PO ID
$poId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$poId) {
    die("Purchase Order ID not provided");
}

// Get PO details
$poController = new PurchaseOrderController($conn, $auth);
$po = $poController->getPO($poId);

if (!$po || isset($po['error'])) {
    die("Purchase Order not found");
}

// Format date
$poDate = date('d/m/Y', strtotime($po['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #<?php echo htmlspecialchars($po['po_number']); ?> - Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 20px;
            }
            .table {
                border-color: #000 !important;
            }
            .table th, .table td {
                border-color: #000 !important;
            }
        }
        .company-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .po-details {
            margin-bottom: 30px;
        }
        .signature-section {
            margin-top: 50px;
        }
        .table-bordered {
            border: 1px solid #000;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print Button -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <button onclick="window.print()" class="btn btn-primary">Print</button>
                <a href="view.php?id=<?php echo $poId; ?>" class="btn btn-secondary">Back</a>
            </div>
        </div>

        <!-- Company Header -->
        <div class="company-header">
            <h2>AVON SYSTEMS</h2>
            <p>123 Business Street, City, State - PIN</p>
            <p>Phone: +1234567890 | Email: info@avonsystems.com</p>
            <h3 class="mt-4">PURCHASE ORDER</h3>
        </div>

        <!-- PO Details -->
        <div class="row po-details">
            <div class="col-6">
                <p><strong>To:</strong><br>
                <?php echo htmlspecialchars($po['company_name']); ?><br>
                <?php echo htmlspecialchars($po['address']); ?><br>
                <?php echo htmlspecialchars($po['city']); ?>, 
                <?php echo htmlspecialchars($po['state_code']); ?> - 
                <?php echo htmlspecialchars($po['pin_code']); ?><br>
                Phone: <?php echo htmlspecialchars($po['mobile_no']); ?><br>
                Email: <?php echo htmlspecialchars($po['email']); ?></p>
            </div>
            <div class="col-6 text-end">
                <p><strong>PO Number:</strong> <?php echo htmlspecialchars($po['po_number']); ?><br>
                <strong>Date:</strong> <?php echo $poDate; ?><br>
                <strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($po['status'])); ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Description</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($po['items'] as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td class="text-end"><?php echo number_format($item['quantity']); ?></td>
                        <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Sub Total:</strong></td>
                        <td class="text-end"><?php echo number_format($po['total_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Tax (18%):</strong></td>
                        <td class="text-end"><?php echo number_format($po['tax_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                        <td class="text-end"><strong><?php echo number_format($po['total_amount'] + $po['tax_amount'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Terms and Signature -->
        <div class="row signature-section">
            <div class="col-12">
                <p><strong>Terms and Conditions:</strong></p>
                <ol>
                    <li>Payment terms: Net 30 days</li>
                    <li>Delivery within agreed timeline</li>
                    <li>All prices are in local currency</li>
                    <li>Goods must meet specified quality standards</li>
                </ol>
            </div>
            <div class="col-6 mt-5">
                <p>_______________________<br>
                Authorized Signature</p>
            </div>
            <div class="col-6 mt-5 text-end">
                <p>_______________________<br>
                Date</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
