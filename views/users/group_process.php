<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$response = ['success' => false, 'message' => 'Invalid action'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    switch ($action) {
        case 'create_group':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $permissions = $_POST['permissions'] ?? [];
            
            if (empty($name) || empty($description)) {
                throw new Exception('Name and description are required');
            }
            
            // Check if group name already exists
            $stmt = $conn->prepare("SELECT id FROM permission_groups WHERE name = ?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('A permission group with this name already exists');
            }
            
            $conn->begin_transaction();
            
            try {
                // Create the group
                $stmt = $conn->prepare("
                    INSERT INTO permission_groups (name, description, created_by) 
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("ssi", $name, $description, $_SESSION['user_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create permission group');
                }
                
                $groupId = $stmt->insert_id;
                
                // Add permissions to the group
                if (!empty($permissions)) {
                    $stmt = $conn->prepare("
                        INSERT INTO permission_group_items (group_id, permission_id)
                        SELECT ?, id FROM permissions WHERE name = ?
                    ");
                    
                    foreach ($permissions as $permission) {
                        $stmt->bind_param("is", $groupId, $permission);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to add permission: $permission");
                        }
                    }
                }
                
                // Log the action
                $conn->query("
                    INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                    VALUES (
                        {$_SESSION['user_id']},
                        'create',
                        'permission_group',
                        $groupId,
                        'Created permission group: $name with " . count($permissions) . " permissions'
                    )
                ");
                
                $conn->commit();
                $response = [
                    'success' => true,
                    'message' => 'Permission group created successfully',
                    'redirect' => "permission_groups.php?success=Permission group created successfully"
                ];
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        case 'edit_group':
            $groupId = (int)$_POST['group_id'];
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $permissions = $_POST['permissions'] ?? [];
            
            if (empty($name) || empty($description)) {
                throw new Exception('Name and description are required');
            }
            
            // Check if group exists
            $stmt = $conn->prepare("SELECT * FROM permission_groups WHERE id = ?");
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            $group = $stmt->get_result()->fetch_assoc();
            
            if (!$group) {
                throw new Exception('Permission group not found');
            }
            
            // Check if new name conflicts with existing groups
            if ($name !== $group['name']) {
                $stmt = $conn->prepare("SELECT id FROM permission_groups WHERE name = ? AND id != ?");
                $stmt->bind_param("si", $name, $groupId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('A permission group with this name already exists');
                }
            }
            
            $conn->begin_transaction();
            
            try {
                // Update group details
                $stmt = $conn->prepare("
                    UPDATE permission_groups 
                    SET name = ?, description = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("ssi", $name, $description, $groupId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update permission group');
                }
                
                // Get current permissions for comparison
                $stmt = $conn->prepare("
                    SELECT p.name 
                    FROM permissions p 
                    JOIN permission_group_items pgi ON p.id = pgi.permission_id 
                    WHERE pgi.group_id = ?
                ");
                $stmt->bind_param("i", $groupId);
                $stmt->execute();
                $result = $stmt->get_result();
                $oldPermissions = [];
                while ($row = $result->fetch_assoc()) {
                    $oldPermissions[] = $row['name'];
                }
                
                // Remove existing permissions
                $stmt = $conn->prepare("DELETE FROM permission_group_items WHERE group_id = ?");
                $stmt->bind_param("i", $groupId);
                $stmt->execute();
                
                // Add new permissions
                if (!empty($permissions)) {
                    $stmt = $conn->prepare("
                        INSERT INTO permission_group_items (group_id, permission_id)
                        SELECT ?, id FROM permissions WHERE name = ?
                    ");
                    
                    foreach ($permissions as $permission) {
                        $stmt->bind_param("is", $groupId, $permission);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to add permission: $permission");
                        }
                    }
                }
                
                // Log the changes
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
                
                $notes = "Updated permission group: $name";
                if (!empty($changeNotes)) {
                    $notes .= " | " . implode(" | ", $changeNotes);
                }
                
                $conn->query("
                    INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                    VALUES (
                        {$_SESSION['user_id']},
                        'update',
                        'permission_group',
                        $groupId,
                        '" . $conn->real_escape_string($notes) . "'
                    )
                ");
                
                $conn->commit();
                $response = [
                    'success' => true,
                    'message' => 'Permission group updated successfully',
                    'redirect' => "permission_groups.php?success=Permission group updated successfully"
                ];
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        case 'delete_group':
            $groupId = (int)$_POST['group_id'];
            
            // Check if group exists
            $stmt = $conn->prepare("SELECT * FROM permission_groups WHERE id = ?");
            $stmt->bind_param("i", $groupId);
            $stmt->execute();
            $group = $stmt->get_result()->fetch_assoc();
            
            if (!$group) {
                throw new Exception('Permission group not found');
            }
            
            $conn->begin_transaction();
            
            try {
                // Delete group items first
                $stmt = $conn->prepare("DELETE FROM permission_group_items WHERE group_id = ?");
                $stmt->bind_param("i", $groupId);
                $stmt->execute();
                
                // Delete the group
                $stmt = $conn->prepare("DELETE FROM permission_groups WHERE id = ?");
                $stmt->bind_param("i", $groupId);
                $stmt->execute();
                
                // Log the deletion
                $conn->query("
                    INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                    VALUES (
                        {$_SESSION['user_id']},
                        'delete',
                        'permission_group',
                        $groupId,
                        'Deleted permission group: {$group['name']}'
                    )
                ");
                
                $conn->commit();
                $response = [
                    'success' => true,
                    'message' => 'Permission group deleted successfully',
                    'redirect' => "permission_groups.php?success=Permission group deleted successfully"
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
        'redirect' => "permission_groups.php?error=" . urlencode($e->getMessage())
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
