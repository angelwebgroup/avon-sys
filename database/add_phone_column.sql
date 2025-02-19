-- Add phone column to customers table
ALTER TABLE customers
ADD COLUMN phone VARCHAR(20) DEFAULT NULL;
