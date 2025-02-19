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

if (!$auth->hasPermission('view_purchase_orders')) {
    header("Location: /avon-sys/dashboard.php?error=" . urlencode('Permission denied'));
    exit();
}

$poId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$po = $poController->getPO($poId);

if (!$po || isset($po['error'])) {
    header("Location: index.php?error=" . urlencode('Purchase Order not found'));
    exit();
}

// Get audit logs for this PO
$auditLogs = [];
try {
    // Check if audit_logs table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'audit_logs'");
    if ($tableCheck->num_rows > 0) {
        $sql = "SELECT al.*, u.username as user_name 
                FROM audit_logs al 
                JOIN users u ON al.user_id = u.id 
                WHERE al.entity_type = 'purchase_order' 
                AND al.entity_id = ? 
                ORDER BY al.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $poId);
        $stmt->execute();
        $auditLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Silently handle the error - audit logs are non-critical
    error_log("Error fetching audit logs: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Purchase Order - Avon System</title>
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
                        <h2>Purchase Order #<?php echo htmlspecialchars($po['po_number']); ?></h2>
                        <div>
                            <a href="index.php" class="btn btn-secondary me-2">Back to List</a>
                            <?php if ($auth->hasPermission('print_purchase_orders')): ?>
                                <a href="print.php?id=<?php echo $po['id']; ?>" 
                                   class="btn btn-info me-2" target="_blank">Print PO</a>
                            <?php endif; ?>
                            <?php if ($auth->hasPermission('edit_purchase_orders')): ?>
                                <?php if ($po['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-success me-2" 
                                            onclick="showApproveModal()">Approve</button>
                                    <button type="button" class="btn btn-danger me-2" 
                                            onclick="showCancelModal()">Cancel</button>
                                <?php elseif ($po['status'] === 'approved'): ?>
                                    <button type="button" class="btn btn-primary" 
                                            onclick="markDelivered()">Mark as Delivered</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Purchase Order Information</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4">PO Number</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($po['po_number']); ?></dd>

                                        <dt class="col-sm-4">Quote Number</dt>
                                        <dd class="col-sm-8">
                                            <a href="../quotes/view.php?id=<?php echo $po['quote_id']; ?>">
                                                <?php echo htmlspecialchars($po['quote_number']); ?>
                                            </a>
                                        </dd>

                                        <dt class="col-sm-4">Status</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge bg-<?php 
                                                echo $po['status'] === 'approved' ? 'success' : 
                                                    ($po['status'] === 'pending' ? 'warning' : 
                                                    ($po['status'] === 'delivered' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($po['status']); ?>
                                            </span>
                                        </dd>

                                        <dt class="col-sm-4">Created By</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($po['created_by_name']); ?></dd>

                                        <dt class="col-sm-4">Created Date</dt>
                                        <dd class="col-sm-8"><?php echo date('M d, Y H:i', strtotime($po['created_at'])); ?></dd>

                                        <?php if ($po['delivery_date']): ?>
                                            <dt class="col-sm-4">Delivery Date</dt>
                                            <dd class="col-sm-8">
                                                <?php echo date('M d, Y', strtotime($po['delivery_date'])); ?>
                                                <?php if ($auth->hasPermission('edit_purchase_orders') && $po['status'] === 'approved'): ?>
                                                    <button type="button" class="btn btn-sm btn-link" 
                                                            onclick="showDeliveryDateModal()">
                                                        Update
                                                    </button>
                                                <?php endif; ?>
                                            </dd>
                                        <?php endif; ?>

                                        <?php if ($po['approved_by_name']): ?>
                                            <dt class="col-sm-4">Approved By</dt>
                                            <dd class="col-sm-8"><?php echo htmlspecialchars($po['approved_by_name']); ?></dd>
                                        <?php endif; ?>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4">Company Name</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($po['company_name']); ?></dd>

                                        <dt class="col-sm-4">Contact Person</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($po['contact_person']); ?></dd>

                                        <dt class="col-sm-4">Email</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($po['email']); ?></dd>

                                        <dt class="col-sm-4">Phone</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($po['phone']); ?></dd>

                                        <dt class="col-sm-4">Address</dt>
                                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($po['address'])); ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Items</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($po['items'] as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                                <td class="text-end"><?php echo number_format($item['quantity']); ?></td>
                                                <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                                <td class="text-end">$<?php echo number_format($item['total_price'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                                            <td class="text-end">$<?php echo number_format($po['total_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Tax (18%)</strong></td>
                                            <td class="text-end">$<?php echo number_format($po['tax_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end"><strong>$<?php echo number_format($po['total_amount'] + $po['tax_amount'], 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Log -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Activity Log</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>Action</th>
                                            <th>User</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($auditLogs as $log): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $log['action_type'] === 'create' ? 'success' : 
                                                            ($log['action_type'] === 'update' ? 'primary' : 
                                                            ($log['action_type'] === 'approve' ? 'success' : 
                                                            ($log['action_type'] === 'cancel' ? 'danger' : 'info'))); 
                                                    ?>">
                                                        <?php echo ucfirst($log['action_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                                <td>
                                                    <?php 
                                                        if ($log['notes']) {
                                                            echo htmlspecialchars($log['notes']);
                                                        } else {
                                                            echo ucfirst($log['action_type']) . 'd by ' . htmlspecialchars($log['user_name']);
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process.php" method="POST">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="po_id" value="<?php echo $po['id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="deliveryDate" class="form-label">Delivery Date</label>
                            <input type="date" class="form-control" id="deliveryDate" name="delivery_date" required
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve PO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process.php" method="POST">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="po_id" value="<?php echo $po['id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cancelReason" class="form-label">Reason for Cancellation *</label>
                            <textarea class="form-control" id="cancelReason" name="reason" rows="3" required></textarea>
                            <div class="form-text">Please provide a reason for cancelling this purchase order.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Cancel PO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Delivery Date Modal -->
    <div class="modal fade" id="deliveryDateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Delivery Date</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process.php" method="POST">
                    <input type="hidden" name="action" value="update_delivery_date">
                    <input type="hidden" name="po_id" value="<?php echo $po['id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newDeliveryDate" class="form-label">New Delivery Date *</label>
                            <input type="date" class="form-control" id="newDeliveryDate" name="delivery_date" 
                                   required min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo $po['delivery_date']; ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Date</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mark as Delivered Form -->
    <form id="deliverForm" action="process.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="deliver">
        <input type="hidden" name="po_id" value="<?php echo $po['id']; ?>">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize modals
        const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
        const deliveryDateModal = new bootstrap.Modal(document.getElementById('deliveryDateModal'));

        function showApproveModal() {
            approveModal.show();
        }

        function showCancelModal() {
            cancelModal.show();
        }

        function showDeliveryDateModal() {
            deliveryDateModal.show();
        }

        function markDelivered() {
            if (confirm('Are you sure you want to mark this purchase order as delivered?')) {
                document.getElementById('deliverForm').submit();
            }
        }
    </script>
</body>
</html>
