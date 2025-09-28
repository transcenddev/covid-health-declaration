-- Migration to add created_at column for time range filtering
-- Run this SQL in phpMyAdmin or MySQL command line

USE covid19recordsdb;

-- Add created_at column to records table
ALTER TABLE `records` 
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `nationality`;

-- Add index for better query performance on date filtering
ALTER TABLE `records` 
ADD INDEX `idx_created_at` (`created_at`);

-- Update existing records with sample dates for testing
-- (This spreads existing records across the last 60 days)
UPDATE `records` 
SET `created_at` = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 60) DAY)
WHERE `created_at` = '0000-00-00 00:00:00' OR `created_at` IS NULL;

-- Show the updated table structure
DESCRIBE `records`;

-- Show sample of updated records
SELECT id, email, full_name, created_at FROM `records` ORDER BY created_at DESC LIMIT 10;