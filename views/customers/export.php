<?php
/**
 * Customer Data Export Handler
 * 
 * Handles the export of customer data in various formats:
 * - CSV export with all customer fields
 * - Excel export with formatted data
 * - Filtered exports based on search criteria
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
    die('Authentication required');
}

$format = $_POST['format'] ?? 'csv';
$filters = [
    'search' => $_POST['search'] ?? '',
    'state' => $_POST['state'] ?? '',
    'approval_status' => $_POST['approval_status'] ?? ''
];

try {
    $data = $customerController->exportCustomers($format, $filters);
    
    // Set headers for file download
    $filename = 'customers_export_' . date('Y-m-d') . '.' . $format;
    header('Content-Type: text/' . $format);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $data;
} catch (Exception $e) {
    die('Export failed: ' . $e->getMessage());
}
