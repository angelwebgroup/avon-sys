<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

if ($userId && in_array($status, ['approved', 'rejected', 'inactive'])) {
    $result = $auth->updateUserStatus($userId, $status);
    
    if ($result['success']) {
        header("Location: index.php?success=Status updated successfully");
    } else {
        header("Location: index.php?error=" . urlencode($result['error']));
    }
} else {
    header("Location: index.php?error=Invalid parameters");
}
exit();
