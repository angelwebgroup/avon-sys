-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- User permissions mapping table
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id),
    FOREIGN KEY (granted_by) REFERENCES users(id)
);

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
('manage_inquiries', 'Can create and manage inquiries'),
('manage_quotes', 'Can create and manage quotes'),
('manage_customers', 'Can create and manage customers'),
('manage_purchase_orders', 'Can create and manage purchase orders'),
('convert_quote_to_po', 'Can convert quotes to purchase orders'),
('approve_sales_managers', 'Can approve or disapprove sales managers');

-- Update users table to include status
ALTER TABLE users 
ADD COLUMN status ENUM('pending', 'approved', 'rejected', 'inactive') DEFAULT 'pending' AFTER role;
