<?php
class AuthController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($username, $password) {
        try {
            // Log login attempt
            error_log("Login attempt for username: " . $username);
            
            $stmt = $this->conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ?");
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return ['error' => 'Database error'];
            }
            
            $stmt->bind_param("s", $username);
            if (!$stmt->execute()) {
                error_log("Execute failed: " . $stmt->error);
                return ['error' => 'Database error'];
            }
            
            $result = $stmt->get_result();
            error_log("Query result rows: " . $result->num_rows);
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                error_log("User found. Status: " . $user['status']);
                
                if ($user['status'] === 'rejected') {
                    return ['error' => 'Your account has been rejected'];
                }
                if ($user['status'] === 'inactive') {
                    return ['error' => 'Your account is inactive'];
                }
                if ($user['status'] === 'pending' && $user['role'] !== 'admin') {
                    return ['error' => 'Your account is pending approval'];
                }
                
                error_log("Verifying password for user: " . $user['username']);
                if (password_verify($password, $user['password'])) {
                    error_log("Password verified successfully");
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Load user permissions
                    $this->loadUserPermissions($user['id']);
                    
                    return ['success' => true];
                } else {
                    error_log("Password verification failed");
                }
            }
            
            return ['error' => 'Invalid username or password'];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['error' => 'An error occurred during login'];
        }
    }

    private function loadUserPermissions($userId) {
        $sql = "SELECT p.name FROM permissions p 
                JOIN user_permissions up ON p.id = up.permission_id 
                WHERE up.user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $_SESSION['permissions'] = [];
        while ($row = $result->fetch_assoc()) {
            $_SESSION['permissions'][] = $row['name'];
        }
    }

    public function logout() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-42000, '/');
        }
        session_destroy();
        header("Location: /avon-sys/views/auth/login.php");
        exit();
    }

    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    public function hasPermission($permission) {
        if ($this->hasRole('admin')) return true;
        return isset($_SESSION['permissions']) && in_array($permission, $_SESSION['permissions']);
    }

    public function createUser($username, $password, $email, $role) {
        if (!$this->hasRole('admin')) {
            return ['error' => 'Unauthorized'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $status = $role === 'admin' ? 'approved' : 'pending';
        
        $stmt = $this->conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $hashedPassword, $email, $role, $status);
        
        if ($stmt->execute()) {
            return ['success' => true, 'user_id' => $stmt->insert_id];
        }
        
        return ['error' => $stmt->error];
    }

    public function updateUserStatus($userId, $status) {
        if (!$this->hasRole('admin')) {
            return ['error' => 'Unauthorized'];
        }

        $stmt = $this->conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $userId);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['error' => $stmt->error];
    }

    public function grantPermission($userId, $permissionName) {
        if (!$this->hasRole('admin')) {
            return ['error' => 'Unauthorized'];
        }

        // Get permission ID
        $stmt = $this->conn->prepare("SELECT id FROM permissions WHERE name = ?");
        $stmt->bind_param("s", $permissionName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['error' => 'Permission not found'];
        }
        
        $permissionId = $result->fetch_assoc()['id'];

        // Grant permission
        $stmt = $this->conn->prepare("INSERT IGNORE INTO user_permissions (user_id, permission_id, granted_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $userId, $permissionId, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['error' => $stmt->error];
    }

    public function revokePermission($userId, $permissionName) {
        if (!$this->hasRole('admin')) {
            return ['error' => 'Unauthorized'];
        }

        $stmt = $this->conn->prepare("DELETE up FROM user_permissions up 
                                    JOIN permissions p ON up.permission_id = p.id 
                                    WHERE up.user_id = ? AND p.name = ?");
        $stmt->bind_param("is", $userId, $permissionName);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['error' => $stmt->error];
    }

    public function getUserPermissions($userId) {
        $sql = "SELECT p.name, p.description, up.created_at, u.username as granted_by
                FROM permissions p
                LEFT JOIN user_permissions up ON p.id = up.permission_id AND up.user_id = ?
                LEFT JOIN users u ON up.granted_by = u.id
                ORDER BY p.name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
