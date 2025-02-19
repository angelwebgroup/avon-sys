<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Create recurring_payments table if not exists
$sql = "CREATE TABLE IF NOT EXISTS recurring_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    frequency VARCHAR(20) NOT NULL, -- Monthly, Quarterly, Yearly
    next_due_date DATE NOT NULL,
    last_paid_date DATE,
    status ENUM('Active', 'Pending', 'Paid', 'Overdue') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Get current year's payments
$current_year = date('Y');
$sql = "SELECT rp.*, p.name as project_name, c.name as client_name 
        FROM recurring_payments rp 
        JOIN projects p ON rp.project_id = p.id 
        JOIN clients c ON p.client_id = c.id 
        WHERE YEAR(rp.next_due_date) = $current_year 
        ORDER BY rp.next_due_date";
$result = $conn->query($sql);

// Calculate totals
$sql = "SELECT 
        SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_received,
        SUM(CASE WHEN status IN ('Pending', 'Overdue') THEN amount ELSE 0 END) as total_pending
        FROM recurring_payments 
        WHERE YEAR(next_due_date) = $current_year";
$totals = $conn->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recurring Payments - Client Management System</title>
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
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
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
                <h2 class="mb-4">Recurring Payments</h2>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="summary-card">
                            <h4>Total Received (<?php echo $current_year; ?>)</h4>
                            <h2 class="text-success">$<?php echo number_format($totals['total_received'], 2); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="summary-card">
                            <h4>Total Pending (<?php echo $current_year; ?>)</h4>
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
                                        <th>Client</th>
                                        <th>Project</th>
                                        <th>Amount</th>
                                        <th>Frequency</th>
                                        <th>Next Due Date</th>
                                        <th>Last Paid Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while($payment = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['project_name']); ?></td>
                                                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo $payment['frequency']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($payment['next_due_date'])); ?></td>
                                                <td>
                                                    <?php echo $payment['last_paid_date'] ? date('M d, Y', strtotime($payment['last_paid_date'])) : 'Never'; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($payment['status']) {
                                                        case 'Active':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        case 'Pending':
                                                            $status_class = 'bg-warning';
                                                            break;
                                                        case 'Paid':
                                                            $status_class = 'bg-primary';
                                                            break;
                                                        case 'Overdue':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <?php echo $payment['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="mark_paid.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Mark Paid
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No recurring payments found</td>
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
