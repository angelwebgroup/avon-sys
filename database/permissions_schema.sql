-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User permissions table
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

-- Permission groups table
CREATE TABLE IF NOT EXISTS permission_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Permission group items table
CREATE TABLE IF NOT EXISTS permission_group_items (
    group_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (group_id, permission_id),
    FOREIGN KEY (group_id) REFERENCES permission_groups(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
-- Customer permissions
('view_customers', 'Can view customer list and details'),
('create_customers', 'Can create new customers'),
('edit_customers', 'Can edit existing customers'),
('delete_customers', 'Can delete customers'),
('approve_customers', 'Can approve or reject customers'),

-- Quote permissions
('view_quotes', 'Can view quote list and details'),
('create_quotes', 'Can create new quotes'),
('edit_quotes', 'Can edit existing quotes'),
('delete_quotes', 'Can delete quotes'),
('approve_quotes', 'Can approve or reject quotes'),
('convert_quote_to_po', 'Can convert quotes to purchase orders'),

-- Purchase Order permissions
('view_purchase_orders', 'Can view purchase order list and details'),
('create_purchase_orders', 'Can create new purchase orders'),
('edit_purchase_orders', 'Can edit purchase orders'),
('delete_purchase_orders', 'Can delete purchase orders'),
('approve_purchase_orders', 'Can approve purchase orders'),
('print_purchase_orders', 'Can print purchase orders'),

-- Inquiry permissions
('view_inquiries', 'Can view inquiry list and details'),
('create_inquiries', 'Can create new inquiries'),
('edit_inquiries', 'Can edit inquiries'),
('close_inquiries', 'Can close inquiries'),
('assign_inquiries', 'Can assign inquiries to users'),

-- Report permissions
('view_reports', 'Can view system reports'),
('export_reports', 'Can export reports'),

-- Dashboard permissions
('view_dashboard_stats', 'Can view dashboard statistics'),
('view_financial_data', 'Can view financial information');

-- Insert default permission groups
INSERT INTO permission_groups (name, description, created_by) VALUES
('basic_sales', 'Basic sales permissions - View only and create inquiries', 1),
('standard_sales', 'Standard sales permissions - Can manage customers and quotes', 1),
('advanced_sales', 'Advanced sales permissions - Can manage all sales functions', 1);

-- Basic sales permissions
INSERT INTO permission_group_items (group_id, permission_id)
SELECT 1, p.id FROM permissions p
WHERE p.name IN (
    'view_customers',
    'view_quotes',
    'view_purchase_orders',
    'view_inquiries',
    'create_inquiries',
    'view_dashboard_stats'
);

-- Standard sales permissions
INSERT INTO permission_group_items (group_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name IN (
    'view_customers',
    'create_customers',
    'edit_customers',
    'view_quotes',
    'create_quotes',
    'edit_quotes',
    'view_purchase_orders',
    'view_inquiries',
    'create_inquiries',
    'edit_inquiries',
    'view_dashboard_stats',
    'view_reports'
);

-- Advanced sales permissions
INSERT INTO permission_group_items (group_id, permission_id)
SELECT 3, p.id FROM permissions p
WHERE p.name IN (
    'view_customers',
    'create_customers',
    'edit_customers',
    'delete_customers',
    'approve_customers',
    'view_quotes',
    'create_quotes',
    'edit_quotes',
    'delete_quotes',
    'approve_quotes',
    'convert_quote_to_po',
    'view_purchase_orders',
    'edit_purchase_orders',
    'print_purchase_orders',
    'view_inquiries',
    'create_inquiries',
    'edit_inquiries',
    'close_inquiries',
    'assign_inquiries',
    'view_dashboard_stats',
    'view_reports',
    'export_reports',
    'view_financial_data'
);
