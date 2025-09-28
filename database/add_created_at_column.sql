-- Migration to add created_at column to records table
-- This enables trend analysis functionality

USE covid19recordsdb;

-- Add created_at column with default timestamp
ALTER TABLE `records` 
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Update existing records with random timestamps for demo purposes
-- This distributes records across recent dates for testing trends
UPDATE `records` 
SET `created_at` = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY)
WHERE `created_at` IS NULL OR `created_at` = '0000-00-00 00:00:00';

-- Verify the migration
SELECT COUNT(*) as total_records, 
       MIN(created_at) as earliest_record, 
       MAX(created_at) as latest_record 
FROM records;