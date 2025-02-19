-- Add new fields to users table
ALTER TABLE users
ADD COLUMN first_name VARCHAR(50) AFTER username,
ADD COLUMN last_name VARCHAR(50) AFTER first_name,
ADD COLUMN phone VARCHAR(20) AFTER email,
ADD COLUMN address TEXT AFTER phone,
ADD COLUMN department VARCHAR(50) AFTER address,
ADD COLUMN position VARCHAR(50) AFTER department;
