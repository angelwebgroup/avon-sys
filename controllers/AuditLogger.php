<?php
class AuditLogger {
    private $conn;
    private $userId;

    public function __construct($db, $userId) {
        $this->conn = $db;
        $this->userId = $userId;
    }

    public function log($actionType, $entityType, $entityId, $oldValue = null, $newValue = null) {
        $stmt = $this->conn->prepare("INSERT INTO audit_logs (user_id, action_type, entity_type, entity_id, old_value, new_value, ip_address) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $oldValueJson = $oldValue ? json_encode($oldValue) : null;
        $newValueJson = $newValue ? json_encode($newValue) : null;
        
        $stmt->bind_param("ississs", 
            $this->userId,
            $actionType,
            $entityType,
            $entityId,
            $oldValueJson,
            $newValueJson,
            $ipAddress
        );
        
        return $stmt->execute();
    }

    public function getPermissionLogs($userId = null) {
        $sql = "SELECT * FROM permission_audit_logs";
        if ($userId) {
            $sql .= " WHERE affected_user = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
        }
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getEntityLogs($entityType, $entityId) {
        $sql = "SELECT al.*, u.username as performed_by
                FROM audit_logs al
                JOIN users u ON al.user_id = u.id
                WHERE al.entity_type = ? AND al.entity_id = ?
                ORDER BY al.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $entityType, $entityId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
