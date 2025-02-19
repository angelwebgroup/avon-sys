-- Add default value to mobile_no column in customers table
ALTER TABLE customers
MODIFY COLUMN mobile_no VARCHAR(20) DEFAULT '';
