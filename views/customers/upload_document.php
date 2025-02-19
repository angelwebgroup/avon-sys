<?php
/**
 * Customer Document Upload Handler
 * 
 * Handles the upload of customer-related documents such as:
 * - GST Certificates
 * - PAN Cards
 * - Company Registration Documents
 * - Other business documents
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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$customerId = $_POST['customer_id'] ?? null;
$documentType = $_POST['document_type'] ?? null;
$file = $_FILES['document'] ?? null;

if (!$customerId || !$documentType || !$file) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$result = $customerController->uploadDocument($customerId, $file, $documentType);

header('Content-Type: application/json');
echo json_encode($result);
