<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/QuoteController.php';

$auth = new AuthController($conn);
$quoteController = new QuoteController($conn, $auth);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $quoteId = $_POST['quote_id'] ?? 0;
    
    switch ($action) {
        case 'approve':
            if (!$auth->hasPermission('approve_quotes')) {
                $response = ['success' => false, 'message' => 'Permission denied'];
                break;
            }
            $result = $quoteController->updateQuote($quoteId, 'approved');
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Quote approved successfully'];
            } else {
                $response = ['success' => false, 'message' => $result['error']];
            }
            break;
            
        case 'reject':
            if (!$auth->hasPermission('approve_quotes')) {
                $response = ['success' => false, 'message' => 'Permission denied'];
                break;
            }
            $result = $quoteController->updateQuote($quoteId, 'rejected');
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Quote rejected successfully'];
            } else {
                $response = ['success' => false, 'message' => $result['error']];
            }
            break;
            
        case 'delete':
            if (!$auth->hasPermission('delete_quotes')) {
                $response = ['success' => false, 'message' => 'Permission denied'];
                break;
            }
            $result = $quoteController->deleteQuote($quoteId);
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Quote deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => $result['error']];
            }
            break;
            
        case 'convert':
            if (!$auth->hasPermission('convert_quote_to_po')) {
                $response = ['success' => false, 'message' => 'Permission denied'];
                break;
            }
            $result = $quoteController->convertToPO($quoteId);
            if ($result['success']) {
                $response = ['success' => true, 'message' => 'Quote converted to PO successfully', 'po_number' => $result['po_number']];
            } else {
                $response = ['success' => false, 'message' => $result['error']];
            }
            break;
    }
}

// If it's an AJAX request, return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// If it's a regular form submission, redirect with message
$status = $response['success'] ? 'success' : 'error';
$message = urlencode($response['message']);
header("Location: index.php?status={$status}&message={$message}");
exit();
