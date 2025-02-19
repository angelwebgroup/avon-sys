<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get all invoices
$sql = "SELECT i.*, c.name as client_name, p.name as project_name 
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id 
        ORDER BY i.issue_date DESC";
$result = $conn->query($sql);

// Calculate totals
$sql = "SELECT 
        SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as total_pending,
        COUNT(*) as total_invoices
        FROM invoices";
$totals = $conn->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Client Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
        }
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover {
            background: #34495e;
            color: white;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .content {
            padding: 20px;
        }
        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Invoices</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Invoice
                    </a>
                </div>

                <?php if (isset($_GET['email_sent'])): ?>
                    <div class="alert alert-success">Invoice has been sent successfully!</div>
                <?php endif; ?>

                <?php if (isset($_GET['email_error'])): ?>
                    <div class="alert alert-danger">Error sending email: <?php echo htmlspecialchars($_GET['email_error']); ?></div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="summary-card">
                            <h4>Total Invoices</h4>
                            <h2><?php echo $totals['total_invoices']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-card">
                            <h4>Total Paid</h4>
                            <h2 class="text-success">$<?php echo number_format($totals['total_paid'], 2); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-card">
                            <h4>Total Pending</h4>
                            <h2 class="text-warning">$<?php echo number_format($totals['total_pending'], 2); ?></h2>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Client</th>
                                        <th>Project</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while($invoice = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                                <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($invoice['project_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                                <td>$<?php echo number_format($invoice['amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $invoice['status'] == 'Paid' ? 'success' : 'warning'; ?>">
                                                        <?php echo $invoice['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="print_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                                        <i class="fas fa-print"></i> Print
                                                    </a>
                                                    <?php if ($invoice['status'] !== 'Paid'): ?>
                                                        <a href="edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="mark_paid.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to mark this invoice as paid?')">
                                                            <i class="fas fa-check"></i> Mark Paid
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($invoice['status'] === 'Pending'): ?>
                                                        <a href="delete.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this invoice?')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No invoices found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
