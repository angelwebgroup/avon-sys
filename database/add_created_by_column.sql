-- Add created_by column to customers table
ALTER TABLE customers
ADD COLUMN created_by INT DEFAULT NULL;
