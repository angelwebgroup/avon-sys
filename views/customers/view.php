<?php
/**
 * Customer View Interface
 * 
 * @package    AvonSystem
 * @subpackage Views/Customers
 * @version    2.0.0
 * @since      2025-02-18
 */

require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/CustomerController.php';

$auth = new AuthController($conn);
$customerController = new CustomerController($conn, $auth);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

// Get the customer ID from the URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : die('ERROR: ID not found.');

// Get customer details
try {
    $customer = $customerController->getCustomerById($id);
    if (!$customer) {
        die('ERROR: Customer not found.');
    }
} catch (Exception $e) {
    die('ERROR: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customer - Avon System</title>
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
                        <h2>Customer Details</h2>
                        <div>
                            <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </a>
                            <a href="index.php" class="btn btn-secondary">Back to List</a>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Address Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <p class="form-control-static"><?php echo nl2br(htmlspecialchars($customer['address'])); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($customer['city']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($customer['state_code']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">PIN Code</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($customer['pin_code']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Contact Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Contact Person</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['contact_person'] ?? ''); ?></dd>

                                        <dt class="col-sm-4">Mobile No.</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['mobile_no'] ?? ''); ?></dd>

                                        <dt class="col-sm-4">Telephone No.</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['telephone_no'] ?? ''); ?></dd>

                                        <dt class="col-sm-4">Email ID</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['email'] ?? ''); ?></dd>

                                        <dt class="col-sm-4">Longitude</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['longitude'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Latitude</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['latitude'] ?? 'N/A'); ?></dd>
                                    </dl>
                                </div>

                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">GST Registration No.</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['gst_registration_no'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">PAN No.</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['pan_no'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Freight Terms</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['freight_terms'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">HSN/SAC Code</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['hsn_sac_code'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Establishment Year</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['establishment_year'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Turnover Year</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['turnover_year'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Payment Terms</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['payment_terms'] ?? 'N/A'); ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Business Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Security Cheque</dt>
                                        <dd class="col-sm-8"><?php echo ($customer['security_cheque'] ?? false) ? 'Yes' : 'No'; ?></dd>

                                        <dt class="col-sm-4">Credit Limit</dt>
                                        <dd class="col-sm-8"><?php echo number_format($customer['credit_limit'] ?? 0, 2); ?></dd>

                                        <dt class="col-sm-4">Enterprise Type</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['enterprise_type'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Equity Type</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['equity_type'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Product Segment</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['product_segment'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Coordinator Name</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['coordinator_name'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Remarks</dt>
                                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($customer['remarks'] ?? 'N/A')); ?></dd>
                                    </dl>
                                </div>

                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Company Name</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['company_name'] ?? ''); ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Status Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Approval Status</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge bg-<?php echo ($customer['customer_approved'] ?? false) ? 'success' : 'warning'; ?>">
                                                <?php echo ($customer['customer_approved'] ?? false) ? 'Approved' : 'Pending'; ?>
                                            </span>
                                        </dd>

                                        <dt class="col-sm-4">Registered By</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['created_by_name'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Registration Date</dt>
                                        <dd class="col-sm-8"><?php echo isset($customer['created_at']) ? date('F j, Y', strtotime($customer['created_at'])) : 'N/A'; ?></dd>
                                    </dl>
                                </div>

                                <?php if (!empty($customer['customer_approved'])): ?>
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Approved By</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($customer['approved_by_name'] ?? 'N/A'); ?></dd>

                                        <dt class="col-sm-4">Approval Date</dt>
                                        <dd class="col-sm-8"><?php echo isset($customer['approved_date']) ? date('F j, Y', strtotime($customer['approved_date'])) : 'N/A'; ?></dd>
                                    </dl>
                                </div>
                                <?php endif; ?>
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
