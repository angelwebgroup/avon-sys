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

if (!$auth->hasPermission('view_quotes')) {
    header("Location: /avon-sys/dashboard.php?error=" . urlencode('Permission denied'));
    exit();
}

// Handle status messages
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = isset($_GET['message']) ? $_GET['message'] : '';

// Get all quotes with related information
$sql = "SELECT q.*, 
               c.company_name, 
               u1.username as created_by_name,
               u2.username as approved_by_name,
               po.po_number
        FROM quotes q 
        LEFT JOIN customers c ON q.customer_id = c.id 
        LEFT JOIN users u1 ON q.created_by = u1.id 
        LEFT JOIN users u2 ON q.approved_by = u2.id
        LEFT JOIN purchase_orders po ON q.id = po.quote_id
        ORDER BY q.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotes - Avon System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="/avon-sys/assets/css/style.css" rel="stylesheet">
    <style>
        .action-buttons .btn { margin-right: 5px; }
        .action-buttons .btn:last-child { margin-right: 0; }
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
                        <h2>Quotes</h2>
                        <?php if ($auth->hasPermission('create_quotes')): ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Create New Quote
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                                echo htmlspecialchars($_SESSION['success_message']);
                                unset($_SESSION['success_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                                echo htmlspecialchars($_SESSION['error_message']);
                                unset($_SESSION['error_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($status && $message): ?>
                        <div class="alert alert-<?php echo $status === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Quote #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Created Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($quote = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($quote['quote_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($quote['company_name']); ?></td>
                                                    <td>$<?php echo number_format($quote['total_amount'] + $quote['tax_amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $quote['status'] === 'approved' ? 'success' : 
                                                                ($quote['status'] === 'pending' ? 'warning' : 
                                                                ($quote['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                                        ?>">
                                                            <?php echo ucfirst($quote['status']); ?>
                                                        </span>
                                                        <?php if ($quote['po_number']): ?>
                                                            <span class="badge bg-info ms-1">
                                                                PO: <?php echo htmlspecialchars($quote['po_number']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($quote['created_by_name']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($quote['created_at'])); ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <?php if ($auth->hasPermission('view_quotes')): ?>
                                                                <a href="view.php?id=<?php echo $quote['id']; ?>" 
                                                                   class="btn btn-sm btn-info">View</a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($auth->hasPermission('edit_quotes') && $quote['status'] === 'draft'): ?>
                                                                <a href="edit.php?id=<?php echo $quote['id']; ?>" 
                                                                   class="btn btn-sm btn-primary">Edit</a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($auth->hasPermission('approve_quotes') && $quote['status'] === 'pending'): ?>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-success"
                                                                        onclick="approveQuote(<?php echo $quote['id']; ?>)">
                                                                    Approve
                                                                </button>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-danger"
                                                                        onclick="rejectQuote(<?php echo $quote['id']; ?>)">
                                                                    Reject
                                                                </button>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($auth->hasPermission('convert_quote_to_po') && 
                                                                      $quote['status'] === 'approved' && 
                                                                      !$quote['po_number']): ?>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-primary"
                                                                        onclick="convertToPO(<?php echo $quote['id']; ?>)">
                                                                    Convert to PO
                                                                </button>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($auth->hasPermission('delete_quotes') && $quote['status'] === 'draft'): ?>
                                                                <form action="process.php" method="POST" class="d-inline delete-quote-form">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                                            onclick="return confirm('Are you sure you want to delete this quote?')">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No quotes found</td>
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

    <!-- Action Forms -->
    <form id="approveForm" action="process.php" method="POST">
        <input type="hidden" name="action" value="approve">
        <input type="hidden" name="quote_id" id="approveQuoteId">
    </form>

    <form id="rejectForm" action="process.php" method="POST">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="quote_id" id="rejectQuoteId">
        <input type="hidden" name="reason" id="rejectReason">
    </form>

    <form id="convertForm" action="process.php" method="POST">
        <input type="hidden" name="action" value="convert">
        <input type="hidden" name="quote_id" id="convertQuoteId">
    </form>

    <!-- Reject Quote Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Quote</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modalRejectReason" class="form-label">Reason for Rejection *</label>
                        <textarea class="form-control" id="modalRejectReason" rows="3" required></textarea>
                        <div class="form-text">Please provide a reason for rejecting this quote.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="submitReject()">Reject Quote</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize modals
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        let currentQuoteId = null;

        function approveQuote(id) {
            if (confirm('Are you sure you want to approve this quote?')) {
                document.getElementById('approveQuoteId').value = id;
                document.getElementById('approveForm').submit();
            }
        }

        function rejectQuote(id) {
            currentQuoteId = id;
            rejectModal.show();
        }

        function submitReject() {
            const reason = document.getElementById('modalRejectReason').value.trim();
            if (!reason) {
                alert('Please provide a reason for rejection');
                return;
            }
            document.getElementById('rejectQuoteId').value = currentQuoteId;
            document.getElementById('rejectReason').value = reason;
            document.getElementById('rejectForm').submit();
        }

        function convertToPO(id) {
            if (confirm('Are you sure you want to convert this quote to a purchase order? This action cannot be undone.')) {
                document.getElementById('convertQuoteId').value = id;
                document.getElementById('convertForm').submit();
            }
        }
    </script>
</body>
</html>
