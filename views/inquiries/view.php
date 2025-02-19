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

if (!$auth->hasPermission('view_inquiries')) {
    header("Location: index.php?error=" . urlencode('Permission denied'));
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: index.php?error=" . urlencode('Invalid inquiry ID'));
    exit();
}

$result = $inquiryController->getInquiry($id);
if (!$result['success']) {
    header("Location: index.php?error=" . urlencode($result['error']));
    exit();
}

$inquiry = $result['inquiry'];

$pageTitle = 'View Inquiry';
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
                        <h2>View Inquiry</h2>
                        <div>
                            <?php if ($auth->hasPermission('edit_inquiries')): ?>
                                <a href="edit.php?id=<?php echo $inquiry['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-pencil-alt me-2"></i>Edit
                                </a>
                            <?php endif; ?>
                            <a href="index.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Inquiry Details -->
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Inquiry Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h6>Company Name</h6>
                                            <p><?php echo htmlspecialchars($inquiry['company_name']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Contact Person</h6>
                                            <p><?php echo htmlspecialchars($inquiry['contact_person']); ?></p>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h6>Email</h6>
                                            <p>
                                                <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>">
                                                    <?php echo htmlspecialchars($inquiry['email']); ?>
                                                </a>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Mobile Number</h6>
                                            <p>
                                                <a href="tel:<?php echo htmlspecialchars($inquiry['mobile_no']); ?>">
                                                    <?php echo htmlspecialchars($inquiry['mobile_no']); ?>
                                                </a>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Subject</h6>
                                        <p><?php echo htmlspecialchars($inquiry['subject']); ?></p>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Message</h6>
                                        <p class="white-space-pre-wrap"><?php echo nl2br(htmlspecialchars($inquiry['message'])); ?></p>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Created By</h6>
                                            <p><?php echo htmlspecialchars($inquiry['created_by_name'] ?? 'Unknown'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Created At</h6>
                                            <p><?php echo date('M d, Y h:i A', strtotime($inquiry['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Status</h5>
                                </div>
                                <div class="card-body">
                                    <p><?php echo htmlspecialchars($inquiry['status']); ?></p>
                                </div>
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
