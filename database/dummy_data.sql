-- Insert dummy customers
INSERT INTO customers (
    company_name, address_1, address_2, city, state_code, pin_code,
    contact_person, mobile_no, telephone_no, email,
    gst_registration_no, pan_no, freight_terms, hsn_sac_code,
    establishment_year, turnover_last_year, payment_terms,
    security_cheque, credit_limit, enterprise_type, equity_type,
    product_segment, coordinator_name, remarks,
    customer_approved, customer_code, created_at
) VALUES 
-- Customer 1: Large Public Company
(
    'Samsung India Electronics Pvt Ltd',
    'Plot No. 2A, Sector 126',
    'Noida Express Highway',
    'Noida', 'UP', '201304',
    'Rajesh Kumar', '9876543210', '0120-4567890',
    'rajesh.kumar@samsung.com',
    'GST09AABCS1234Z1ZX', 'AABCS1234Z', 'FREIGHT INCL', 'HSN8415',
    1995, 50000000.00, '30 Days Credit',
    1, 1000000.00, 'Large', 'PUBLIC LTD.',
    'WHITE GOODS', 'ARVIND', 'Major account with good payment history',
    'Y', 'CUST001', NOW()
),

-- Customer 2: Medium Private Company
(
    'Havells India Limited',
    '48, Sector 14, Industrial Area',
    'Near Metro Station',
    'Gurugram', 'HR', '122001',
    'Amit Singh', '9876543211', '0124-4567891',
    'amit.singh@havells.com',
    'GST06AABCH1234Y1ZX', 'AABCH1234Y', 'FREIGHT INCL', 'HSN8516',
    1983, 25000000.00, '100% Advance',
    0, 0.00, 'Large', 'PUBLIC LTD.',
    'WHITE GOODS', 'ARVIND', 'Regular customer',
    'Y', 'CUST002', NOW()
),

-- Customer 3: Small Enterprise
(
    'Cool Tech Solutions',
    '15A, Industrial Estate',
    'Phase II',
    'Pune', 'MH', '411057',
    'Priya Patel', '9876543212', '020-4567892',
    'priya.patel@cooltech.com',
    'GST27AABCC1234Z1ZX', 'AABCC1234Z', 'FREIGHT INCL', 'HSN8418',
    2010, 5000000.00, '50% Advance',
    0, 0.00, 'Small', 'PVT. LTD.',
    'BROWN GOODS', 'ARVIND', 'Growing business',
    'N', 'CUST003', NOW()
),

-- Customer 4: New Customer
(
    'Global Appliances Ltd',
    '72, Trade Center',
    'Anna Salai',
    'Chennai', 'TN', '600002',
    'Suresh Kumar', '9876543213', '044-4567893',
    'suresh@globalappliances.com',
    'GST33AABCG1234Z1ZX', 'AABCG1234Z', 'FREIGHT INCL', 'HSN8422',
    2015, 15000000.00, '45 Days Credit',
    1, 500000.00, 'Medium', 'PVT. LTD.',
    'BOTH', 'ARVIND', 'New customer with good market reputation',
    'N', 'CUST004', NOW()
),

-- Customer 5: Partnership Firm
(
    'Modern Electronics',
    '23, Commercial Complex',
    'M.G. Road',
    'Bengaluru', 'KA', '560001',
    'Mohammed Ali', '9876543214', '080-4567894',
    'mali@modernelectronics.com',
    'GST29AABCM1234Z1ZX', 'AABCM1234Z', 'FREIGHT EXTRA', 'HSN8450',
    2008, 8000000.00, '100% Advance',
    0, 0.00, 'Small', 'PARTNERSHIP',
    'BROWN GOODS', 'ARVIND', 'Regular buyer of components',
    'Y', 'CUST005', NOW()
);

-- Update some customers to have latitude/longitude
UPDATE customers SET 
    latitude = 28.5355, 
    longitude = 77.3910 
WHERE customer_code = 'CUST001';

UPDATE customers SET 
    latitude = 28.4595, 
    longitude = 77.0266 
WHERE customer_code = 'CUST002';

UPDATE customers SET 
    latitude = 18.5204, 
    longitude = 73.8567 
WHERE customer_code = 'CUST003';

UPDATE customers SET 
    latitude = 13.0827, 
    longitude = 80.2707 
WHERE customer_code = 'CUST004';

UPDATE customers SET 
    latitude = 12.9716, 
    longitude = 77.5946 
WHERE customer_code = 'CUST005';

-- Set some customers as approved by admin
UPDATE customers SET 
    customer_approved = 'Y',
    approved_by = (SELECT id FROM users WHERE role = 'admin' LIMIT 1),
    approved_date = NOW()
WHERE customer_code IN ('CUST001', 'CUST002', 'CUST005');

-- Set registered_by for all customers
UPDATE customers SET 
    registered_by = (SELECT id FROM users WHERE role = 'admin' LIMIT 1),
    registered_date = NOW();
