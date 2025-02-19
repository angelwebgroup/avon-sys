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

if (!$auth->hasPermission('edit_inquiries')) {
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

$pageTitle = 'Edit Inquiry';
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
                        <h2>Edit Inquiry</h2>
                        <div>
                            <a href="view.php?id=<?php echo $inquiry['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to View
                            </a>
                        </div>
                    </div>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form action="process.php" method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo $inquiry['id']; ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="company_name" class="form-label">Company Name</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" 
                                               value="<?php echo htmlspecialchars($inquiry['company_name']); ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="contact_person" class="form-label">Contact Person</label>
                                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                               value="<?php echo htmlspecialchars($inquiry['contact_person']); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($inquiry['email']); ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="mobile_no" class="form-label">Mobile Number</label>
                                        <input type="text" class="form-control" id="mobile_no" name="mobile_no" 
                                               value="<?php echo htmlspecialchars($inquiry['mobile_no']); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" 
                                           value="<?php echo htmlspecialchars($inquiry['subject']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php 
                                        echo htmlspecialchars($inquiry['message']); 
                                    ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="new" <?php echo ($inquiry['status'] ?? 'new') === 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="in_progress" <?php echo ($inquiry['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo ($inquiry['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo ($inquiry['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
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
</body>
</html>
