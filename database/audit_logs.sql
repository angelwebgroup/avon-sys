-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Permission audit logs view
CREATE OR REPLACE VIEW permission_audit_logs AS
SELECT 
    al.id,
    al.created_at,
    u1.username as performed_by,
    u2.username as affected_user,
    al.action_type,
    al.old_value,
    al.new_value
FROM audit_logs al
JOIN users u1 ON al.user_id = u1.id
JOIN users u2 ON al.entity_id = u2.id
WHERE al.entity_type = 'permission'
ORDER BY al.created_at DESC;
