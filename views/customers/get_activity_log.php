<?php
/**
 * Customer Activity Log Retrieval
 * 
 * Retrieves and formats customer activity logs including:
 * - Document uploads
 * - Status changes
 * - Information updates
 * - Bulk operations
 * 
 * @package    AvonSystem
 * @subpackage Views/Customers
 * @author     Cascade (AI Assistant) <cascade@codeium.com>
 * @version    1.0.0
 * @since      2025-02-18
 */

require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$auth = new AuthController($conn);

if (!$auth->isAuthenticated()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$customerId = $_GET['customer_id'] ?? null;

if (!$customerId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Customer ID is required']);
    exit();
}

try {
    $sql = "SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) as performed_by_name
            FROM customer_activity_logs l
            LEFT JOIN users u ON l.performed_by = u.id
            WHERE l.customer_id = ?
            ORDER BY l.performed_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'activity_type' => $row['activity_type'],
            'description' => $row['description'],
            'performed_by_name' => $row['performed_by_name'],
            'performed_at' => date('M j, Y g:i A', strtotime($row['performed_at']))
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($logs);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
