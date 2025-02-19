<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

// Function to safely get count
function getCount($conn, $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['count'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Error in query: " . $query . " - " . $e->getMessage());
    }
    return 0;
}

// Function to safely get records
function getRecords($conn, $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            return $result;
        }
    } catch (Exception $e) {
        error_log("Error in query: " . $query . " - " . $e->getMessage());
    }
    return false;
}

// Get total customers
$total_customers = getCount($conn, "SELECT COUNT(*) as count FROM customers");

// Get total quotes
$quote_stats = [
    'total_quotes' => getCount($conn, "SELECT COUNT(*) as count FROM quotes"),
    'pending_quotes' => getCount($conn, "SELECT COUNT(*) as count FROM quotes WHERE status = 'pending'"),
    'approved_quotes' => getCount($conn, "SELECT COUNT(*) as count FROM quotes WHERE status = 'approved'")
];

// Get total purchase orders
$total_pos = getCount($conn, "SELECT COUNT(*) as count FROM purchase_orders");

// Get recent quotes
$recent_quotes = getRecords($conn, "
    SELECT q.*, c.company_name 
    FROM quotes q 
    LEFT JOIN customers c ON q.customer_id = c.id 
    ORDER BY q.created_at DESC 
    LIMIT 5
");

// Get recent inquiries
$recent_inquiries = getRecords($conn, "
    SELECT * FROM inquiries 
    ORDER BY created_at DESC 
    LIMIT 5
");

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
           
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .card-body {
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }
        .stat-card .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .stat-card .card-text {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0;
            color: white;
        }
        .stat-card.customers, .stat-card.quotes, .stat-card.pending,  .stat-card.pos  {
            background: #0a58ca;
        }
      
        .trend-indicator {
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        .trend-up {
            color: #4CAF50;
        }
        .trend-down {
            color: #F44336;
        }

        .custom-icon {
            color: #cee2ff;}
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Dashboard</h2>
                        <div>
                            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card stat-card customers">
                                <div class="card-body">
                                    <i class="fas fa-users icon custom-icon"></i>
                                    <h5 class="card-title">Total Customers</h5>
                                    <h2 class="card-text">
                                        <?php echo number_format($total_customers); ?>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card stat-card quotes">
                                <div class="card-body">
                                    <i class="fas fa-file-invoice-dollar icon custom-icon"></i>
                                    <h5 class="card-title">Total Quotes</h5>
                                    <h2 class="card-text">
                                        <?php echo number_format($quote_stats['total_quotes']); ?>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card stat-card pending">
                                <div class="card-body">
                                    <i class="fas fa-clock icon custom-icon"></i>
                                    <h5 class="card-title">Pending Quotes</h5>
                                    <h2 class="card-text">
                                        <?php echo number_format($quote_stats['pending_quotes']); ?>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card stat-card pos">
                                <div class="card-body">
                                    <i class="fas fa-shopping-cart icon custom-icon"></i>
                                    <h5 class="card-title">Purchase Orders</h5>
                                    <h2 class="card-text">
                                        <?php echo number_format($total_pos); ?>
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Recent Quotes -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Quotes</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($recent_quotes && $recent_quotes->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while ($quote = $recent_quotes->fetch_assoc()): ?>
                                                <a href="/avon-sys/views/quotes/view.php?id=<?php echo $quote['id']; ?>" 
                                                   class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($quote['company_name']); ?></h6>
                                                        <small><?php echo date('M d, Y', strtotime($quote['created_at'])); ?></small>
                                                    </div>
                                                    <p class="mb-1">Amount: $<?php echo number_format($quote['total_amount'], 2); ?></p>
                                                    <small>Status: <?php echo ucwords($quote['status']); ?></small>
                                                </a>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No recent quotes found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Inquiries -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Inquiries</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($recent_inquiries && $recent_inquiries->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while ($inquiry = $recent_inquiries->fetch_assoc()): ?>
                                                <a href="/avon-sys/views/inquiries/view.php?id=<?php echo $inquiry['id']; ?>" 
                                                   class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($inquiry['company_name']); ?></h6>
                                                        <small><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></small>
                                                    </div>
                                                    <p class="mb-1"><?php echo htmlspecialchars($inquiry['subject']); ?></p>
                                                    <small>Status: <?php echo ucwords(str_replace('_', ' ', $inquiry['status'] ?? 'unknown')); ?></small>
                                                </a>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No recent inquiries found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
