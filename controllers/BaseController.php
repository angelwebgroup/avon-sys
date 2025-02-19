<?php
require_once __DIR__ . '/AuditLogger.php';

class BaseController {
    protected $conn;
    protected $auth;
    protected $logger;

    public function __construct($db, $auth) {
        $this->conn = $db;
        $this->auth = $auth;
        $this->logger = new AuditLogger($db, $_SESSION['user_id'] ?? null);
    }

    protected function requirePermission($permission) {
        if (!$this->auth->hasPermission($permission)) {
            $this->logger->log('access_denied', 'permission', 0, null, ['required_permission' => $permission]);
            throw new Exception('Permission denied: ' . $permission);
        }
    }

    protected function logAction($action, $entityType, $entityId, $oldValue = null, $newValue = null) {
        return $this->logger->log($action, $entityType, $entityId, $oldValue, $newValue);
    }

    protected function validateInput($data, $rules) {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field]) || empty($data[$field])) {
                if (strpos($rule, 'required') !== false) {
                    $errors[] = ucfirst($field) . ' is required';
                }
                continue;
            }

            if (strpos($rule, 'email') !== false) {
                if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format';
                }
            }

            if (strpos($rule, 'numeric') !== false) {
                if (!is_numeric($data[$field])) {
                    $errors[] = ucfirst($field) . ' must be a number';
                }
            }

            if (preg_match('/min:(\d+)/', $rule, $matches)) {
                if (strlen($data[$field]) < $matches[1]) {
                    $errors[] = ucfirst($field) . ' must be at least ' . $matches[1] . ' characters';
                }
            }

            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                if (strlen($data[$field]) > $matches[1]) {
                    $errors[] = ucfirst($field) . ' must not exceed ' . $matches[1] . ' characters';
                }
            }
        }
        return $errors;
    }

    protected function sanitizeInput($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    protected function paginateResults($query, $params = [], $page = 1, $perPage = 10) {
        // Get total count
        $countQuery = preg_replace('/SELECT.*?FROM/is', 'SELECT COUNT(*) as count FROM', $query);
        $countQuery = preg_replace('/ORDER BY.*$/is', '', $countQuery);
        
        $stmt = $this->conn->prepare($countQuery);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $totalCount = $stmt->get_result()->fetch_assoc()['count'];
        
        // Calculate pagination
        $totalPages = ceil($totalCount / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        
        // Get paginated results
        $query .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $perPage;
        
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return [
            'data' => $results,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ];
    }

    protected function generateNumber($prefix, $table, $column) {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT {$column} FROM {$table} WHERE {$column} LIKE ? ORDER BY id DESC LIMIT 1";
        $pattern = $prefix . $year . $month . '%';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $lastNumber = $result->fetch_assoc()[$column];
            $sequence = intval(substr($lastNumber, -4)) + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    protected function sendNotification($userId, $title, $message, $type = 'info') {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('isss', $userId, $title, $message, $type);
        return $stmt->execute();
    }

    protected function handleError($e, $customMessage = null) {
        $this->logger->log('error', 'system', 0, null, [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return [
            'error' => $customMessage ?? 'An error occurred. Please try again.',
            'details' => $e->getMessage()
        ];
    }
}
