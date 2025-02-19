<?php
class QuoteController {
    private $conn;
    private $auth;

    public function __construct($db, $auth) {
        $this->conn = $db;
        $this->auth = $auth;
    }

    public function generateQuoteNumber() {
        $year = date('Y');
        $month = date('m');
        
        // Get the last quote number for this month
        $sql = "SELECT quote_number FROM quotes WHERE quote_number LIKE 'Q{$year}{$month}%' ORDER BY id DESC LIMIT 1";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $lastQuote = $result->fetch_assoc();
            $lastNumber = intval(substr($lastQuote['quote_number'], -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return "Q{$year}{$month}" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function createQuote($customerId, $items) {
        if (!$this->auth->isAuthenticated()) {
            return ['error' => 'Unauthorized'];
        }

        $this->conn->begin_transaction();

        try {
            $quoteNumber = $this->generateQuoteNumber();
            $totalAmount = 0;
            $taxAmount = 0;

            // Calculate totals
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }
            $taxAmount = $totalAmount * 0.18; // 18% tax

            // Create quote
            $stmt = $this->conn->prepare("INSERT INTO quotes (quote_number, customer_id, total_amount, tax_amount, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siddi", $quoteNumber, $customerId, $totalAmount, $taxAmount, $_SESSION['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating quote: " . $stmt->error);
            }
            
            $quoteId = $stmt->insert_id;

            // Insert quote items
            $stmt = $this->conn->prepare("INSERT INTO quote_items (quote_id, description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($items as $item) {
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $stmt->bind_param("isids", $quoteId, $item['description'], $item['quantity'], $item['unit_price'], $totalPrice);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error creating quote item: " . $stmt->error);
                }
            }

            $this->conn->commit();
            return ['success' => true, 'quote_id' => $quoteId, 'quote_number' => $quoteNumber];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['error' => $e->getMessage()];
        }
    }

    public function getQuote($id) {
        if (!$this->auth->isAuthenticated()) {
            return ['error' => 'Unauthorized'];
        }

        $sql = "SELECT q.*, 
                       c.company_name, 
                       c.contact_person, 
                       c.email, 
                       c.mobile_no,
                       c.telephone_no,
                       c.address,
                       c.city,
                       c.state_code,
                       c.pin_code,
                       u1.username as created_by_name, 
                       u2.username as approved_by_name
                FROM quotes q
                LEFT JOIN customers c ON q.customer_id = c.id
                LEFT JOIN users u1 ON q.created_by = u1.id
                LEFT JOIN users u2 ON q.approved_by = u2.id
                WHERE q.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $quote = $stmt->get_result()->fetch_assoc();

        if ($quote) {
            // Get quote items
            $sql = "SELECT * FROM quote_items WHERE quote_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $quote['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $quote;
    }

    public function updateQuoteStatus($id, $status) {
        if (!$this->auth->hasPermission('approve_quotes')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }

        try {
            $this->conn->begin_transaction();

            // Get current quote
            $stmt = $this->conn->prepare("SELECT status FROM quotes WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $quote = $stmt->get_result()->fetch_assoc();

            if (!$quote) {
                throw new Exception('Quote not found');
            }

            // Validate status transition
            $validStatuses = ['draft', 'pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status');
            }

            // Update quote status
            $sql = "UPDATE quotes SET status = ?";
            if ($status === 'approved') {
                $sql .= ", approved_by = ?, approved_at = CURRENT_TIMESTAMP";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $status, $_SESSION['user_id']);
            } else {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("s", $status);
            }

            if (!$stmt->execute()) {
                throw new Exception('Error updating quote status');
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateQuote($id, $data) {
        if (!$this->auth->hasPermission('edit_quotes')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }

        try {
            $this->conn->begin_transaction();

            // Get current quote
            $currentQuote = $this->getQuote($id);
            if (!$currentQuote) {
                throw new Exception('Quote not found');
            }

            // Handle status change if user has permission
            if (isset($data['status']) && $data['status'] !== $currentQuote['status']) {
                if ($this->auth->hasPermission('approve_quotes')) {
                    $statusResult = $this->updateQuoteStatus($id, $data['status']);
                    if (!$statusResult['success']) {
                        throw new Exception($statusResult['error']);
                    }
                } else {
                    throw new Exception('You do not have permission to change quote status');
                }
            }

            // Update customer information
            $stmt = $this->conn->prepare("
                UPDATE customers 
                SET company_name = ?,
                    contact_person = ?,
                    email = ?,
                    mobile_no = ?
                WHERE id = ?
            ");
            
            $stmt->bind_param("ssssi",
                $data['company_name'],
                $data['contact_person'],
                $data['email'],
                $data['mobile_no'],
                $currentQuote['customer_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error updating customer information');
            }

            // Calculate totals
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }
            $taxAmount = $totalAmount * 0.18; // 18% tax

            // Update quote
            $stmt = $this->conn->prepare("
                UPDATE quotes 
                SET total_amount = ?,
                    tax_amount = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->bind_param("ddi",
                $totalAmount,
                $taxAmount,
                $id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error updating quote');
            }

            // Update quote items
            $stmt = $this->conn->prepare("DELETE FROM quote_items WHERE quote_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $this->conn->prepare("
                INSERT INTO quote_items (
                    quote_id, 
                    description, 
                    quantity, 
                    unit_price, 
                    total_price
                ) VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($data['items'] as $item) {
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $stmt->bind_param("isddd",
                    $id,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $totalPrice
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Error updating quote items');
                }
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteQuote($id) {
        if (!$this->auth->hasPermission('delete_quotes')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }

        try {
            $this->conn->begin_transaction();

            // Get current quote status
            $stmt = $this->conn->prepare("SELECT status FROM quotes WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $quote = $stmt->get_result()->fetch_assoc();

            if (!$quote) {
                throw new Exception('Quote not found');
            }

            if ($quote['status'] !== 'draft') {
                throw new Exception('Only draft quotes can be deleted');
            }

            // Delete quote items first
            $stmt = $this->conn->prepare("DELETE FROM quote_items WHERE quote_id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception('Error deleting quote items');
            }

            // Delete the quote
            $stmt = $this->conn->prepare("DELETE FROM quotes WHERE id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception('Error deleting quote');
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function approveQuote($id) {
        if (!$this->auth->isAuthenticated()) {
            return ['error' => 'Unauthorized'];
        }

        try {
            $stmt = $this->conn->prepare("UPDATE quotes SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error approving quote: " . $stmt->error);
            }

            return ['success' => true, 'message' => 'Quote approved successfully'];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function convertToPO($quoteId) {
        if (!$this->auth->hasRole('admin')) {
            return ['error' => 'Unauthorized'];
        }

        $quote = $this->getQuote($quoteId);
        if (!$quote || $quote['status'] !== 'approved') {
            return ['error' => 'Quote must be approved before converting to PO'];
        }

        $this->conn->begin_transaction();

        try {
            // Generate PO number
            $year = date('Y');
            $month = date('m');
            $sql = "SELECT po_number FROM purchase_orders WHERE po_number LIKE 'PO{$year}{$month}%' ORDER BY id DESC LIMIT 1";
            $result = $this->conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $lastPO = $result->fetch_assoc();
                $lastNumber = intval(substr($lastPO['po_number'], -4));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $poNumber = "PO{$year}{$month}" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            // Create PO
            $stmt = $this->conn->prepare("INSERT INTO purchase_orders (po_number, quote_id, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $poNumber, $quoteId, $_SESSION['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating purchase order: " . $stmt->error);
            }

            $this->conn->commit();
            return ['success' => true, 'po_number' => $poNumber];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['error' => $e->getMessage()];
        }
    }
}
