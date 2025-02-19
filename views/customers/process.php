<?php
/**
 * Customer Processing Handler
 * 
 * Handles various customer operations including:
 * - Customer creation
 * - Customer updates
 * - Status changes
 * 
 * @package    AvonSystem
 * @subpackage Views/Customers
 * @author     Cascade (AI Assistant) <cascade@codeium.com>
 * @version    1.0.0
 * @since      2025-02-18
 */

require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/CustomerController.php';

$auth = new AuthController($conn);
$customerController = new CustomerController($conn, $auth);

if (!$auth->isAuthenticated()) {
    header("Location: /avon-sys/views/auth/login.php");
    exit();
}

// Debug log
error_log("POST Data in process.php: " . print_r($_POST, true));

// Handle JSON requests
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === "application/json") {
    $content = trim(file_get_contents("php://input"));
    $decoded = json_decode($content, true);
    
    if (!is_array($decoded)) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }
    
    $action = $decoded['action'] ?? '';
    
    try {
        switch ($action) {
            case 'delete':
                if (!isset($decoded['id'])) {
                    throw new Exception('Customer ID is required');
                }
                $result = $customerController->deleteCustomer($decoded['id']);
                echo json_encode($result);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle form submissions
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'create':
            // Validate and create customer using controller
            $data = $_POST;
            $result = $customerController->createCustomer($data);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Customer registered successfully';
                header("Location: index.php");
                exit();
            } else {
                throw new Exception($result['message']);
            }
            break;

        case 'update':
            if (!isset($_POST['id'])) {
                throw new Exception('Customer ID is required');
            }
            
            // Debug log
            error_log("Updating customer ID: " . $_POST['id']);
            
            // Validate and update customer using controller
            $customerId = (int)$_POST['id'];
            unset($_POST['id']); // Remove id from data array
            unset($_POST['action']); // Remove action from data array
            
            // Debug log before update
            error_log("Data before update: " . print_r($_POST, true));
            
            $result = $customerController->updateCustomer($customerId, $_POST);
            
            // Debug log after update
            error_log("Update result: " . print_r($result, true));
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Customer updated successfully';
                header("Location: index.php");
                exit();
            } else {
                throw new Exception($result['message'] ?? 'Failed to update customer');
            }
            break;

        case 'delete':
            if (!isset($_POST['id'])) {
                throw new Exception('Customer ID is required');
            }
            
            $customerId = (int)$_POST['id'];
            $result = $customerController->deleteCustomer($customerId);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Customer deleted successfully';
                header("Location: index.php");
                exit();
            } else {
                throw new Exception($result['message'] ?? 'Failed to delete customer');
            }
            break;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    if (isset($_POST['id'])) {
        header("Location: edit.php?id=" . $_POST['id']);
    } else {
        header("Location: index.php");
    }
    exit();
}

header("Location: index.php");
exit();
