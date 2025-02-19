<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $required_fields = ['company_name', 'contact_person', 'mobile_no', 'email', 'address', 'city', 'state_code', 'pin_code'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = str_replace('_', ' ', ucfirst($field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error = 'Required fields missing: ' . implode(', ', $missing_fields);
    } else {
        try {
            // Prepare data for insertion
            $data = [
                'company_name' => $_POST['company_name'],
                'address' => $_POST['address'],
                'city' => $_POST['city'],
                'state_code' => $_POST['state_code'],
                'pin_code' => $_POST['pin_code'],
                'contact_person' => $_POST['contact_person'],
                'mobile_no' => $_POST['mobile_no'],
                'telephone_no' => $_POST['telephone_no'] ?? '',
                'email' => $_POST['email'],
                'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
                'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'gst_registration_no' => $_POST['gst_registration_no'] ?? '',
                'pan_no' => $_POST['pan_no'] ?? '',
                'freight_terms' => $_POST['freight_terms'] ?? 'FREIGHT INCL',
                'hsn_sac_code' => $_POST['hsn_sac_code'] ?? '',
                'establishment_year' => !empty($_POST['establishment_year']) ? (int)$_POST['establishment_year'] : null,
                'turnover_last_year' => !empty($_POST['turnover_last_year']) ? (float)$_POST['turnover_last_year'] : null,
                'payment_terms' => $_POST['payment_terms'] ?? '100% Advance',
                'security_cheque' => isset($_POST['security_cheque']) ? 1 : 0,
                'credit_limit' => !empty($_POST['credit_limit']) ? (float)$_POST['credit_limit'] : 0,
                'equity_type' => $_POST['equity_type'] ?? ''
            ];

            require_once '../../controllers/CustomerController.php';
            $customerController = new CustomerController($conn, $auth);
            $result = $customerController->createCustomer($data);

            if ($result['success']) {
                header("Location: index.php?success=Customer created successfully");
                exit();
            } else {
                $error = "Error creating customer: " . $result['message'];
            }
        } catch (Exception $e) {
            $error = "Error creating customer: " . $e->getMessage();
        }
    }
}

$pageTitle = "Add New Customer";
include '../../includes/header.php';
?>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Add New Customer</h2>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Customers
                        </a>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Company Details -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Company Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Company Name *</label>
                                        <input type="text" class="form-control" name="company_name" required maxlength="100">
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Address *</label>
                                        <textarea class="form-control" name="address" required rows="3"></textarea>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">City *</label>
                                        <input type="text" class="form-control" name="city" required maxlength="100">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">State *</label>
                                        <select class="form-select" name="state_code" required>
                                            <option value="">Select State</option>
                                            <option value="AP">Andhra Pradesh</option>
                                            <option value="AR">Arunachal Pradesh</option>
                                            <option value="AS">Assam</option>
                                            <option value="BR">Bihar</option>
                                            <option value="CT">Chhattisgarh</option>
                                            <option value="GA">Goa</option>
                                            <option value="GJ">Gujarat</option>
                                            <option value="HR">Haryana</option>
                                            <option value="HP">Himachal Pradesh</option>
                                            <option value="JH">Jharkhand</option>
                                            <option value="KA">Karnataka</option>
                                            <option value="KL">Kerala</option>
                                            <option value="MP">Madhya Pradesh</option>
                                            <option value="MH">Maharashtra</option>
                                            <option value="MN">Manipur</option>
                                            <option value="ML">Meghalaya</option>
                                            <option value="MZ">Mizoram</option>
                                            <option value="NL">Nagaland</option>
                                            <option value="OR">Odisha</option>
                                            <option value="PB">Punjab</option>
                                            <option value="RJ">Rajasthan</option>
                                            <option value="SK">Sikkim</option>
                                            <option value="TN">Tamil Nadu</option>
                                            <option value="TG">Telangana</option>
                                            <option value="TR">Tripura</option>
                                            <option value="UP">Uttar Pradesh</option>
                                            <option value="UT">Uttarakhand</option>
                                            <option value="WB">West Bengal</option>
                                            <option value="AN">Andaman and Nicobar Islands</option>
                                            <option value="CH">Chandigarh</option>
                                            <option value="DN">Dadra and Nagar Haveli</option>
                                            <option value="DD">Daman and Diu</option>
                                            <option value="DL">Delhi</option>
                                            <option value="JK">Jammu and Kashmir</option>
                                            <option value="LA">Ladakh</option>
                                            <option value="LD">Lakshadweep</option>
                                            <option value="PY">Puducherry</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">PIN Code *</label>
                                        <input type="text" class="form-control" name="pin_code" required maxlength="10" pattern="[0-9]{6}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Contact Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Person *</label>
                                        <input type="text" class="form-control" name="contact_person" required maxlength="100">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" required maxlength="100">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Mobile No. *</label>
                                        <input type="tel" class="form-control" name="mobile_no" required maxlength="20" pattern="[0-9]{10}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Telephone No.</label>
                                        <input type="tel" class="form-control" name="telephone_no" maxlength="20">
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
                                        <input type="text" class="form-control" name="gst_registration_no" maxlength="20">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">PAN No.</label>
                                        <input type="text" class="form-control" name="pan_no" maxlength="20">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">HSN/SAC Code</label>
                                        <input type="text" class="form-control" name="hsn_sac_code" maxlength="20">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Establishment Year</label>
                                        <input type="number" class="form-control" name="establishment_year" min="1900" max="<?php echo date('Y'); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Turnover Last Year</label>
                                        <input type="number" class="form-control" name="turnover_last_year" step="0.01" min="0">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Equity Type *</label>
                                        <select class="form-select" name="equity_type" required>
                                            <option value="">Select Type</option>
                                            <option value="PUBLIC LTD.">PUBLIC LTD.</option>
                                            <option value="PVT. LTD.">PVT. LTD.</option>
                                            <option value="PARTNERSHIP">PARTNERSHIP</option>
                                            <option value="PROPRIETORSHIP">PROPRIETORSHIP</option>
                                            <option value="LLP">LLP</option>
                                            <option value="OTHER">OTHER</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms & Conditions -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Terms & Conditions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Freight Terms</label>
                                        <select class="form-select" name="freight_terms">
                                            <option value="FREIGHT INCL">FREIGHT INCL</option>
                                            <option value="FREIGHT EXTRA">FREIGHT EXTRA</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Payment Terms</label>
                                        <select class="form-select" name="payment_terms">
                                            <option value="100% Advance">100% Advance</option>
                                            <option value="50% Advance">50% Advance</option>
                                            <option value="30 Days Credit">30 Days Credit</option>
                                            <option value="45 Days Credit">45 Days Credit</option>
                                            <option value="60 Days Credit">60 Days Credit</option>
                                            <option value="90 Days Credit">90 Days Credit</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Credit Limit</label>
                                        <input type="number" class="form-control" name="credit_limit" step="0.01" min="0">
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="security_cheque" id="security_cheque">
                                            <label class="form-check-label" for="security_cheque">
                                                Security Cheque Required
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location (Hidden) -->
                        <input type="hidden" name="longitude" value="">
                        <input type="hidden" name="latitude" value="">

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
