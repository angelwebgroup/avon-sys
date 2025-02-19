<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user details
$stmt = $conn->prepare("
    SELECT u.* 
    FROM users u 
    WHERE u.id = ?
");

if (!$stmt) {
    header("Location: index.php?error=" . urlencode('Database error: ' . $conn->error));
    exit();
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: index.php?error=" . urlencode('User not found'));
    exit();
}

// Get user's current permissions
$stmt = $conn->prepare("
    SELECT p.name 
    FROM permissions p 
    JOIN user_permissions up ON p.id = up.permission_id 
    WHERE up.user_id = ?
");

if (!$stmt) {
    header("Location: index.php?error=" . urlencode('Database error: ' . $conn->error));
    exit();
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$currentPermissions = [];
while ($row = $result->fetch_assoc()) {
    $currentPermissions[] = $row['name'];
}

// Format full name
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (empty($fullName)) {
    $fullName = $user['username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User: <?php echo htmlspecialchars($fullName); ?> - Avon System</title>
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
                        <h2>Edit User: <?php echo htmlspecialchars($fullName); ?></h2>
                        <div>
                            <a href="index.php" class="btn btn-secondary me-2">Back to Users</a>
                            <a href="permissions.php?id=<?php echo $user['id']; ?>" class="btn btn-info">
                                Manage Permissions
                            </a>
                        </div>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="process.php" method="POST" id="editUserForm">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">First Name *</label>
                                            <input type="text" class="form-control" name="first_name" 
                                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" name="last_name" 
                                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username *</label>
                                            <input type="text" class="form-control" name="username" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                            <div class="form-text">Username must be unique</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email *</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            <div class="form-text">Email must be unique</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Role *</label>
                                            <select class="form-select" name="role" required>
                                                <option value="sales_rep" <?php echo $user['role'] === 'sales_rep' ? 'selected' : ''; ?>>
                                                    Sales Representative
                                                </option>
                                                <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>
                                                    Sales Manager
                                                </option>
                                                <?php if ($auth->hasRole('admin')): ?>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                                        Administrator
                                                    </option>
                                                <?php endif; ?>
                                            </select>
                                            <div class="form-text">User's role determines their base permissions</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="password" 
                                                   minlength="6" autocomplete="new-password">
                                            <div class="form-text">Leave blank to keep current password. Minimum 6 characters.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="password_confirm" 
                                                   minlength="6" autocomplete="new-password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Additional Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea class="form-control" name="address" rows="3"><?php 
                                                echo htmlspecialchars($user['address'] ?? ''); 
                                            ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Department</label>
                                            <input type="text" class="form-control" name="department" 
                                                   value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Position</label>
                                            <input type="text" class="form-control" name="position" 
                                                   value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="index.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirm = document.querySelector('input[name="password_confirm"]').value;
            
            if (password || confirm) {
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long');
                    return;
                }
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match');
                    return;
                }
            }
            
            // Validate required fields
            const required = ['first_name', 'last_name', 'username', 'email', 'role'];
            let valid = true;
            
            required.forEach(field => {
                const input = document.querySelector(`[name="${field}"]`);
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    </script>
</body>
</html>
