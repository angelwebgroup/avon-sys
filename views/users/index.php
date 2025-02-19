<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

// Get all users except current admin
$sql = "SELECT u.*, 
               CONCAT(u.first_name, ' ', u.last_name) as full_name,
               GROUP_CONCAT(p.name) as permissions
        FROM users u
        LEFT JOIN user_permissions up ON u.id = up.user_id
        LEFT JOIN permissions p ON up.permission_id = p.id
        WHERE u.id != ?
        GROUP BY u.id
        ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Avon System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="/avon-sys/assets/css/style.css" rel="stylesheet">
    <style>
        .user-info { font-size: 0.9em; }
        .user-info .label { font-weight: bold; color: #666; }
        .permissions-list { max-height: 100px; overflow-y: auto; }
        .table td { vertical-align: middle; }
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
                        <h2>Users</h2>
                        <div>
                            <a href="permission_groups.php" class="btn btn-info me-2">Permission Groups</a>
                            <a href="create.php" class="btn btn-primary">Create New User</a>
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

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>User Information</th>
                                            <th>Contact Details</th>
                                            <th>Role & Status</th>
                                            <th>Department Info</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="mb-1">
                                                            <strong>
                                                                <?php 
                                                                    $firstName = $user['first_name'] ?? '';
                                                                    $lastName = $user['last_name'] ?? '';
                                                                    echo $firstName || $lastName ? 
                                                                        htmlspecialchars(trim($firstName . ' ' . $lastName)) : 
                                                                        htmlspecialchars($user['username']);
                                                                ?>
                                                            </strong>
                                                        </div>
                                                        <div>
                                                            <span class="label">Username:</span> 
                                                            <?php echo htmlspecialchars($user['username']); ?>
                                                        </div>
                                                        <div>
                                                            <span class="label">Created:</span>
                                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="user-info">
                                                        <div>
                                                            <span class="label">Email:</span>
                                                            <?php echo htmlspecialchars($user['email']); ?>
                                                        </div>
                                                        <?php if (!empty($user['phone'])): ?>
                                                            <div>
                                                                <span class="label">Phone:</span>
                                                                <?php echo htmlspecialchars($user['phone']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($user['address'])): ?>
                                                            <div>
                                                                <span class="label">Address:</span>
                                                                <?php echo htmlspecialchars($user['address']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="mb-2">
                                                            <span class="badge bg-primary">
                                                                <?php echo ucfirst($user['role']); ?>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="badge bg-<?php 
                                                                echo $user['status'] === 'approved' ? 'success' : 
                                                                    ($user['status'] === 'pending' ? 'warning' : 
                                                                    ($user['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                                            ?>">
                                                                <?php echo ucfirst($user['status']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="user-info">
                                                        <?php if (!empty($user['department'])): ?>
                                                            <div>
                                                                <span class="label">Department:</span>
                                                                <?php echo htmlspecialchars($user['department']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($user['position'])): ?>
                                                            <div>
                                                                <span class="label">Position:</span>
                                                                <?php echo htmlspecialchars($user['position']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($user['permissions'])): ?>
                                                            <div class="mt-1">
                                                                <small class="text-muted">
                                                                    <?php 
                                                                        $perms = explode(',', $user['permissions']);
                                                                        echo count($perms) . ' permissions';
                                                                    ?>
                                                                </small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="edit.php?id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-primary">Edit</a>
                                                        <a href="permissions.php?id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-info">Permissions</a>
                                                        <?php if ($user['status'] === 'pending'): ?>
                                                            <form method="POST" action="process.php" class="d-inline">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="status" value="approved">
                                                                <button type="submit" class="btn btn-sm btn-success"
                                                                        onclick="return confirm('Are you sure you want to approve this user?')">
                                                                    Approve
                                                                </button>
                                                            </form>
                                                            <form method="POST" action="process.php" class="d-inline">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="status" value="rejected">
                                                                <button type="submit" class="btn btn-sm btn-danger"
                                                                        onclick="return confirm('Are you sure you want to reject this user?')">
                                                                    Reject
                                                                </button>
                                                            </form>
                                                        <?php elseif ($user['status'] === 'approved'): ?>
                                                            <form method="POST" action="process.php" class="d-inline">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="status" value="inactive">
                                                                <button type="submit" class="btn btn-sm btn-warning"
                                                                        onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                                    Deactivate
                                                                </button>
                                                            </form>
                                                        <?php elseif ($user['status'] === 'inactive' || $user['status'] === 'rejected'): ?>
                                                            <form method="POST" action="process.php" class="d-inline">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="status" value="approved">
                                                                <button type="submit" class="btn btn-sm btn-success"
                                                                        onclick="return confirm('Are you sure you want to activate this user?')">
                                                                    Activate
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
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
