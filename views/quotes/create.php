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

if (!$auth->hasPermission('create_quotes')) {
    header("Location: /avon-sys/dashboard.php?error=" . urlencode('Permission denied'));
    exit();
}

// Get all approved customers for dropdown
$customers = $conn->query("SELECT id, company_name FROM customers WHERE customer_approved = true ORDER BY company_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'customer_id' => $_POST['customer_id'],
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

    $result = $quoteController->createQuote($data['customer_id'], $data['items']);
    if ($result['success']) {
        header("Location: view.php?id=" . $result['quote_id'] . "&success=Quote created successfully");
        exit();
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quote - Avon System</title>
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
                        <h2>Create New Quote</h2>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
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
                                <div class="mb-3">
                                    <label class="form-label">Customer *</label>
                                    <select class="form-select" name="customer_id" required>
                                        <option value="">Select Customer</option>
                                        <?php while ($customer = $customers->fetch_assoc()): ?>
                                            <option value="<?php echo $customer['id']; ?>">
                                                <?php echo htmlspecialchars($customer['company_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
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
                                    <div class="row mb-3 item-row">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" 
                                                   name="items[0][description]" 
                                                   placeholder="Item Description" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control quantity" 
                                                   name="items[0][quantity]" 
                                                   placeholder="Quantity" min="1" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control unit-price" 
                                                   name="items[0][unit_price]" 
                                                   placeholder="Unit Price" step="0.01" min="0" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control total" value="$0.00" readonly>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row justify-content-end mt-4">
                                    <div class="col-md-4">
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Subtotal:</td>
                                                <td class="text-end" id="subtotal">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td>Tax (18%):</td>
                                                <td class="text-end" id="tax">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total:</strong></td>
                                                <td class="text-end"><strong id="total">$0.00</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">Create Quote</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let itemIndex = 1;

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
                        Remove
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
    </script>
</body>
</html>
