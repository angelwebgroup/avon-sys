<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get total clients
$sql = "SELECT COUNT(*) as total FROM clients";
$result = $conn->query($sql);
$total_clients = $result->fetch_assoc()['total'];

// Get total projects
$sql = "SELECT COUNT(*) as total, 
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress
        FROM projects";
$result = $conn->query($sql);
$projects = $result->fetch_assoc();

// Get invoice statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN status = 'Overdue' THEN amount ELSE 0 END) as total_overdue,
        SUM(amount) as total_amount
        FROM invoices";
$result = $conn->query($sql);
$invoices = $result->fetch_assoc();

// Get recent activities (last 5 invoices)
$sql = "SELECT i.invoice_number, i.amount, i.status, c.name as client_name, i.created_at
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        ORDER BY i.created_at DESC LIMIT 5";
$recent_invoices = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Client Management System</title>
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
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .stats-card h3 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .stats-card .number {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <h2 class="mb-4">Reports & Analytics</h2>
                
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>Total Clients</h3>
                            <p class="number"><?php echo $total_clients; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>Total Projects</h3>
                            <p class="number"><?php echo $projects['total']; ?></p>
                            <small class="text-muted">
                                <?php echo $projects['completed']; ?> Completed, 
                                <?php echo $projects['in_progress']; ?> In Progress
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>Total Revenue</h3>
                            <p class="number">$<?php echo number_format($invoices['total_paid'], 2); ?></p>
                            <small class="text-muted">
                                From <?php echo $invoices['total']; ?> Invoices
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3>Outstanding Amount</h3>
                            <p class="number">$<?php echo number_format($invoices['total_overdue'], 2); ?></p>
                            <small class="text-danger">Overdue Invoices</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Recent Invoices</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($invoice = $recent_invoices->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $invoice['invoice_number']; ?></td>
                                            <td><?php echo $invoice['client_name']; ?></td>
                                            <td>$<?php echo number_format($invoice['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $invoice['status'] == 'Paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo $invoice['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
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
