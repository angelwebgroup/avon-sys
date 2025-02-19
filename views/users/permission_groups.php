<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

// Get all permission groups
$groupsQuery = "SELECT pg.*, u.username as created_by_name,
                (SELECT COUNT(*) FROM permission_group_items WHERE group_id = pg.id) as permission_count
                FROM permission_groups pg
                LEFT JOIN users u ON pg.created_by = u.id
                ORDER BY pg.name";
$groups = $conn->query($groupsQuery)->fetch_all(MYSQLI_ASSOC);

// Get all available permissions
$permissionsQuery = "SELECT * FROM permissions ORDER BY name";
$permissions = $conn->query($permissionsQuery)->fetch_all(MYSQLI_ASSOC);

// Group permissions by category
$permissionCategories = [];
foreach ($permissions as $permission) {
    $category = explode('_', $permission['name'])[0];
    $category = ucfirst($category);
    $permissionCategories[$category][] = $permission;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission Groups - Avon System</title>
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
                        <h2>Permission Groups</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                            Create New Group
                        </button>
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
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Permissions</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($groups as $group): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($group['name']); ?></td>
                                                <td><?php echo htmlspecialchars($group['description']); ?></td>
                                                <td><?php echo $group['permission_count']; ?> permissions</td>
                                                <td><?php echo htmlspecialchars($group['created_by_name']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($group['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-info"
                                                                onclick="viewGroup(<?php echo $group['id']; ?>)">
                                                            View
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                                onclick="editGroup(<?php echo $group['id']; ?>)">
                                                            Edit
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                                onclick="deleteGroup(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>')">
                                                            Delete
                                                        </button>
                                                    </div>
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

    <!-- Create Group Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Permission Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="group_process.php" method="POST">
                    <input type="hidden" name="action" value="create_group">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Group Name *</label>
                            <input type="text" class="form-control" name="name" required>
                            <div class="form-text">Enter a unique name for this permission group</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="2" required></textarea>
                            <div class="form-text">Provide a brief description of this permission group</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <div class="row">
                                <?php foreach ($permissionCategories as $category => $categoryPermissions): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="form-check">
                                                    <input class="form-check-input category-checkbox" type="checkbox" 
                                                           data-category="<?php echo $category; ?>">
                                                    <label class="form-check-label">
                                                        <strong><?php echo $category; ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($categoryPermissions as $permission): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" 
                                                               type="checkbox"
                                                               name="permissions[]" 
                                                               value="<?php echo $permission['name']; ?>"
                                                               data-category="<?php echo $category; ?>">
                                                        <label class="form-check-label">
                                                            <?php echo ucwords(str_replace('_', ' ', $permission['name'])); ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo $permission['description']; ?></small>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Group Modal -->
    <div class="modal fade" id="viewGroupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Permission Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="viewGroupContent">
                        Loading...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Group Form -->
    <form id="deleteGroupForm" action="group_process.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_group">
        <input type="hidden" name="group_id" id="deleteGroupId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize modals
        const viewGroupModal = new bootstrap.Modal(document.getElementById('viewGroupModal'));

        // Category checkboxes
        document.querySelectorAll('.category-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const category = this.dataset.category;
                const checked = this.checked;
                document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`)
                    .forEach(permCheckbox => {
                        permCheckbox.checked = checked;
                    });
            });
        });

        // Update category checkbox when all permissions are checked/unchecked
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const category = this.dataset.category;
                const categoryCheckbox = document.querySelector(`.category-checkbox[data-category="${category}"]`);
                const categoryPermissions = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
                const allChecked = Array.from(categoryPermissions).every(perm => perm.checked);
                categoryCheckbox.checked = allChecked;
            });
        });

        function viewGroup(id) {
            fetch(`get_group.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewGroupContent').innerHTML = html;
                    viewGroupModal.show();
                });
        }

        function editGroup(id) {
            window.location.href = `edit_group.php?id=${id}`;
        }

        function deleteGroup(id, name) {
            if (confirm(`Are you sure you want to delete the permission group "${name}"? This action cannot be undone.`)) {
                document.getElementById('deleteGroupId').value = id;
                document.getElementById('deleteGroupForm').submit();
            }
        }
    </script>
</body>
</html>
