<?php
/**
 * Customer Controller
 * 
 * Handles all customer-related operations including:
 * - Customer listing with pagination and filtering
 * - Bulk operations (approve, delete, update)
 * - Document management
 * - Activity logging
 * - Data export
 * 
 * @package    AvonSystem
 * @subpackage Controllers
 * @author     Cascade (AI Assistant) <cascade@codeium.com>
 * @version    2.0.0
 * @since      2025-02-18
 */

require_once __DIR__ . '/BaseController.php';

class CustomerController extends BaseController {
    private $validationRules = [
        'company_name' => 'required|max:100',
        'contact_person' => 'required|max:100',
        'email' => 'required|email|max:100',
        'phone' => 'max:20',
        'address' => 'max:500'
    ];

    public function __construct($db, $auth) {
        parent::__construct($db, $auth);
    }

    // Pagination and Search
    public function getCustomers($page = 1, $perPage = 10, $filters = []) {
        $this->requirePermission('view_customers');
        
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];
        $types = "";
        
        // Build search conditions
        if (!empty($filters['search'])) {
            $whereConditions[] = "(c.company_name LIKE ? OR c.contact_person LIKE ? OR c.email LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            $types .= "sss";
        }
        
        if (!empty($filters['state'])) {
            $whereConditions[] = "c.state_code = ?";
            $params[] = $filters['state'];
            $types .= "s";
        }
        
        if (!empty($filters['approval_status'])) {
            $whereConditions[] = "c.customer_approved = ?";
            $params[] = $filters['approval_status'];
            $types .= "s";
        }
        
