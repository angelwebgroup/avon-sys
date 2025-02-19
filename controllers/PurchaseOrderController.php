<?php
class PurchaseOrderController {
    private $conn;
    private $auth;

    public function __construct($db, $auth) {
        $this->conn = $db;
        $this->auth = $auth;
    }

    public function getPO($id) {
        if (!$this->auth->isAuthenticated()) {
            return ['error' => 'Unauthorized'];
        }

        $sql = "SELECT po.*, q.quote_number, q.total_amount, q.tax_amount,
                       c.company_name, c.contact_person, c.email, c.mobile_no, 
                       c.address, c.city, c.state_code, c.pin_code,
                       u1.username as created_by_name, u2.username as approved_by_name
                FROM purchase_orders po
                LEFT JOIN quotes q ON po.quote_id = q.id
                LEFT JOIN customers c ON q.customer_id = c.id
                LEFT JOIN users u1 ON po.created_by = u1.id
                LEFT JOIN users u2 ON po.approved_by = u2.id
                WHERE po.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $po = $stmt->get_result()->fetch_assoc();

        if ($po) {
            // Get quote items
            $sql = "SELECT * FROM quote_items WHERE quote_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $po['quote_id']);
            $stmt->execute();
            $po['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $po;
    }

    public function updatePO($id, $data) {
        if (!$this->auth->hasPermission('edit_purchase_orders')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }

        try {
            $this->conn->begin_transaction();

            // Get current PO
            $currentPO = $this->getPO($id);
            if (!$currentPO) {
                throw new Exception('Purchase order not found');
            }

            $updates = [];
            $types = "";
            $values = [];

            // Handle delivery date
            if (isset($data['delivery_date'])) {
                $updates[] = "delivery_date = ?";
                $types .= "s";
                $values[] = $data['delivery_date'];
            }

            // Handle status change if user has permission
            if (isset($data['status']) && $data['status'] !== $currentPO['status']) {
                if (!$this->auth->hasPermission('approve_purchase_orders')) {
                    throw new Exception('You do not have permission to change PO status');
                }

                // Validate status
                $validStatuses = ['pending', 'approved', 'cancelled'];
                if (!in_array($data['status'], $validStatuses)) {
                    throw new Exception('Invalid status value');
                }

                $updates[] = "status = ?";
                $types .= "s";
                $values[] = $data['status'];

                if ($data['status'] === 'approved') {
                    $updates[] = "approved_by = ?";
                    $updates[] = "approved_at = CURRENT_TIMESTAMP";
                    $types .= "i";
                    $values[] = $_SESSION['user_id'];
                }
            }

            if (empty($updates)) {
                throw new Exception('No changes to update');
            }

            // Add ID to values
            $values[] = $id;
            $types .= "i";

            // Update PO
            $sql = "UPDATE purchase_orders SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$values);

            if (!$stmt->execute()) {
                throw new Exception('Error updating purchase order');
            }

            // Log the update
            if (isset($data['status']) && $data['status'] !== $currentPO['status']) {
                $notes = "Status changed from {$currentPO['status']} to {$data['status']}";
                $stmt = $this->conn->prepare("
                    INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, notes)
                    VALUES (?, 'status_change', 'purchase_order', ?, ?)
                ");
                $stmt->bind_param("iis", $_SESSION['user_id'], $id, $notes);
                $stmt->execute();
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deletePO($id) {
        if (!$this->auth->hasRole('admin')) {
            return ['error' => 'Unauthorized'];
        }

        $po = $this->getPO($id);
        if (!$po) {
            return ['error' => 'Purchase order not found'];
        }

        if ($po['status'] !== 'pending') {
            return ['error' => 'Only pending purchase orders can be deleted'];
        }

        $stmt = $this->conn->prepare("DELETE FROM purchase_orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['error' => $stmt->error];
    }

    public function generatePDF($id) {
        $po = $this->getPO($id);
        if (!$po) {
            return ['error' => 'Purchase order not found'];
        }

        // Implementation for PDF generation
        // This would typically use a library like TCPDF or FPDF
        // For now, we'll return the data that would be used in the PDF
        return [
            'success' => true,
            'data' => $po
        ];
    }
}
