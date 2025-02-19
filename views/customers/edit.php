<?php
/**
 * Customer Edit Interface
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

$states = [
    'AN' => 'Andaman and Nicobar Islands',
    'AP' => 'Andhra Pradesh',
    'AR' => 'Arunachal Pradesh',
    'AS' => 'Assam',
    'BR' => 'Bihar',
    'CH' => 'Chandigarh',
    'CT' => 'Chhattisgarh',
    'DN' => 'Dadra and Nagar Haveli',
    'DD' => 'Daman and Diu',
    'DL' => 'Delhi',
    'GA' => 'Goa',
    'GJ' => 'Gujarat',
    'HR' => 'Haryana',
    'HP' => 'Himachal Pradesh',
    'JK' => 'Jammu and Kashmir',
    'JH' => 'Jharkhand',
    'KA' => 'Karnataka',
    'KL' => 'Kerala',
    'LA' => 'Ladakh',
    'LD' => 'Lakshadweep',
    'MP' => 'Madhya Pradesh',
    'MH' => 'Maharashtra',
    'MN' => 'Manipur',
    'ML' => 'Meghalaya',
    'MZ' => 'Mizoram',
    'NL' => 'Nagaland',
    'OR' => 'Odisha',
    'PY' => 'Puducherry',
    'PB' => 'Punjab',
    'RJ' => 'Rajasthan',
    'SK' => 'Sikkim',
    'TN' => 'Tamil Nadu',
    'TG' => 'Telangana',
    'TR' => 'Tripura',
    'UP' => 'Uttar Pradesh',
    'UT' => 'Uttarakhand',
    'WB' => 'West Bengal',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Avon System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <link href="/avon-sys/assets/css/style.css" rel="stylesheet">
    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
            border: none;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
            border: none;
            padding: 0.625rem 1.25rem;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #0a58ca, #084298);
        }
        .invalid-feedback {
            font-size: 0.875rem;
        }
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
                        <h2>Edit Customer</h2>
                        <a href="index.php" class="btn btn-secondary">Back to List</a>
                    </div>

                    <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Bill to Details</h5>
                            </div>
                            <div class="card-body">
                                <form action="process.php" method="post" class="needs-validation" novalidate>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Company Name *</label>
                                            <input type="text" class="form-control" name="company_name" required maxlength="100" 
                                                   value="<?php echo htmlspecialchars($customer['company_name'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Contact Person *</label>
                                            <input type="text" class="form-control" name="contact_person" required maxlength="100" 
                                                   value="<?php echo htmlspecialchars($customer['contact_person'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Email *</label>
                                            <input type="email" class="form-control" name="email" required maxlength="100" 
                                                   value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Mobile Number *</label>
                                            <input type="tel" class="form-control" name="mobile_no" required maxlength="20" 
                                                   value="<?php echo htmlspecialchars($customer['mobile_no'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label">Address *</label>
                                            <textarea class="form-control" name="address" required rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">City *</label>
                                            <input type="text" class="form-control" name="city" required maxlength="100" 
                                                   value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">State *</label>
                                            <select class="form-select" name="state_code" required>
                                                <option value="">Select State</option>
                                                <?php foreach ($states as $code => $name): ?>
                                                    <option value="<?php echo $code; ?>" <?php echo ($customer['state_code'] === $code) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">PIN Code *</label>
                                            <input type="text" class="form-control" name="pin_code" required maxlength="10" pattern="[0-9]{6}" 
                                                   value="<?php echo htmlspecialchars($customer['pin_code'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Telephone Number</label>
                                            <input type="tel" class="form-control" name="telephone_no" maxlength="20" 
                                                   value="<?php echo htmlspecialchars($customer['telephone_no'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Longitude</label>
                                            <input type="text" class="form-control" name="longitude" readonly 
                                                   value="<?php echo htmlspecialchars($customer['longitude'] ?? ''); ?>">
                                            <small class="text-muted">Auto from Mobile APP</small>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Latitude</label>
                                            <input type="text" class="form-control" name="latitude" readonly 
                                                   value="<?php echo htmlspecialchars($customer['latitude'] ?? ''); ?>">
                                            <small class="text-muted">Auto from Mobile APP</small>
                                        </div>
                                    </div>
                            </div>
                        </div>

                        <!-- Business Details -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Business Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">GST Registration No.</label>
                                        <input type="text" class="form-control" name="gst_registration_no" maxlength="20" 
                                               value="<?php echo htmlspecialchars($customer['gst_registration_no'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">PAN No.</label>
                                        <input type="text" class="form-control" name="pan_no" maxlength="10" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" 
                                               value="<?php echo htmlspecialchars($customer['pan_no'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Freight Terms *</label>
                                        <select class="form-select" name="freight_terms" required>
                                            <option value="FREIGHT INCL" <?php echo ($customer['freight_terms'] ?? '') === 'FREIGHT INCL' ? 'selected' : ''; ?>>FREIGHT INCL</option>
                                            <option value="FREIGHT EXTRA" <?php echo ($customer['freight_terms'] ?? '') === 'FREIGHT EXTRA' ? 'selected' : ''; ?>>FREIGHT EXTRA</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">HSN/SAC Code</label>
                                        <input type="text" class="form-control" name="hsn_sac_code" maxlength="20" 
                                               value="<?php echo htmlspecialchars($customer['hsn_sac_code'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Establishment Year</label>
                                        <input type="number" class="form-control" name="establishment_year" min="1900" max="<?php echo date('Y'); ?>" 
                                               value="<?php echo htmlspecialchars($customer['establishment_year'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Turnover Year</label>
                                        <input type="number" class="form-control" name="turnover_year" step="0.01" min="0" 
                                               value="<?php echo htmlspecialchars($customer['turnover_year'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Terms *</label>
                                        <select class="form-select" name="payment_terms" required>
                                            <option value="100% Advance" <?php echo ($customer['payment_terms'] ?? '') === '100% Advance' ? 'selected' : ''; ?>>100% Advance</option>
                                            <option value="50% Advance" <?php echo ($customer['payment_terms'] ?? '') === '50% Advance' ? 'selected' : ''; ?>>50% Advance</option>
                                            <option value="Credit" <?php echo ($customer['payment_terms'] ?? '') === 'Credit' ? 'selected' : ''; ?>>Credit</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Security Cheque</label>
                                        <select class="form-select" name="security_cheque">
                                            <option value="0" <?php echo !($customer['security_cheque'] ?? false) ? 'selected' : ''; ?>>No</option>
                                            <option value="1" <?php echo ($customer['security_cheque'] ?? false) ? 'selected' : ''; ?>>Yes</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Credit Limit</label>
                                        <input type="number" class="form-control" name="credit_limit" step="0.01" min="0" 
                                               value="<?php echo htmlspecialchars($customer['credit_limit'] ?? '0'); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Enterprise Type *</label>
                                        <select class="form-select" name="enterprise_type" required>
                                            <option value="">Select Type</option>
                                            <option value="Large" <?php echo ($customer['enterprise_type'] ?? '') === 'Large' ? 'selected' : ''; ?>>Large</option>
                                            <option value="Medium" <?php echo ($customer['enterprise_type'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="Small" <?php echo ($customer['enterprise_type'] ?? '') === 'Small' ? 'selected' : ''; ?>>Small</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Equity Type *</label>
                                        <select class="form-select" name="equity_type" required>
                                            <option value="">Select Type</option>
                                            <option value="PUBLIC LTD." <?php echo ($customer['equity_type'] ?? '') === 'PUBLIC LTD.' ? 'selected' : ''; ?>>PUBLIC LTD.</option>
                                            <option value="PRIVATE LTD." <?php echo ($customer['equity_type'] ?? '') === 'PRIVATE LTD.' ? 'selected' : ''; ?>>PRIVATE LTD.</option>
                                            <option value="PARTNERSHIP" <?php echo ($customer['equity_type'] ?? '') === 'PARTNERSHIP' ? 'selected' : ''; ?>>PARTNERSHIP</option>
                                            <option value="PROPRIETORSHIP" <?php echo ($customer['equity_type'] ?? '') === 'PROPRIETORSHIP' ? 'selected' : ''; ?>>PROPRIETORSHIP</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Product Segment *</label>
                                        <select class="form-select" name="product_segment" required>
                                            <option value="">Select Segment</option>
                                            <option value="WHITE GOODS" <?php echo ($customer['product_segment'] ?? '') === 'WHITE GOODS' ? 'selected' : ''; ?>>WHITE GOODS</option>
                                            <option value="BROWN GOODS" <?php echo ($customer['product_segment'] ?? '') === 'BROWN GOODS' ? 'selected' : ''; ?>>BROWN GOODS</option>
                                            <option value="BOTH" <?php echo ($customer['product_segment'] ?? '') === 'BOTH' ? 'selected' : ''; ?>>BOTH</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Coordinator Name</label>
                                        <input type="text" class="form-control" name="coordinator_name" maxlength="100" 
                                               value="<?php echo htmlspecialchars($customer['coordinator_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">Remarks</label>
                                        <textarea class="form-control" name="remarks" rows="3"><?php echo htmlspecialchars($customer['remarks'] ?? ''); ?></textarea>
                                    </div>

                                    <?php if ($auth->hasRole('admin')): ?>
                                    <div class="col-md-6">
                                        <label class="form-label">Approval Status</label>
                                        <select class="form-select" name="customer_approved">
                                            <option value="0" <?php echo !($customer['customer_approved'] ?? false) ? 'selected' : ''; ?>>Pending</option>
                                            <option value="1" <?php echo ($customer['customer_approved'] ?? false) ? 'selected' : ''; ?>>Approved</option>
                                        </select>
                                    </div>
                                    <?php endif; ?>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                                                    <i class="bi bi-arrow-left"></i> Back
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save"></i> Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
    </script>
</body>
</html>
