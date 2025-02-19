<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/QuoteController.php';

$auth = new AuthController($conn);
$quoteController = new QuoteController($conn, $auth);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

if (!$auth->hasPermission('edit_quotes')) {
    header("Location: /avon-sys/dashboard.php?error=" . urlencode('Permission denied'));
    exit();
}

$quoteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quote = $quoteController->getQuote($quoteId);

if (!$quote || isset($quote['error'])) {
    header("Location: index.php?error=" . urlencode('Quote not found'));
    exit();
}

// Check if quote can be edited
$canEdit = !in_array($quote['status'], ['approved', 'rejected']);
if (!$canEdit) {
    header("Location: view.php?id=" . $quoteId . "&error=" . urlencode('Approved or rejected quotes cannot be edited'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'company_name' => $_POST['company_name'],
        'contact_person' => $_POST['contact_person'],
        'email' => $_POST['email'],
        'mobile_no' => $_POST['mobile_no'],
        'status' => $_POST['status'],
        'items' => []
    ];

    // Process items
    foreach ($_POST['items'] as $item) {
        if (!empty($item['description']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
            $data['items'][] = [
                'description' => $item['description'],
                'quantity' => (int)$item['quantity'],
                'unit_price' => (float)$item['unit_price']
            ];
        }
    }

    $result = $quoteController->updateQuote($quoteId, $data);
    if ($result['success']) {
        header("Location: view.php?id=" . $quoteId . "&success=Quote updated successfully");
        exit();
    } else {
        $error = $result['error'];
    }
}

// Get available statuses based on permissions
$availableStatuses = [
    'draft' => 'Draft',
    'pending' => 'Pending'
];

if ($auth->hasPermission('approve_quotes')) {
    $availableStatuses['approved'] = 'Approved';
    $availableStatuses['rejected'] = 'Rejected';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quote - Avon System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="/avon-sys/assets/css/style.css" rel="stylesheet">
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.5em;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-draft { background-color: #e2e3e5; }
        .status-pending { background-color: #fff3cd; }
        .status-approved { background-color: #d1e7dd; }
        .status-rejected { background-color: #f8d7da; }
    </style>
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
                        <h2>Edit Quote #<?php echo htmlspecialchars($quote['quote_number']); ?></h2>
                        <div>
                            <a href="view.php?id=<?php echo $quote['id']; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="quoteForm">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Company Name *</label>
                                            <input type="text" class="form-control" name="company_name"
                                                   value="<?php echo htmlspecialchars($quote['company_name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Contact Person *</label>
                                            <input type="text" class="form-control" name="contact_person"
                                                   value="<?php echo htmlspecialchars($quote['contact_person']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email *</label>
                                            <input type="email" class="form-control" name="email"
                                                   value="<?php echo htmlspecialchars($quote['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Mobile Number *</label>
                                            <input type="text" class="form-control" name="mobile_no"
                                                   value="<?php echo htmlspecialchars($quote['mobile_no']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Quote Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Quote Number</label>
                                            <input type="text" class="form-control" name="quote_number"
                                                   value="<?php echo htmlspecialchars($quote['quote_number']); ?>" readonly>
                                        </div>
                                    </div>
                                    <?php if ($auth->hasPermission('approve_quotes')): ?>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="draft" <?php echo $quote['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="pending" <?php echo $quote['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $quote['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $quote['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Quote Items</h5>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">Add Item</button>
                            </div>
                            <div class="card-body">
                                <div id="itemsContainer">
                                    <?php foreach ($quote['items'] as $index => $item): ?>
                                        <div class="row mb-3 item-row">
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" 
                                                       name="items[<?php echo $index; ?>][description]" 
                                                       placeholder="Item Description"
                                                       value="<?php echo htmlspecialchars($item['description']); ?>" 
                                                       required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" class="form-control quantity" 
                                                       name="items[<?php echo $index; ?>][quantity]" 
                                                       placeholder="Quantity"
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" class="form-control unit-price" 
                                                       name="items[<?php echo $index; ?>][unit_price]" 
                                                       placeholder="Unit Price"
                                                       value="<?php echo $item['unit_price']; ?>" 
                                                       step="0.01" min="0" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control total" 
                                                       value="$<?php echo number_format($item['total_price'], 2); ?>" 
                                                       readonly>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="row justify-content-end mt-4">
                                    <div class="col-md-4">
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Subtotal:</td>
                                                <td class="text-end" id="subtotal">$<?php echo number_format($quote['total_amount'], 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Tax (18%):</td>
                                                <td class="text-end" id="tax">$<?php echo number_format($quote['tax_amount'], 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total:</strong></td>
                                                <td class="text-end"><strong id="total">$<?php echo number_format($quote['total_amount'] + $quote['tax_amount'], 2); ?></strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="view.php?id=<?php echo $quote['id']; ?>" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Quote</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let itemIndex = <?php echo count($quote['items']); ?>;

        function addItem() {
            const container = document.getElementById('itemsContainer');
            const row = document.createElement('div');
            row.className = 'row mb-3 item-row';
            row.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" 
                           name="items[${itemIndex}][description]" 
                           placeholder="Item Description" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control quantity" 
                           name="items[${itemIndex}][quantity]" 
                           placeholder="Quantity" min="1" required>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control unit-price" 
                           name="items[${itemIndex}][unit_price]" 
                           placeholder="Unit Price" step="0.01" min="0" required>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control total" value="$0.00" readonly>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
            itemIndex++;
            
            // Add event listeners to new inputs
            const newRow = container.lastElementChild;
            addCalculationListeners(newRow);
        }

        function removeItem(button) {
            if (document.querySelectorAll('.item-row').length > 1) {
                button.closest('.item-row').remove();
                calculateTotals();
            } else {
                alert('You must have at least one item');
            }
        }

        function addCalculationListeners(row) {
            const quantity = row.querySelector('.quantity');
            const unitPrice = row.querySelector('.unit-price');
            
            quantity.addEventListener('input', () => calculateRowTotal(row));
            unitPrice.addEventListener('input', () => calculateRowTotal(row));
        }

        function calculateRowTotal(row) {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
            const total = quantity * unitPrice;
            row.querySelector('.total').value = `$${total.toFixed(2)}`;
            calculateTotals();
        }

        function calculateTotals() {
            let subtotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const total = parseFloat(row.querySelector('.total').value.replace('$', '')) || 0;
                subtotal += total;
            });
            
            const tax = subtotal * 0.18;
            const total = subtotal + tax;
            
            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;
        }

        // Add calculation listeners to existing rows
        document.querySelectorAll('.item-row').forEach(row => {
            addCalculationListeners(row);
        });

        // Form validation
        document.getElementById('quoteForm').addEventListener('submit', function(e) {
            const items = document.querySelectorAll('.item-row');
            let valid = true;
            
            items.forEach(item => {
                const description = item.querySelector('input[name*="[description]"]').value;
                const quantity = item.querySelector('input[name*="[quantity]"]').value;
                const unitPrice = item.querySelector('input[name*="[unit_price]"]').value;
                
                if (!description || !quantity || !unitPrice) {
                    valid = false;
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields for each item');
            }
        });

        // Status change warning
        document.querySelector('select[name="status"]').addEventListener('change', function(e) {
            const newStatus = e.target.value;
            const currentStatus = '<?php echo $quote['status']; ?>';
            
            if (newStatus !== currentStatus) {
                if (!confirm(`Are you sure you want to change the status to ${newStatus}?`)) {
                    e.target.value = currentStatus;
                }
            }
        });
    </script>
</body>
</html>
