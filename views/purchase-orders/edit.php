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

// Check edit permission
if (!$auth->hasPermission('edit_purchase_orders')) {
    header("Location: index.php?error=" . urlencode('Permission denied'));
    exit();
}

// Get PO ID
$poId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$poId) {
    header("Location: index.php?error=" . urlencode('Purchase Order ID not provided'));
    exit();
}

// Get PO details
$poController = new PurchaseOrderController($conn, $auth);
$po = $poController->getPO($poId);

if (!$po || isset($po['error'])) {
    header("Location: index.php?error=" . urlencode('Purchase Order not found'));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'delivery_date' => $_POST['delivery_date'] ?? null
    ];

    if ($auth->hasPermission('approve_purchase_orders')) {
        $data['status'] = $_POST['status'];
    }

    $result = $poController->updatePO($poId, $data);
    if (isset($result['success'])) {
        header("Location: view.php?id=$poId&success=" . urlencode('Purchase Order updated successfully'));
        exit();
    } else {
        $error = $result['error'] ?? 'Error updating purchase order';
    }
}

// Get available statuses based on permissions
$availableStatuses = ['pending' => 'Pending'];
if ($auth->hasPermission('approve_purchase_orders')) {
    $availableStatuses['approved'] = 'Approved';
    $availableStatuses['cancelled'] = 'Cancelled';
}

$pageTitle = "Edit Purchase Order";
include '../../components/layout.php';
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Purchase Order #<?php echo htmlspecialchars($po['po_number']); ?></h2>
        <div>
            <a href="view.php?id=<?php echo $poId; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" form="poForm" class="btn btn-primary">Update</button>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form id="poForm" method="POST">
        <div class="row">
            <!-- Purchase Order Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Purchase Order Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">PO Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($po['po_number']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quote Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($po['quote_number']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Delivery Date</label>
                            <input type="date" class="form-control" name="delivery_date" 
                                   value="<?php echo $po['delivery_date'] ? date('Y-m-d', strtotime($po['delivery_date'])) : ''; ?>">
                        </div>
                        <?php if ($auth->hasPermission('approve_purchase_orders')): ?>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach ($availableStatuses as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                            <?php echo $po['status'] === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($po['company_name']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($po['contact_person']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($po['email']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($po['mobile_no']); ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
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
                                <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Sub Total:</strong></td>
                                <td class="text-end"><?php echo number_format($po['total_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tax (18%):</strong></td>
                                <td class="text-end"><?php echo number_format($po['tax_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                <td class="text-end"><strong><?php echo number_format($po['total_amount'] + $po['tax_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>
