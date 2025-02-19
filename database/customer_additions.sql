-- --------------------------------------------------------
-- Customer Management System Additional Tables
-- --------------------------------------------------------
-- Author: Cascade (AI Assistant)
-- Date: 2025-02-18
-- Version: 1.0.0
-- 
-- This SQL script adds document management and activity logging
-- capabilities to the customer management system.
-- --------------------------------------------------------

-- Customer Documents Table
CREATE TABLE customer_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Customer Activity Logs Table
CREATE TABLE customer_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    performed_by INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
);
