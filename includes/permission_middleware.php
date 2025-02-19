<?php
function checkPermission($permission) {
    global $auth;
    
    if (!isset($auth)) {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../controllers/AuthController.php';
        $auth = new AuthController($conn);
    }
    
    if (!$auth->isAuthenticated()) {
        header("Location: /avon-sys/views/auth/login.php");
        exit();
    }
    
    if (!$auth->hasPermission($permission)) {
        // Log access attempt
        $logger = new AuditLogger($conn, $_SESSION['user_id'] ?? null);
        $logger->log('access_denied', 'permission', 0, null, [
            'required_permission' => $permission,
            'url' => $_SERVER['REQUEST_URI']
        ]);
        
        // Redirect to error page
        header("Location: /avon-sys/views/error.php?code=403&message=" . urlencode('Permission denied: ' . $permission));
        exit();
    }
    
    return true;
}

function can($permission) {
    global $auth;
    
    if (!isset($auth)) {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../controllers/AuthController.php';
        $auth = new AuthController($conn);
    }
    
    return $auth->hasPermission($permission);
}

function cannot($permission) {
    return !can($permission);
}

function authorize($permission, $callback) {
    if (can($permission)) {
        return $callback();
    }
    return '';
}
