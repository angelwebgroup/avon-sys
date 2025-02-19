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

$quoteId = isset($_POST['quote_id']) ? (int)$_POST['quote_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    switch ($action) {
        case 'create':
            if (!$auth->hasPermission('create_quotes')) {
                throw new Exception('Permission denied');
            }
            
            // Validate required fields
            if (empty($_POST['customer_id'])) {
                throw new Exception('Customer is required');
            }
            if (empty($_POST['items']) || !is_array($_POST['items'])) {
                throw new Exception('At least one item is required');
            }
            
            $customerId = (int)$_POST['customer_id'];
            $items = $_POST['items'];
            
            // Validate items
            foreach ($items as &$item) {
                if (empty($item['description'])) {
                    throw new Exception('Item description is required');
                }
                $item['quantity'] = floatval($item['quantity']);
                $item['unit_price'] = floatval($item['unit_price']);
                if ($item['quantity'] <= 0 || $item['unit_price'] <= 0) {
                    throw new Exception('Invalid quantity or unit price');
                }
            }
            
            $result = $quoteController->createQuote($customerId, $items);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            $_SESSION['success_message'] = 'Quote created successfully';
            header("Location: view.php?id={$result['quote_id']}");
            exit();
            break;

        case 'update':
            if (!$auth->hasPermission('edit_quotes')) {
                throw new Exception('Permission denied');
            }
            
            if (empty($_POST['quote_id'])) {
                throw new Exception('Quote ID is required');
            }
            if (empty($_POST['customer_id'])) {
                throw new Exception('Customer is required');
            }
            if (empty($_POST['items']) || !is_array($_POST['items'])) {
                throw new Exception('At least one item is required');
            }
            
            $quoteId = (int)$_POST['quote_id'];
            $customerId = (int)$_POST['customer_id'];
            $items = $_POST['items'];
            
            // Validate items
            foreach ($items as &$item) {
                if (empty($item['description'])) {
                    throw new Exception('Item description is required');
                }
                $item['quantity'] = floatval($item['quantity']);
                $item['unit_price'] = floatval($item['unit_price']);
                if ($item['quantity'] <= 0 || $item['unit_price'] <= 0) {
                    throw new Exception('Invalid quantity or unit price');
                }
            }
            
            $result = $quoteController->updateQuote($quoteId, $customerId, $items);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            $_SESSION['success_message'] = 'Quote updated successfully';
            header("Location: view.php?id=$quoteId");
            exit();
            break;

        case 'delete':
            if (!$auth->hasPermission('delete_quotes')) {
                throw new Exception('Permission denied');
            }
            
            if (empty($_POST['quote_id'])) {
                throw new Exception('Quote ID is required');
            }
            
            $quoteId = (int)$_POST['quote_id'];
            $result = $quoteController->deleteQuote($quoteId);
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            $_SESSION['success_message'] = 'Quote deleted successfully';
            header("Location: index.php");
            exit();
            break;

        case 'approve':
            if (!$auth->hasPermission('approve_quotes')) {
                throw new Exception('Permission denied');
            }
            
            $result = $quoteController->updateQuoteStatus($quoteId, 'approved');
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Log the approval
            $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id) 
                         VALUES ({$_SESSION['user_id']}, 'approve', 'quote', $quoteId)");
            
            $_SESSION['success_message'] = 'Quote approved successfully';
            header("Location: view.php?id=$quoteId");
            exit();
            break;

        case 'reject':
            if (!$auth->hasPermission('approve_quotes')) {
                throw new Exception('Permission denied');
            }
            
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            if (empty($reason)) {
                throw new Exception('Rejection reason is required');
            }
            
            $result = $quoteController->updateQuoteStatus($quoteId, 'rejected');
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Log the rejection with reason
            $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                         VALUES ({$_SESSION['user_id']}, 'reject', 'quote', $quoteId, '" . 
                         $conn->real_escape_string($reason) . "')");
            
            $_SESSION['success_message'] = 'Quote rejected successfully';
            header("Location: view.php?id=$quoteId");
            exit();
            break;

        case 'convert':
            if (!$auth->hasPermission('convert_quote_to_po')) {
                throw new Exception('Permission denied');
            }
            
            // Check if quote is approved
            $quote = $quoteController->getQuote($quoteId);
            if (!$quote || $quote['status'] !== 'approved') {
                throw new Exception('Only approved quotes can be converted to PO');
            }
            
            // Check if PO already exists
            $existingPO = $conn->query("SELECT id FROM purchase_orders WHERE quote_id = $quoteId")->fetch_assoc();
            if ($existingPO) {
                throw new Exception('Purchase Order already exists for this quote');
            }
            
            // Generate PO number
            $year = date('Y');
            $month = date('m');
            $result = $conn->query("SELECT MAX(CAST(SUBSTRING(po_number, 9) AS UNSIGNED)) as last_num 
                                  FROM purchase_orders 
                                  WHERE po_number LIKE 'PO$year$month%'");
            $row = $result->fetch_assoc();
            $nextNum = ($row['last_num'] ?? 0) + 1;
            $poNumber = "PO$year$month" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            
            // Create PO
            $stmt = $conn->prepare("
                INSERT INTO purchase_orders (
                    po_number, quote_id, status, created_by, created_at
                ) VALUES (?, ?, 'pending', ?, CURRENT_TIMESTAMP)
            ");
            
            $stmt->bind_param("sii", $poNumber, $quoteId, $_SESSION['user_id']);
            if (!$stmt->execute()) {
                throw new Exception('Failed to create Purchase Order');
            }
            
            $poId = $stmt->insert_id;
            
            // Log the conversion
            $conn->query("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes) 
                         VALUES ({$_SESSION['user_id']}, 'convert', 'quote', $quoteId, 
                         'Converted to PO: $poNumber')");
            
            $_SESSION['success_message'] = 'Quote converted to Purchase Order successfully';
            header("Location: ../purchase-orders/view.php?id=$poId");
            exit();
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    if (isset($quoteId)) {
        header("Location: view.php?id=$quoteId");
    } else {
        header("Location: index.php");
    }
    exit();
}
