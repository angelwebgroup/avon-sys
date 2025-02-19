<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/InquiryController.php';

$auth = new AuthController($conn);
$inquiryController = new InquiryController($conn, $auth);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

if (!$auth->hasPermission('create_inquiries')) {
    header("Location: /avon-sys/views/inquiries/index.php?error=" . urlencode('Permission denied'));
    exit();
}

$pageTitle = 'Create Inquiry';
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
                        <h2>Create New Inquiry</h2>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                    </div>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form action="process.php" method="POST" id="inquiryForm">
                                <input type="hidden" name="action" value="create">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="company_name" class="form-label">Company Name*</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_person" class="form-label">Contact Person*</label>
                                        <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email*</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="mobile_no" class="form-label">Mobile Number*</label>
                                        <input type="tel" class="form-control" id="mobile_no" name="mobile_no" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject*</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                </div>

                                <div class="mb-3">
                                    <label for="message" class="form-label">Message*</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Inquiry
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('inquiryForm').addEventListener('submit', function(e) {
            const requiredFields = ['company_name', 'contact_person', 'email', 'mobile_no', 'subject', 'message'];
            let isValid = true;

            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    </script>
</body>
</html>
