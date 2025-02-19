-- Add sample customers
INSERT INTO customers (company_name, contact_person, email, phone, address, status, created_by) VALUES
('ABC Corporation', 'John Smith', 'john@abc.com', '123-456-7890', '123 Business St, City', 'approved', 1),
('XYZ Industries', 'Jane Doe', 'jane@xyz.com', '987-654-3210', '456 Industry Ave, Town', 'approved', 2),
('Tech Solutions', 'Mike Johnson', 'mike@techsol.com', '555-123-4567', '789 Tech Blvd, Valley', 'pending', 4),
('Global Traders', 'Sarah Wilson', 'sarah@global.com', '444-555-6666', '321 Trade Center, City', 'approved', 4);

-- Add sample quotes
INSERT INTO quotes (quote_number, customer_id, total_amount, tax_amount, status, created_by) VALUES
('Q202401001', 1, 5000.00, 900.00, 'approved', 1),
('Q202401002', 2, 7500.00, 1350.00, 'pending', 2),
('Q202401003', 3, 3200.00, 576.00, 'draft', 4),
('Q202401004', 4, 12000.00, 2160.00, 'approved', 4);

-- Add sample quote items
INSERT INTO quote_items (quote_id, description, quantity, unit_price, total_price) VALUES
(1, 'Product A - Premium Package', 2, 2500.00, 5000.00),
(2, 'Service B - Annual Subscription', 1, 7500.00, 7500.00),
(3, 'Product C - Basic Package', 4, 800.00, 3200.00),
(4, 'Service D - Enterprise Solution', 1, 12000.00, 12000.00);

-- Add sample purchase orders
INSERT INTO purchase_orders (po_number, quote_id, status, delivery_date, created_by) VALUES
('PO202401001', 1, 'approved', '2024-02-28', 1),
('PO202401002', 4, 'pending', '2024-03-15', 2);

-- Add sample inquiries
INSERT INTO inquiries (customer_id, subject, description, status, assigned_to, created_by) VALUES
(1, 'Product Information Request', 'Need detailed specifications for Product A', 'open', 6, 1),
(2, 'Service Upgrade Inquiry', 'Interested in upgrading current service package', 'in_progress', 6, 2),
(3, 'Quote Request', 'Requesting quote for bulk order', 'open', NULL, 4),
(4, 'Support Inquiry', 'Technical support needed for recent installation', 'closed', 4, 4);
