<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AuditLogger.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

// Get permission statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'")->fetch_assoc()['count'],
    'active_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'approved' AND role != 'admin'")->fetch_assoc()['count'],
    'pending_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch_assoc()['count'],
    'permission_groups' => $conn->query("SELECT COUNT(*) as count FROM permission_groups")->fetch_assoc()['count']
];

// Get most common permissions
$commonPermissions = $conn->query("
    SELECT p.name, p.description, COUNT(up.user_id) as user_count
    FROM permissions p
    LEFT JOIN user_permissions up ON p.id = up.permission_id
    GROUP BY p.id
    ORDER BY user_count DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get recent permission changes
$recentChanges = $conn->query("
    SELECT al.*, 
           u1.username as performed_by,
           u2.username as affected_user
    FROM audit_logs al
    JOIN users u1 ON al.user_id = u1.id
    JOIN users u2 ON al.entity_id = u2.id
    WHERE al.entity_type = 'permission'
    ORDER BY al.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get permission distribution by role
$rolePermissions = $conn->query("
    SELECT u.role, COUNT(DISTINCT up.permission_id) as permission_count
    FROM users u
    LEFT JOIN user_permissions up ON u.id = up.user_id
    GROUP BY u.role
")->fetch_all(MYSQLI_ASSOC);

// Get users without any permissions
$usersWithoutPermissions = $conn->query("
    SELECT u.username, u.email, u.role, u.status
    FROM users u
    LEFT JOIN user_permissions up ON u.id = up.user_id
    WHERE up.user_id IS NULL AND u.role != 'admin'
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission Analytics - Avon System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="/avon-sys/assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <h2>Permission Analytics</h2>
                        <a href="audit_logs.php" class="btn btn-primary">View Audit Logs</a>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users</h5>
                                    <h2 class="card-text"><?php echo $stats['total_users']; ?></h2>
                                    <p class="text-muted mb-0">Excluding administrators</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Active Users</h5>
                                    <h2 class="card-text"><?php echo $stats['active_users']; ?></h2>
                                    <p class="text-muted mb-0">With approved status</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Users</h5>
                                    <h2 class="card-text"><?php echo $stats['pending_users']; ?></h2>
                                    <p class="text-muted mb-0">Awaiting approval</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Permission Groups</h5>
                                    <h2 class="card-text"><?php echo $stats['permission_groups']; ?></h2>
                                    <p class="text-muted mb-0">Active groups</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Permission Distribution Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Permission Distribution by Role</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="rolePermissionsChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Most Common Permissions -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Most Common Permissions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Permission</th>
                                                    <th>Users</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($commonPermissions as $perm): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo ucwords(str_replace('_', ' ', $perm['name'])); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo $perm['description']; ?></small>
                                                        </td>
                                                        <td><?php echo $perm['user_count']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Recent Changes -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Permission Changes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Action</th>
                                                    <th>User</th>
                                                    <th>Changed By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentChanges as $change): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, H:i', strtotime($change['created_at'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $change['action_type'] === 'create' ? 'success' : 
                                                                    ($change['action_type'] === 'update' ? 'primary' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($change['action_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($change['affected_user']); ?></td>
                                                        <td><?php echo htmlspecialchars($change['performed_by']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Without Permissions -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Users Without Permissions</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($usersWithoutPermissions)): ?>
                                        <p class="text-success mb-0">All users have permissions assigned!</p>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($usersWithoutPermissions as $user): ?>
                                                <a href="permissions.php?id=<?php echo $user['id']; ?>" 
                                                   class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h6>
                                                        <small class="text-<?php 
                                                            echo $user['status'] === 'approved' ? 'success' : 
                                                                ($user['status'] === 'pending' ? 'warning' : 'danger'); 
                                                        ?>">
                                                            <?php echo ucfirst($user['status']); ?>
                                                        </small>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo ucfirst($user['role']); ?> - 
                                                        <?php echo $user['email']; ?>
                                                    </small>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Role Permissions Chart
        const roleData = <?php echo json_encode($rolePermissions); ?>;
        const ctx = document.getElementById('rolePermissionsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: roleData.map(item => item.role),
                datasets: [{
                    label: 'Number of Permissions',
                    data: roleData.map(item => item.permission_count),
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(153, 102, 255, 0.2)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Permissions'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
