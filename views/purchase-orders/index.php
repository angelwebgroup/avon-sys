<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/PurchaseOrderController.php';

$auth = new AuthController($conn);
$poController = new PurchaseOrderController($conn, $auth);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

// Get all purchase orders
$sql = "SELECT po.*, q.quote_number, q.total_amount, q.tax_amount,
               c.company_name, u1.username as created_by_name,
               u2.username as approved_by_name
        FROM purchase_orders po 
        LEFT JOIN quotes q ON po.quote_id = q.id
        LEFT JOIN customers c ON q.customer_id = c.id 
        LEFT JOIN users u1 ON po.created_by = u1.id
        LEFT JOIN users u2 ON po.approved_by = u2.id
        ORDER BY po.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - Avon System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="/avon-sys/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Purchase Orders</h2>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>PO #</th>
                                            <th>Quote #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Delivery Date</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($po = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($po['po_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($po['quote_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($po['company_name']); ?></td>
                                                    <td>$<?php echo number_format($po['total_amount'] + $po['tax_amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $po['status'] === 'approved' ? 'success' : 
                                                                ($po['status'] === 'pending' ? 'warning' : 
                                                                ($po['status'] === 'delivered' ? 'info' : 'danger')); 
                                                        ?>">
                                                            <?php echo ucfirst($po['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $po['delivery_date'] ? date('M d, Y', strtotime($po['delivery_date'])) : 'Not Set'; ?></td>
                                                    <td><?php echo htmlspecialchars($po['created_by_name']); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view.php?id=<?php echo $po['id']; ?>" 
                                                               class="btn btn-sm btn-info">View</a>
                                                            <?php if ($auth->hasRole('admin')): ?>
                                                                <?php if ($po['status'] === 'pending'): ?>
                                                                    <a href="edit.php?id=<?php echo $po['id']; ?>" 
                                                                       class="btn btn-sm btn-primary">Edit</a>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-success"
                                                                            onclick="approvePO(<?php echo $po['id']; ?>)">
                                                                        Approve
                                                                    </button>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-danger"
                                                                            onclick="deletePO(<?php echo $po['id']; ?>)">
                                                                        Delete
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if ($po['status'] === 'approved'): ?>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-success"
                                                                            onclick="markDelivered(<?php echo $po['id']; ?>)">
                                                                        Mark Delivered
                                                                    </button>
                                                                <?php endif; ?>
                                                                <a href="print.php?id=<?php echo $po['id']; ?>" 
                                                                   class="btn btn-sm btn-secondary"
                                                                   target="_blank">Print</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No purchase orders found</td>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approvePO(id) {
            if (confirm('Are you sure you want to approve this purchase order?')) {
                window.location.href = `approve.php?id=${id}`;
            }
        }

        function deletePO(id) {
            if (confirm('Are you sure you want to delete this purchase order?')) {
                window.location.href = `delete.php?id=${id}`;
            }
        }

        function markDelivered(id) {
            if (confirm('Are you sure you want to mark this purchase order as delivered?')) {
                window.location.href = `deliver.php?id=${id}`;
            }
        }
    </script>
</body>
</html>
