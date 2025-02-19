<?php
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/PurchaseOrderController.php';

$auth = new AuthController($conn);
$poController = new PurchaseOrderController($conn, $auth);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

$response = ['success' => false, 'message' => 'Invalid action'];
$poId = isset($_POST['po_id']) ? (int)$_POST['po_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    switch ($action) {
        case 'approve':
            if (!$auth->hasPermission('approve_purchase_orders')) {
                throw new Exception('Permission denied');
            }
            
            $data = ['status' => 'approved'];
            if (isset($_POST['delivery_date']) && !empty($_POST['delivery_date'])) {
                $data['delivery_date'] = $_POST['delivery_date'];
            }
            
            $result = $poController->updatePO($poId, $data);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Log the approval
            $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id) 
                         VALUES ({$_SESSION['user_id']}, 'approve', 'purchase_order', $poId)");
            
            $response = [
                'success' => true,
                'message' => 'Purchase Order approved successfully',
                'redirect' => "view.php?id=$poId&success=Purchase Order approved successfully"
            ];
            break;

        case 'deliver':
            if (!$auth->hasPermission('edit_purchase_orders')) {
                throw new Exception('Permission denied');
            }
            
            $data = [
                'status' => 'delivered',
                'delivery_date' => date('Y-m-d')
            ];
            
            $result = $poController->updatePO($poId, $data);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Log the delivery
            $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id) 
                         VALUES ({$_SESSION['user_id']}, 'deliver', 'purchase_order', $poId)");
            
            $response = [
                'success' => true,
                'message' => 'Purchase Order marked as delivered',
                'redirect' => "view.php?id=$poId&success=Purchase Order marked as delivered"
            ];
            break;

        case 'cancel':
            if (!$auth->hasPermission('edit_purchase_orders')) {
                throw new Exception('Permission denied');
            }
            
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            if (empty($reason)) {
                throw new Exception('Cancellation reason is required');
            }
            
            $data = ['status' => 'canceled'];
            $result = $poController->updatePO($poId, $data);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Log the cancellation with reason
            $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                         VALUES ({$_SESSION['user_id']}, 'cancel', 'purchase_order', $poId, '" . 
                         $conn->real_escape_string($reason) . "')");
            
            $response = [
                'success' => true,
                'message' => 'Purchase Order cancelled successfully',
                'redirect' => "view.php?id=$poId&success=Purchase Order cancelled successfully"
            ];
            break;

        case 'update_delivery_date':
            if (!$auth->hasPermission('edit_purchase_orders')) {
                throw new Exception('Permission denied');
            }
            
            $deliveryDate = isset($_POST['delivery_date']) ? trim($_POST['delivery_date']) : '';
            if (empty($deliveryDate)) {
                throw new Exception('Delivery date is required');
            }
            
            $data = ['delivery_date' => $deliveryDate];
            $result = $poController->updatePO($poId, $data);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Log the delivery date update
            $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                         VALUES ({$_SESSION['user_id']}, 'update', 'purchase_order', $poId, 
                         'Updated delivery date to: $deliveryDate')");
            
            $response = [
                'success' => true,
                'message' => 'Delivery date updated successfully',
                'redirect' => "view.php?id=$poId&success=Delivery date updated successfully"
            ];
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'redirect' => isset($poId) ? "view.php?id=$poId&error=" . urlencode($e->getMessage()) : "index.php"
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
