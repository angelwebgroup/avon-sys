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

$quoteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quote = $quoteController->getQuote($quoteId);

if (!$quote || isset($quote['error'])) {
    header("Location: index.php?error=" . urlencode('Quote not found'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Quote - Avon System</title>
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
                        <h2>Quote Details</h2>
                        <div>
                            <?php if ($auth->hasPermission('edit_quotes') && $quote['status'] === 'draft'): ?>
                                <a href="edit.php?id=<?php echo $quote['id']; ?>" class="btn btn-primary me-2">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                            <?php endif; ?>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
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

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Quote Information</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4">Quote Number</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($quote['quote_number']); ?></dd>

                                        <dt class="col-sm-4">Status</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge bg-<?php 
                                                echo $quote['status'] === 'approved' ? 'success' : 
                                                    ($quote['status'] === 'pending' ? 'warning' : 
                                                    ($quote['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                            ?>">
                                                <?php echo ucfirst($quote['status']); ?>
                                            </span>
                                        </dd>

                                        <dt class="col-sm-4">Created By</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($quote['created_by_name']); ?></dd>

                                        <dt class="col-sm-4">Created Date</dt>
                                        <dd class="col-sm-8"><?php echo date('M d, Y H:i', strtotime($quote['created_at'])); ?></dd>

                                        <?php if ($quote['approved_by_name']): ?>
                                            <dt class="col-sm-4">Approved By</dt>
                                            <dd class="col-sm-8"><?php echo htmlspecialchars($quote['approved_by_name']); ?></dd>
                                        <?php endif; ?>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Company Name:</strong><br>
                                                <?php echo htmlspecialchars($quote['company_name']); ?>
                                            </p>
                                            <p><strong>Contact Person:</strong><br>
                                                <?php echo htmlspecialchars($quote['contact_person']); ?>
                                            </p>
                                            <p><strong>Email:</strong><br>
                                                <?php echo htmlspecialchars($quote['email']); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Mobile Number:</strong><br>
                                                <?php echo htmlspecialchars($quote['mobile_no']); ?>
                                            </p>
                                            <p><strong>Telephone Number:</strong><br>
                                                <?php echo htmlspecialchars($quote['telephone_no'] ?? 'N/A'); ?>
                                            </p>
                                            <p><strong>Address:</strong><br>
                                                <?php 
                                                    echo nl2br(htmlspecialchars($quote['address'])) . '<br>';
                                                    echo htmlspecialchars($quote['city']) . ', ';
                                                    echo htmlspecialchars($quote['state_code']) . ' - ';
                                                    echo htmlspecialchars($quote['pin_code']);
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quote Items</h5>
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
                                        <?php foreach ($quote['items'] as $item): ?>
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
                                            <td class="text-end">$<?php echo number_format($quote['total_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Tax (18%)</strong></td>
                                            <td class="text-end">$<?php echo number_format($quote['tax_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end"><strong>$<?php echo number_format($quote['total_amount'] + $quote['tax_amount'], 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
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
        <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
    </form>

    <form id="rejectForm" action="process.php" method="POST">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
        <input type="hidden" name="reason" id="rejectReason">
    </form>

    <form id="convertForm" action="process.php" method="POST">
        <input type="hidden" name="action" value="convert">
        <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
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

        function approveQuote() {
            if (confirm('Are you sure you want to approve this quote?')) {
                document.getElementById('approveForm').submit();
            }
        }

        function rejectQuote() {
            rejectModal.show();
        }

        function submitReject() {
            const reason = document.getElementById('modalRejectReason').value.trim();
            if (!reason) {
                alert('Please provide a reason for rejection');
                return;
            }
            document.getElementById('rejectReason').value = reason;
            document.getElementById('rejectForm').submit();
        }

        function convertToPO() {
            if (confirm('Are you sure you want to convert this quote to a purchase order? This action cannot be undone.')) {
                document.getElementById('convertForm').submit();
            }
        }
    </script>
</body>
</html>
