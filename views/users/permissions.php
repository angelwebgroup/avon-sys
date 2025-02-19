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
$stmt = $conn->prepare("SELECT username, role, status FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit();
}

// Get permission groups
$groupsQuery = "SELECT * FROM permission_groups ORDER BY name";
$groups = $conn->query($groupsQuery)->fetch_all(MYSQLI_ASSOC);

// Get user's permissions
$permissions = $auth->getUserPermissions($userId);

// Handle permission updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['permission_group'])) {
        // Apply permission group
        $groupId = (int)$_POST['permission_group'];
        
        // First, revoke all permissions
        foreach ($permissions as $perm) {
            $auth->revokePermission($userId, $perm['name']);
        }
        
        // Get permissions from the selected group
        $groupPermsQuery = "SELECT p.name 
                           FROM permissions p 
                           JOIN permission_group_items pgi ON p.id = pgi.permission_id 
                           WHERE pgi.group_id = ?";
        $stmt = $conn->prepare($groupPermsQuery);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $groupPerms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Grant group permissions
        foreach ($groupPerms as $perm) {
            $auth->grantPermission($userId, $perm['name']);
        }
        
        $success = "Permission group applied successfully!";
    } else {
        // Handle individual permission updates
        $newPermissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        
        // First, revoke all permissions
        foreach ($permissions as $perm) {
            $auth->revokePermission($userId, $perm['name']);
        }
        
        // Then grant selected permissions
        foreach ($newPermissions as $permName) {
            $auth->grantPermission($userId, $permName);
        }
        
        $success = "Individual permissions updated successfully!";
    }
    
    // Refresh permissions list
    $permissions = $auth->getUserPermissions($userId);
}

// Group permissions by category
$permissionCategories = [
    'Customers' => array_filter($permissions, fn($p) => strpos($p['name'], 'customer') !== false),
    'Quotes' => array_filter($permissions, fn($p) => strpos($p['name'], 'quote') !== false),
    'Purchase Orders' => array_filter($permissions, fn($p) => strpos($p['name'], 'purchase_order') !== false),
    'Inquiries' => array_filter($permissions, fn($p) => strpos($p['name'], 'inquir') !== false),
    'Reports' => array_filter($permissions, fn($p) => strpos($p['name'], 'report') !== false || strpos($p['name'], 'dashboard') !== false),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Permissions - Avon System</title>
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
                        <h2>Manage Permissions: <?php echo htmlspecialchars($user['username']); ?></h2>
                        <a href="index.php" class="btn btn-secondary">Back to Users</a>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">User Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Role:</strong> <?php echo ucfirst($user['role']); ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?php 
                                        echo $user['status'] === 'approved' ? 'success' : 
                                            ($user['status'] === 'pending' ? 'warning' : 
                                            ($user['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="process.php">
                        <input type="hidden" name="action" value="update_permissions">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Apply Permission Group</h5>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-end">
                                    <div class="col-md-8">
                                        <label for="permission_group" class="form-label">Select Permission Group</label>
                                        <select class="form-select" id="permission_group" name="group_id">
                                            <option value="">Select a group...</option>
                                            <?php foreach ($groups as $group): ?>
                                                <option value="<?php echo $group['id']; ?>">
                                                    <?php echo htmlspecialchars($group['name']); ?> - 
                                                    <?php echo htmlspecialchars($group['description']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-primary w-100" 
                                                onclick="applyPermissionGroup()">Apply Group</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Individual Permissions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($permissionCategories as $category => $categoryPermissions): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0"><?php echo $category; ?></h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php foreach ($categoryPermissions as $permission): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="permissions[]" 
                                                                   value="<?php echo $permission['name']; ?>"
                                                                   id="perm_<?php echo $permission['name']; ?>"
                                                                   <?php echo $permission['granted_by'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="perm_<?php echo $permission['name']; ?>">
                                                                <strong><?php echo ucwords(str_replace('_', ' ', $permission['name'])); ?></strong>
                                                                <p class="text-muted mb-0 small"><?php echo $permission['description']; ?></p>
                                                                <?php if ($permission['granted_by']): ?>
                                                                    <small class="text-muted">
                                                                        Granted by <?php echo htmlspecialchars($permission['granted_by']); ?> 
                                                                        on <?php echo date('M d, Y', strtotime($permission['created_at'])); ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">Update Individual Permissions</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Apply Group Form -->
                    <form id="applyGroupForm" action="process.php" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="apply_group">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                        <input type="hidden" name="group_id" id="applyGroupId">
                    </form>

                    <script>
                        function applyPermissionGroup() {
                            const select = document.getElementById('permission_group');
                            const groupId = select.value;
                            
                            if (!groupId) {
                                alert('Please select a permission group');
                                return;
                            }
                            
                            if (confirm('Are you sure you want to apply this permission group? This will replace all existing permissions.')) {
                                document.getElementById('applyGroupId').value = groupId;
                                document.getElementById('applyGroupForm').submit();
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
