<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AuditLogger.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$logger = new AuditLogger($conn, $_SESSION['user_id']);

// Get user ID filter if provided
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Get all users for filter
$usersQuery = "SELECT id, username FROM users ORDER BY username";
$users = $conn->query($usersQuery)->fetch_all(MYSQLI_ASSOC);

// Get permission logs
$logs = $logger->getPermissionLogs($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission Audit Logs - Avon System</title>
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
                        <h2>Permission Audit Logs</h2>
                        <div>
                            <form class="d-flex gap-2">
                                <select class="form-select" name="user_id" onchange="this.form.submit()">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo $userId === $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($userId): ?>
                                    <a href="audit_logs.php" class="btn btn-secondary">Clear Filter</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>Action</th>
                                            <th>Performed By</th>
                                            <th>Affected User</th>
                                            <th>Changes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $log['action_type'] === 'create' ? 'success' : 
                                                            ($log['action_type'] === 'update' ? 'primary' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($log['action_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['performed_by']); ?></td>
                                                <td><?php echo htmlspecialchars($log['affected_user']); ?></td>
                                                <td>
                                                    <?php if ($log['action_type'] === 'update'): ?>
                                                        <?php
                                                            $oldValue = json_decode($log['old_value'], true);
                                                            $newValue = json_decode($log['new_value'], true);
                                                            
                                                            if (isset($oldValue['permissions']) && isset($newValue['permissions'])) {
                                                                $added = array_diff($newValue['permissions'], $oldValue['permissions']);
                                                                $removed = array_diff($oldValue['permissions'], $newValue['permissions']);
                                                                
                                                                if (!empty($added)) {
                                                                    echo '<div class="text-success">Added: ' . implode(', ', $added) . '</div>';
                                                                }
                                                                if (!empty($removed)) {
                                                                    echo '<div class="text-danger">Removed: ' . implode(', ', $removed) . '</div>';
                                                                }
                                                            }
                                                        ?>
                                                    <?php elseif ($log['action_type'] === 'create'): ?>
                                                        <?php
                                                            $newValue = json_decode($log['new_value'], true);
                                                            if (isset($newValue['permissions'])) {
                                                                echo '<div class="text-success">Granted: ' . implode(', ', $newValue['permissions']) . '</div>';
                                                            }
                                                        ?>
                                                    <?php elseif ($log['action_type'] === 'delete'): ?>
                                                        <?php
                                                            $oldValue = json_decode($log['old_value'], true);
                                                            if (isset($oldValue['permissions'])) {
                                                                echo '<div class="text-danger">Revoked: ' . implode(', ', $oldValue['permissions']) . '</div>';
                                                            }
                                                        ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
