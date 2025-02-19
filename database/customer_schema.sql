-- Drop existing table if it exists
DROP TABLE IF EXISTS customers;

-- Create customers table with all fields from the registration form
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(100) NOT NULL,
    address_1 VARCHAR(255) NOT NULL,
    address_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state_code VARCHAR(50) NOT NULL,
    pin_code VARCHAR(20) NOT NULL,
    contact_person VARCHAR(100) NOT NULL,
    mobile_no VARCHAR(20) NOT NULL,
    telephone_no VARCHAR(20),
    email VARCHAR(100) NOT NULL,
    longitude DECIMAL(10, 8),
    latitude DECIMAL(10, 8),
    gst_registration_no VARCHAR(50),
    pan_no VARCHAR(20),
    freight_terms VARCHAR(50) DEFAULT 'FREIGHT INCL',
    hsn_sac_code VARCHAR(50),
    establishment_year INT,
    turnover_last_year DECIMAL(15, 2),
    payment_terms VARCHAR(50) DEFAULT '100% Advance',
    security_cheque BOOLEAN DEFAULT FALSE,
    credit_limit DECIMAL(15, 2) DEFAULT 0,
    enterprise_type VARCHAR(50),
    equity_type VARCHAR(50),
    product_segment VARCHAR(50),
    coordinator_name VARCHAR(100),
    remarks TEXT,
    customer_approved ENUM('Y', 'N') DEFAULT 'N',
    approved_by INT,
    approved_date DATETIME,
    customer_code VARCHAR(50),
    registered_by INT,
    registered_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (registered_by) REFERENCES users(id)
);

-- Create customer activity logs table
CREATE TABLE IF NOT EXISTS customer_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
