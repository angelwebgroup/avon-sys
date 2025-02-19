<?php
class InquiryController {
    private $conn;
    private $auth;

    public function __construct($db, $auth) {
        $this->conn = $db;
        $this->auth = $auth;
    }

    public function createInquiry($data) {
        if (!$this->auth->hasPermission('create_inquiries')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO inquiries (
                    company_name, 
                    contact_person, 
                    email, 
                    mobile_no, 
                    subject, 
                    message,
                    status, 
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, 'new', ?)
            ");

            $stmt->bind_param(
                "ssssssi",
                $data['company_name'],
                $data['contact_person'],
                $data['email'],
                $data['mobile_no'],
                $data['subject'],
                $data['message'],
                $_SESSION['user_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error creating inquiry: ' . $stmt->error);
            }

            return ['success' => true, 'inquiry_id' => $stmt->insert_id];
        } catch (Exception $e) {
            error_log("Error in createInquiry: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getInquiry($id) {
        if (!$this->auth->hasPermission('view_inquiries')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }

        try {
            $sql = "SELECT i.*, 
                           u1.username as created_by_name, 
                           u2.username as assigned_to_name
                    FROM inquiries i
                    LEFT JOIN users u1 ON i.created_by = u1.id
                    LEFT JOIN users u2 ON i.assigned_to = u2.id
                    WHERE i.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error preparing statement: ' . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception('Error executing query: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $inquiry = $result->fetch_assoc();

            if (!$inquiry) {
                throw new Exception('Inquiry not found');
            }

            return ['success' => true, 'inquiry' => $inquiry];
        } catch (Exception $e) {
            error_log("Error in getInquiry: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateInquiry($id, $data) {
        if (!$this->auth->hasPermission('edit_inquiries')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }

        try {
            $this->conn->begin_transaction();

            $updates = [];
            $types = "";
            $values = [];

            // Handle basic fields
            $allowedFields = [
                'company_name' => 's',
                'contact_person' => 's',
                'email' => 's',
                'mobile_no' => 's',
                'subject' => 's',
                'message' => 's',
                'status' => 's'
            ];

            foreach ($allowedFields as $field => $type) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $types .= $type;
                    $values[] = $data[$field];
                }
            }

            // Add the ID at the end of the parameter list
            $types .= "i";
            $values[] = $id;

            $sql = "UPDATE inquiries SET " . implode(", ", $updates) . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error preparing statement: ' . $this->conn->error);
            }

            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) {
                throw new Exception('Error executing update: ' . $stmt->error);
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in updateInquiry: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getInquiries($filters = []) {
        if (!$this->auth->hasPermission('view_inquiries')) {
            return ['success' => false, 'error' => 'Permission denied'];
        }

        try {
            $sql = "SELECT i.*, 
                           u1.username as created_by_name, 
                           u2.username as assigned_to_name
                    FROM inquiries i
                    LEFT JOIN users u1 ON i.created_by = u1.id
                    LEFT JOIN users u2 ON i.assigned_to = u2.id";

            $where = [];
            $params = [];
            $types = "";

            // Apply filters
            if (!empty($filters['status'])) {
                $where[] = "i.status = ?";
                $params[] = $filters['status'];
                $types .= "s";
            }

            if (!empty($filters['created_by'])) {
                $where[] = "u1.username = ?";
                $params[] = $filters['created_by'];
                $types .= "s";
            }

            if (!empty($filters['search'])) {
                $searchTerm = "%" . $filters['search'] . "%";
                $where[] = "(i.company_name LIKE ? OR i.contact_person LIKE ? OR i.subject LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }

            if (!empty($filters['date_filter'])) {
                switch ($filters['date_filter']) {
                    case 'today':
                        $where[] = "DATE(i.created_at) = CURDATE()";
                        break;
                    case 'yesterday':
                        $where[] = "DATE(i.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                        break;
                    case 'last_7_days':
                        $where[] = "i.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                        break;
                    case 'this_month':
                        $where[] = "MONTH(i.created_at) = MONTH(CURDATE()) AND YEAR(i.created_at) = YEAR(CURDATE())";
                        break;
                    case 'last_month':
                        $where[] = "i.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                        break;
                }
            }

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY i.created_at DESC";

            if (!empty($params)) {
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Error preparing statement: ' . $this->conn->error);
                }
                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) {
                    throw new Exception('Error executing query: ' . $stmt->error);
                }
                $result = $stmt->get_result();
            } else {
                $result = $this->conn->query($sql);
                if (!$result) {
                    throw new Exception('Error executing query: ' . $this->conn->error);
                }
            }

            $inquiries = [];
            while ($row = $result->fetch_assoc()) {
                $inquiries[] = $row;
            }

            return $inquiries;
        } catch (Exception $e) {
            error_log("Error in getInquiries: " . $e->getMessage());
            return [];
        }
    }

    public function getUsers() {
        try {
            $sql = "SELECT DISTINCT u.id, u.username FROM users u 
                      JOIN inquiries i ON u.id = i.created_by";
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception('Error executing query: ' . $this->conn->error);
            }
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getUsers: " . $e->getMessage());
            return [];
        }
    }
}