        // Build query
        $query = "SELECT c.*, u1.username as created_by_name, u2.username as approved_by_name
                 FROM customers c
                 LEFT JOIN users u1 ON c.registered_by = u1.id
                 LEFT JOIN users u2 ON c.approved_by = u2.id";
        
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        // Get total count for pagination
        $countQuery = str_replace("c.*, u1.username as created_by_name, u2.username as approved_by_name", "COUNT(*) as total", $query);
        $stmt = $this->conn->prepare($countQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalRecords = $stmt->get_result()->fetch_assoc()['total'];
        
        // Add pagination
        $query .= " ORDER BY c.created_at DESC LIMIT ?, ?";
        $params = array_merge($params, [$offset, $perPage]);
        $types .= "ii";
        
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        
        return [
            'data' => $customers,
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalRecords / $perPage)
        ];
    }

    // Bulk Operations
    public function bulkOperation($operation, $customerIds, $data = []) {
        $this->requirePermission('manage_customers');
        
        try {
            $this->conn->begin_transaction();
            
            $placeholders = str_repeat('?,', count($customerIds) - 1) . '?';
            $types = str_repeat('i', count($customerIds));
            
            switch ($operation) {
                case 'approve':
                    $stmt = $this->conn->prepare("
                        UPDATE customers 
                        SET customer_approved = 'Y',
                            approved_by = ?,
                            approved_date = CURRENT_TIMESTAMP,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id IN ({$placeholders})
                    ");
                    
                    $params = array_merge([$_SESSION['user_id']], $customerIds);
                    $stmt->bind_param('i' . $types, ...$params);
                    $stmt->execute();
                    break;
                    
                case 'update_credit_limit':
                    if (!isset($data['credit_limit'])) {
                        throw new Exception('Credit limit is required');
                    }
                    
                    $stmt = $this->conn->prepare("
                        UPDATE customers 
                        SET credit_limit = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id IN ({$placeholders})
                    ");
                    
                    $params = array_merge([$data['credit_limit']], $customerIds);
                    $stmt->bind_param('d' . $types, ...$params);
                    $stmt->execute();
                    break;
                    
                case 'delete':
                    // Check for related records
                    $relatedTables = [
                        'purchase_orders' => 'purchase orders',
                        'quotes' => 'quotes'
                    ];
                    
                    foreach ($customerIds as $customerId) {
                        foreach ($relatedTables as $table => $label) {
                            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM {$table} WHERE customer_id = ?");
                            $stmt->bind_param('i', $customerId);
                            $stmt->execute();
                            $result = $stmt->get_result()->fetch_assoc();
                            
                            if ($result['count'] > 0) {
                                throw new Exception("Cannot delete customers: One or more customers have related {$label}. Please delete those records first.");
                            }
                        }
                    }
                    
                    // Delete activity logs first
                    $stmt = $this->conn->prepare("DELETE FROM customer_activity_logs WHERE customer_id IN ({$placeholders})");
                    $stmt->bind_param($types, ...$customerIds);
                    $stmt->execute();
                    
                    // Delete documents
                    $stmt = $this->conn->prepare("DELETE FROM customer_documents WHERE customer_id IN ({$placeholders})");
                    $stmt->bind_param($types, ...$customerIds);
                    $stmt->execute();
                    
                    // Finally delete customers
                    $stmt = $this->conn->prepare("DELETE FROM customers WHERE id IN ({$placeholders})");
                    $stmt->bind_param($types, ...$customerIds);
                    $stmt->execute();
                    break;
                    
                default:
                    throw new Exception('Invalid operation');
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => ucfirst($operation) . ' operation completed successfully'
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Document Management
    public function uploadDocument($customerId, $file, $documentType) {
        $this->requirePermission('manage_customers');
        
        try {
            $uploadDir = __DIR__ . '/../uploads/customer_documents/' . $customerId . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = time() . '_' . basename($file['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $this->conn->prepare("
                    INSERT INTO customer_documents (customer_id, document_type, file_name, file_path, uploaded_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $relativePath = 'uploads/customer_documents/' . $customerId . '/' . $fileName;
                $stmt->bind_param("isssi", $customerId, $documentType, $fileName, $relativePath, $_SESSION['user_id']);
                $stmt->execute();
                
                $this->logActivity($customerId, 'document_upload', "Uploaded document: $fileName");
                return ['success' => true, 'message' => 'Document uploaded successfully'];
            }
            
            throw new Exception('Failed to upload document');
        } catch (Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    // Activity Logging
    private function logActivity($customerId, $action, $description) {
        try {
            // Check if the table exists
            $tableExists = $this->conn->query("SHOW TABLES LIKE 'customer_activity_logs'")->num_rows > 0;
            
            if (!$tableExists) {
                // Create the table if it doesn't exist
                $sql = "CREATE TABLE IF NOT EXISTS customer_activity_logs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    customer_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    description TEXT,
                    user_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (customer_id) REFERENCES customers(id),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )";
                
                if (!$this->conn->query($sql)) {
                    error_log("Failed to create customer_activity_logs table: " . $this->conn->error);
                    return;
                }
            }
            
            $stmt = $this->conn->prepare("INSERT INTO customer_activity_logs (customer_id, action, description, user_id) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                $stmt->bind_param("issi", $customerId, $action, $description, $userId);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            // Don't throw the exception as logging failure shouldn't affect the main operation
        }
    }

    private function logBulkActivity($operation, $customerIds) {
        try {
            $description = sprintf("Bulk operation '%s' performed on customer IDs: %s", 
                                 $operation, 
                                 implode(', ', $customerIds));
            
            foreach ($customerIds as $customerId) {
                $this->logActivity($customerId, 'bulk_' . $operation, $description);
            }
        } catch (Exception $e) {
            error_log("Error logging bulk activity: " . $e->getMessage());
            // Don't throw the exception as logging failure shouldn't affect the main operation
        }
    }

    // Get Single Customer
    public function getCustomerById($id) {
        $this->requirePermission('view_customers');
        
        $query = "SELECT c.*, u1.username as created_by_name, u2.username as approved_by_name
                 FROM customers c
                 LEFT JOIN users u1 ON c.registered_by = u1.id
                 LEFT JOIN users u2 ON c.approved_by = u2.id
                 WHERE c.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Update Customer
    public function updateCustomer($id, $data) {
        if (!$this->auth->isAuthenticated()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            // Validate required fields
            $requiredFields = [
                'company_name' => 'Company Name',
                'contact_person' => 'Contact Person',
                'email' => 'Email',
                'mobile_no' => 'Mobile Number',
                'address' => 'Address',
                'city' => 'City',
                'state_code' => 'State',
                'pin_code' => 'PIN Code'
            ];

            foreach ($requiredFields as $field => $label) {
                if (empty($data[$field])) {
                    throw new Exception("$label is required");
                }
            }

            // Start transaction
            $this->conn->begin_transaction();

            // Update customer
            $sql = "UPDATE customers SET 
                    company_name = ?,
                    contact_person = ?,
                    email = ?,
                    mobile_no = ?,
                    telephone_no = ?,
                    address = ?,
                    city = ?,
                    state_code = ?,
                    pin_code = ?,
                    gst_registration_no = ?,
                    pan_no = ?,
                    freight_terms = ?,
                    hsn_sac_code = ?,
                    establishment_year = ?,
                    turnover_last_year = ?,
                    payment_terms = ?,
                    security_cheque = ?,
                    credit_limit = ?,
                    enterprise_type = ?,
                    equity_type = ?,
                    product_segment = ?,
                    coordinator_name = ?,
                    remarks = ?,
                    customer_approved = ?
                    WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            
            // Convert empty strings to null for optional fields
            $telephoneNo = empty($data['telephone_no']) ? null : $data['telephone_no'];
            $gstNo = empty($data['gst_registration_no']) ? null : $data['gst_registration_no'];
            $panNo = empty($data['pan_no']) ? null : $data['pan_no'];
            $freightTerms = empty($data['freight_terms']) ? null : $data['freight_terms'];
            $hsnCode = empty($data['hsn_sac_code']) ? null : $data['hsn_sac_code'];
            $estYear = empty($data['establishment_year']) ? null : (int)$data['establishment_year'];
            $turnover = empty($data['turnover_last_year']) ? null : (float)$data['turnover_last_year'];
            $paymentTerms = empty($data['payment_terms']) ? null : $data['payment_terms'];
            $securityCheque = empty($data['security_cheque']) ? null : (float)$data['security_cheque'];
            $creditLimit = empty($data['credit_limit']) ? null : (float)$data['credit_limit'];
            $enterpriseType = empty($data['enterprise_type']) ? null : $data['enterprise_type'];
            $equityType = empty($data['equity_type']) ? null : $data['equity_type'];
            $productSegment = empty($data['product_segment']) ? null : $data['product_segment'];
            $coordinatorName = empty($data['coordinator_name']) ? null : $data['coordinator_name'];
            $remarks = empty($data['remarks']) ? null : $data['remarks'];
            $customerApproved = isset($data['customer_approved']) ? (bool)$data['customer_approved'] : false;

            $stmt->bind_param(
                "ssssssssssssisssddsssssis",
                $data['company_name'],
                $data['contact_person'],
                $data['email'],
                $data['mobile_no'],
                $telephoneNo,
                $data['address'],
                $data['city'],
                $data['state_code'],
                $data['pin_code'],
                $gstNo,
                $panNo,
                $freightTerms,
                $hsnCode,
                $estYear,
                $turnover,
                $paymentTerms,
                $securityCheque,
                $creditLimit,
                $enterpriseType,
                $equityType,
                $productSegment,
                $coordinatorName,
                $remarks,
                $customerApproved,
                $id
            );

            if (!$stmt->execute()) {
                throw new Exception("Error updating customer: " . $stmt->error);
            }

            // Log the update
            $this->logActivity($id, 'update', 'Customer details updated');

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Data Export
    public function exportCustomers($format = 'csv', $filters = []) {
        $this->requirePermission('view_customers');
        
        $customers = $this->getCustomers(1, PHP_INT_MAX, $filters)['data'];
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($customers);
            case 'excel':
                return $this->exportToExcel($customers);
            default:
                throw new Exception('Unsupported export format');
        }
    }

    private function exportToCsv($customers) {
        $output = fopen('php://temp', 'w');
        
        // Write headers
        $headers = ['Company Name', 'Contact Person', 'Email', 'Phone', 'Address', 'City', 'State', 'PIN Code', 'GST No', 'PAN No'];
        fputcsv($output, $headers);
        
        // Write data
        foreach ($customers as $customer) {
            $row = [
                $customer['company_name'],
                $customer['contact_person'],
                $customer['email'],
                $customer['mobile_no'],
                $customer['address'],
                $customer['city'],
                $customer['state_code'],
                $customer['pin_code'],
                $customer['gst_registration_no'],
                $customer['pan_no']
            ];
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    public function getCustomer($id) {
        $this->requirePermission('view_customers');

        $sql = "SELECT c.*, u1.username as created_by_name, u2.username as approved_by_name
                FROM customers c
                LEFT JOIN users u1 ON c.registered_by = u1.id
                LEFT JOIN users u2 ON c.approved_by = u2.id
                WHERE c.id = ?";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['error' => 'Customer not found'];
            }
            
            return ['success' => true, 'data' => $result->fetch_assoc()];
        } catch (Exception $e) {
            return $this->handleError($e);
        }
    }

    public function createCustomer($data) {
        $this->requirePermission('create_customers');

        try {
            $this->conn->begin_transaction();
            
            // Check if email already exists
            $stmt = $this->conn->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->bind_param('s', $data['email']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('A customer with this email already exists');
            }
            
            // Prepare fields and values for insertion
            $fields = [];
            $values = [];
            $types = '';
            $params = [];
            
            $allowedFields = [
                'company_name' => 's',
                'contact_person' => 's',
                'email' => 's',
                'mobile_no' => 's',
                'telephone_no' => 's',
                'address' => 's',
                'city' => 's',
                'state_code' => 's',
                'pin_code' => 's',
                'gst_registration_no' => 's',
                'pan_no' => 's',
                'freight_terms' => 's',
                'hsn_sac_code' => 's',
                'establishment_year' => 'i',
                'turnover_last_year' => 'd',
                'payment_terms' => 's',
                'security_cheque' => 'i',
                'credit_limit' => 'd',
                'equity_type' => 's',
                'longitude' => 'd',
                'latitude' => 'd'
            ];
            
            foreach ($allowedFields as $field => $type) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    $fields[] = $field;
                    $values[] = '?';
                    $types .= $type;
                    $params[] = $data[$field];
                }
            }
            
            // Add created_by and created_at
            $fields[] = 'created_by';
            $values[] = '?';
            $types .= 'i';
            $params[] = $_SESSION['user_id'];
            
            $fields[] = 'created_at';
            $values[] = 'NOW()';
            
            // Build and execute query
            $sql = "INSERT INTO customers (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            $stmt = $this->conn->prepare($sql);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $customerId = $stmt->insert_id;
            
            // Log activity
            $stmt = $this->conn->prepare("
                INSERT INTO customer_activity_logs (customer_id, action, description, user_id)
                VALUES (?, 'create', 'Customer created', ?)
            ");
            $stmt->bind_param('ii', $customerId, $_SESSION['user_id']);
            $stmt->execute();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Customer created successfully',
                'customer_id' => $customerId
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deleteCustomer($id) {
        $this->requirePermission('delete_customers');
        
        try {
            $this->conn->begin_transaction();
            
            // First, check if customer exists
            $stmt = $this->conn->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Customer not found');
            }
            
            // Get all quotes for this customer
            $stmt = $this->conn->prepare("SELECT id FROM quotes WHERE customer_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $quotesResult = $stmt->get_result();
            
            // Delete purchase orders related to quotes
            while ($quote = $quotesResult->fetch_assoc()) {
                $stmt = $this->conn->prepare("DELETE FROM purchase_orders WHERE quote_id = ?");
                $stmt->bind_param('i', $quote['id']);
                $stmt->execute();
            }
            
            // Now delete quotes
            $stmt = $this->conn->prepare("DELETE FROM quotes WHERE customer_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            // Delete customer activity logs
            $stmt = $this->conn->prepare("DELETE FROM customer_activity_logs WHERE customer_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            // Delete customer documents
            $stmt = $this->conn->prepare("DELETE FROM customer_documents WHERE customer_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            // Finally, delete the customer
            $stmt = $this->conn->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Customer deleted successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error deleting customer: ' . $e->getMessage()];
        }
    }

    public function approveCustomer($id) {
        $this->requirePermission('approve_customers');
        
        try {
            // Get current customer data for audit log
            $oldData = $this->getCustomer($id);
            if (isset($oldData['error'])) {
                throw new Exception($oldData['error']);
            }
            
            $stmt = $this->conn->prepare("
                UPDATE customers 
                SET status = 'approved', approved_by = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->bind_param('ii', $_SESSION['user_id'], $id);
            $stmt->execute();
            
            $this->logAction('approve', 'customer', $id, $oldData['data'], ['status' => 'approved']);
            
            // Notify the user who created the customer
            $this->sendNotification(
                $oldData['data']['created_by'],
                'Customer Approved',
                "Customer {$oldData['data']['company_name']} has been approved",
                'success'
            );
            
            return [
                'success' => true,
                'message' => 'Customer approved successfully'
            ];
        } catch (Exception $e) {
            return $this->handleError($e);
        }
    }

    public function getCustomerStats() {
        $this->requirePermission('view_customers');
        
        try {
            $stats = [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'deactivated' => 0,
                'recent' => []
            ];
            
            // Get counts
            $result = $this->conn->query("
                SELECT status, COUNT(*) as count
                FROM customers
                GROUP BY status
            ");
            
            while ($row = $result->fetch_assoc()) {
                $stats[$row['status']] = $row['count'];
                $stats['total'] += $row['count'];
            }
            
            // Get recent customers
            $result = $this->conn->query("
                SELECT c.*, u.username as created_by_name
                FROM customers c
                LEFT JOIN users u ON c.registered_by = u.id
                ORDER BY c.created_at DESC
                LIMIT 5
            ");
            
            $stats['recent'] = $result->fetch_all(MYSQLI_ASSOC);
            
            return ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            return $this->handleError($e);
        }
    }
}
