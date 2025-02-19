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

// Get all inquiries
$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_filter' => $_GET['date_filter'] ?? '',
    'created_by' => $_GET['created_by'] ?? '',
];
$inquiries = $inquiryController->getInquiries($filters);

// Get all users for Created By filter
$users = $inquiryController->getUsers();

$pageTitle = 'Inquiries';
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
                        <h2>Inquiries</h2>
                        <?php if ($auth->hasPermission('create_inquiries')): ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Create New Inquiry
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get" action="">
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6 col-md-2">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">All Status</option>
                                            <option value="new" <?php echo isset($_GET['status']) && $_GET['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                            <option value="in_progress" <?php echo isset($_GET['status']) && $_GET['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-2">
                                        <label for="created_by" class="form-label">Created By</label>
                                        <select class="form-select" id="created_by" name="created_by">
                                            <option value="" selected>All Users</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['username']; ?>" <?php echo isset($_GET['created_by']) && $_GET['created_by'] === $user['username'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-2">
                                        <label for="date_filter" class="form-label">Date Filter</label>
                                        <select class="form-select" id="date_filter" name="date_filter">
                                            <option value="">All Time</option>
                                            <option value="today" <?php echo isset($_GET['date_filter']) && $_GET['date_filter'] === 'today' ? 'selected' : ''; ?>>Today</option>
                                            <option value="yesterday" <?php echo isset($_GET['date_filter']) && $_GET['date_filter'] === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                            <option value="last_7_days" <?php echo isset($_GET['date_filter']) && $_GET['date_filter'] === 'last_7_days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                            <option value="this_month" <?php echo isset($_GET['date_filter']) && $_GET['date_filter'] === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                            <option value="last_month" <?php echo isset($_GET['date_filter']) && $_GET['date_filter'] === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" 
                                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                                               placeholder="Search inquiries...">
                                    </div>
                                    <div class="col-12 col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100 mt-2 mt-md-0">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Inquiries Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Company</th>
                                            <th>Subject</th>
                                            <th>Contact Person</th>
                                            <th>Date Created</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($inquiries && count($inquiries) > 0): ?>
                                            <?php foreach ($inquiries as $inquiry): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($inquiry['company_name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($inquiry['subject'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($inquiry['contact_person'] ?? ''); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo match($inquiry['status'] ?? 'new') {
                                                                'new' => 'primary',
                                                                'in_progress' => 'warning',
                                                                'completed' => 'success',
                                                                'cancelled' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucwords(str_replace('_', ' ', $inquiry['status'] ?? 'new')); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view.php?id=<?php echo $inquiry['id']; ?>" 
                                                               class="btn btn-sm btn-info" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($auth->hasPermission('edit_inquiries')): ?>
                                                                <a href="edit.php?id=<?php echo $inquiry['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                    <i class="fas fa-pencil-alt"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No inquiries found.</td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
