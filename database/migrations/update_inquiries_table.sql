-- Drop foreign key constraints if they exist
SET @constraint_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'avon_sys'
    AND TABLE_NAME = 'inquiries'
    AND CONSTRAINT_NAME = 'inquiries_ibfk_1'
);

SET @sql := IF(@constraint_exists > 0,
    'ALTER TABLE `inquiries` DROP FOREIGN KEY `inquiries_ibfk_1`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'avon_sys'
    AND TABLE_NAME = 'inquiries'
    AND CONSTRAINT_NAME = 'inquiries_ibfk_2'
);

SET @sql := IF(@constraint_exists > 0,
    'ALTER TABLE `inquiries` DROP FOREIGN KEY `inquiries_ibfk_2`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'avon_sys'
    AND TABLE_NAME = 'inquiries'
    AND CONSTRAINT_NAME = 'inquiries_ibfk_3'
);

SET @sql := IF(@constraint_exists > 0,
    'ALTER TABLE `inquiries` DROP FOREIGN KEY `inquiries_ibfk_3`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop customer_id column if it exists
SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'avon_sys'
    AND TABLE_NAME = 'inquiries'
    AND COLUMN_NAME = 'customer_id'
);

SET @sql := IF(@column_exists > 0,
    'ALTER TABLE `inquiries` DROP COLUMN `customer_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new columns if they don't exist
SET @company_name_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'avon_sys'
    AND TABLE_NAME = 'inquiries'
    AND COLUMN_NAME = 'company_name'
);

SET @sql := IF(@company_name_exists = 0,
    'ALTER TABLE `inquiries` 
    ADD COLUMN `company_name` varchar(255) NOT NULL AFTER `id`,
    ADD COLUMN `contact_person` varchar(255) NOT NULL AFTER `company_name`,
    ADD COLUMN `email` varchar(255) NOT NULL AFTER `contact_person`,
    ADD COLUMN `mobile_no` varchar(20) NOT NULL AFTER `email`,
    ADD COLUMN `message` text NOT NULL AFTER `subject`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Handle status conversion
SET @new_status_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'avon_sys'
    AND TABLE_NAME = 'inquiries'
    AND COLUMN_NAME = 'new_status'
);

SET @sql := IF(@new_status_exists = 0,
    'ALTER TABLE `inquiries` ADD COLUMN `new_status` enum("new","in_progress","completed","cancelled") NOT NULL DEFAULT "new" AFTER `status`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Convert old status to new status if old status exists
SET @status_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'avon_sys'
    AND TABLE_NAME = 'inquiries'
    AND COLUMN_NAME = 'status'
);

SET @sql := IF(@status_exists > 0,
    'UPDATE `inquiries` 
    SET `new_status` = CASE 
        WHEN `status` = "open" THEN "new"
        WHEN `status` = "in_progress" THEN "in_progress"
        WHEN `status` = "closed" THEN "completed"
        ELSE "new"
    END',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop old status and rename new_status if they exist
SET @sql := IF(@status_exists > 0,
    'ALTER TABLE `inquiries` DROP COLUMN `status`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@new_status_exists > 0,
    'ALTER TABLE `inquiries` CHANGE COLUMN `new_status` `status` enum("new","in_progress","completed","cancelled") NOT NULL DEFAULT "new"',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Convert description to message if it exists
SET @description_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'avon_sys'
    AND TABLE_NAME = 'inquiries'
    AND COLUMN_NAME = 'description'
);

SET @sql := IF(@description_exists > 0,
    'UPDATE `inquiries` SET `message` = `description`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@description_exists > 0,
    'ALTER TABLE `inquiries` DROP COLUMN `description`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraints
ALTER TABLE `inquiries`
    ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
