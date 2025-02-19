<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$response = ['success' => false, 'message' => 'Invalid action'];
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    switch ($action) {
        case 'update_status':
            $status = $_POST['status'] ?? '';
            if (!in_array($status, ['approved', 'rejected', 'inactive', 'active'])) {
                throw new Exception('Invalid status');
            }
            
            // Get current user data
            $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $currentUser = $stmt->get_result()->fetch_assoc();
            
            if (!$currentUser) {
                throw new Exception('User not found');
            }
            
            // Update user status
            $stmt = $conn->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("si", $status, $userId);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update user status');
            }
            
            // Log the status change
            $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                         VALUES ({$_SESSION['user_id']}, 'status_change', 'user', $userId, 
                         'Status changed from {$currentUser['status']} to {$status}')");
            
            $response = [
                'success' => true,
                'message' => 'User status updated successfully',
                'redirect' => "index.php?success=User status updated successfully"
            ];
            break;

        case 'update_permissions':
            $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
            
            $conn->begin_transaction();
            
            try {
                // Get current permissions for audit log
                $stmt = $conn->prepare("
                    SELECT p.name 
                    FROM permissions p 
                    JOIN user_permissions up ON p.id = up.permission_id 
                    WHERE up.user_id = ?
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $oldPermissions = [];
                while ($row = $result->fetch_assoc()) {
                    $oldPermissions[] = $row['name'];
                }
                
                // Remove all current permissions
                $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                
                // Add new permissions
                if (!empty($permissions)) {
                    $stmt = $conn->prepare("
                        INSERT INTO user_permissions (user_id, permission_id, granted_by) 
                        SELECT ?, id, ? FROM permissions WHERE name = ?
                    ");
                    
                    foreach ($permissions as $permission) {
                        $stmt->bind_param("iis", $userId, $_SESSION['user_id'], $permission);
                        if (!$stmt->execute()) {
                            throw new Exception("Error granting permission: $permission");
                        }
                    }
                }
                
                // Log the permission changes
                $changes = [
                    'removed' => array_diff($oldPermissions, $permissions),
                    'added' => array_diff($permissions, $oldPermissions)
                ];
                
                $changeNotes = [];
                if (!empty($changes['removed'])) {
                    $changeNotes[] = "Removed: " . implode(", ", $changes['removed']);
                }
                if (!empty($changes['added'])) {
                    $changeNotes[] = "Added: " . implode(", ", $changes['added']);
                }
                
                if (!empty($changeNotes)) {
                    $notes = implode(" | ", $changeNotes);
                    $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                                VALUES ({$_SESSION['user_id']}, 'permission_change', 'user', $userId, 
                                '" . $conn->real_escape_string($notes) . "')");
                }
                
                $conn->commit();
                $response = [
                    'success' => true,
                    'message' => 'User permissions updated successfully',
                    'redirect' => "permissions.php?id=$userId&success=Permissions updated successfully"
                ];
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        case 'apply_group':
            $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
            if (!$groupId) {
                throw new Exception('Invalid permission group');
            }
            
            $conn->begin_transaction();
            
            try {
                // Get current permissions for audit log
                $stmt = $conn->prepare("
                    SELECT p.name 
                    FROM permissions p 
                    JOIN user_permissions up ON p.id = up.permission_id 
                    WHERE up.user_id = ?
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $oldPermissions = [];
                while ($row = $result->fetch_assoc()) {
                    $oldPermissions[] = $row['name'];
                }
                
                // Remove all current permissions
                $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                
                // Add permissions from group
                $stmt = $conn->prepare("
                    INSERT INTO user_permissions (user_id, permission_id, granted_by)
                    SELECT ?, pgi.permission_id, ?
                    FROM permission_group_items pgi
                    WHERE pgi.group_id = ?
                ");
                $stmt->bind_param("iii", $userId, $_SESSION['user_id'], $groupId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to apply permission group');
                }
                
                // Get group name and new permissions for audit log
                $stmt = $conn->prepare("
                    SELECT pg.name as group_name, GROUP_CONCAT(p.name) as permissions
                    FROM permission_groups pg
                    JOIN permission_group_items pgi ON pg.id = pgi.group_id
                    JOIN permissions p ON pgi.permission_id = p.id
                    WHERE pg.id = ?
                    GROUP BY pg.id
                ");
                $stmt->bind_param("i", $groupId);
                $stmt->execute();
                $groupInfo = $stmt->get_result()->fetch_assoc();
                
                if ($groupInfo) {
                    $newPermissions = explode(',', $groupInfo['permissions']);
                    $changes = [
                        'removed' => array_diff($oldPermissions, $newPermissions),
                        'added' => array_diff($newPermissions, $oldPermissions)
                    ];
                    
                    $notes = "Applied permission group: {$groupInfo['group_name']} | ";
                    if (!empty($changes['removed'])) {
                        $notes .= "Removed: " . implode(", ", $changes['removed']) . " | ";
                    }
                    if (!empty($changes['added'])) {
                        $notes .= "Added: " . implode(", ", $changes['added']);
                    }
                    
                    $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                                VALUES ({$_SESSION['user_id']}, 'permission_group_applied', 'user', $userId, 
                                '" . $conn->real_escape_string($notes) . "')");
                }
                
                $conn->commit();
                $response = [
                    'success' => true,
                    'message' => 'Permission group applied successfully',
                    'redirect' => "permissions.php?id=$userId&success=Permission group applied successfully"
                ];
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        case 'update_user':
            $userId = (int)$_POST['user_id'];
                    
            // Validate input
            $required = ['username', 'email', 'first_name', 'last_name', 'role'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(ucfirst($field) . ' is required');
                }
            }
                    
            // Check if username or email is already taken
            $stmt = $conn->prepare("
                SELECT id FROM users 
                WHERE (username = ? OR email = ?) 
                AND id != ?
            ");
            $stmt->bind_param("ssi", $_POST['username'], $_POST['email'], $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('Username or email already exists');
            }
                    
            $conn->begin_transaction();
                    
            try {
                // Get current user data for comparison
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $oldData = $stmt->get_result()->fetch_assoc();
                        
                if (!$oldData) {
                    throw new Exception('User not found');
                }
                        
                // Build update query
                $updateFields = [
                    'username = ?',
                    'email = ?',
                    'first_name = ?',
                    'last_name = ?',
                    'phone = ?',
                    'address = ?',
                    'department = ?',
                    'position = ?',
                    'role = ?'
                ];
                        
                $params = [
                    $_POST['username'],
                    $_POST['email'],
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['phone'] ?? null,
                    $_POST['address'] ?? null,
                    $_POST['department'] ?? null,
                    $_POST['position'] ?? null,
                    $_POST['role']
                ];
                $types = "sssssssss";
                        
                // Add password if provided
                if (!empty($_POST['password'])) {
                    if (strlen($_POST['password']) < 6) {
                        throw new Exception('Password must be at least 6 characters');
                    }
                    if ($_POST['password'] !== $_POST['password_confirm']) {
                        throw new Exception('Passwords do not match');
                    }
                            
                    $updateFields[] = 'password = ?';
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $types .= "s";
                }
                        
                // Add user ID to params
                $params[] = $userId;
                $types .= "i";
                        
                $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                        
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update user');
                }
                        
                // Log changes
                $changes = [];
                $fields = [
                    'username', 'email', 'first_name', 'last_name', 'phone', 
                    'department', 'position', 'role'
                ];
                        
                foreach ($fields as $field) {
                    if (isset($_POST[$field]) && $_POST[$field] !== ($oldData[$field] ?? '')) {
                        $changes[] = "$field: {$oldData[$field]} → {$_POST[$field]}";
                    }
                }
                        
                if (!empty($changes)) {
                    $changeLog = implode(" | ", $changes);
                    $conn->query("
                        INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                        VALUES (
                            {$_SESSION['user_id']},
                            'update',
                            'user',
                            $userId,
                            '" . $conn->real_escape_string($changeLog) . "'
                        )
                    ");
                }
                        
                $conn->commit();
                $response = [
                    'success' => true,
                    'message' => 'User updated successfully',
                    'redirect' => "edit.php?id=$userId&success=User updated successfully"
                ];
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'redirect' => isset($userId) ? 
            "permissions.php?id=$userId&error=" . urlencode($e->getMessage()) : 
            "index.php?error=" . urlencode($e->getMessage())
    ];
}

// Return JSON response for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Redirect for form submissions
header("Location: " . $response['redirect']);
exit();
