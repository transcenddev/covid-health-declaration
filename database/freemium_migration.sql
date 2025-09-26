-- COVID-19 Health Declaration System
-- Guest Usage Tracking Migration
-- Date: September 24, 2025
-- Description: Adds guest usage tracking functionality for freemium model

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Use COVID19RecordsDB database
--

USE `COVID19RecordsDB`;

-- --------------------------------------------------------

--
-- Table structure for table `guest_usage`
--

CREATE TABLE `guest_usage` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
  `usage_count` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of health declarations submitted',
  `date` date NOT NULL COMMENT 'Date of usage (YYYY-MM-DD)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'First usage timestamp',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last usage timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks guest usage for freemium model';

--
-- Indexes for table `guest_usage`
--

ALTER TABLE `guest_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip_date` (`ip_address`, `date`) COMMENT 'Prevent duplicate entries for same IP on same date',
  ADD KEY `idx_ip_address` (`ip_address`) COMMENT 'Fast lookup by IP address',
  ADD KEY `idx_date` (`date`) COMMENT 'Fast lookup by date for cleanup',
  ADD KEY `idx_created_at` (`created_at`) COMMENT 'Fast lookup for cleanup operations';

--
-- AUTO_INCREMENT for table `guest_usage`
--

ALTER TABLE `guest_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key with auto increment';

-- --------------------------------------------------------

--
-- Create stored procedure for cleanup of old records (older than 30 days)
--

DELIMITER $$

CREATE PROCEDURE `CleanupGuestUsage`()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Delete records older than 30 days
    DELETE FROM `guest_usage` 
    WHERE `date` < DATE_SUB(CURDATE(), INTERVAL 30 DAY);
    
    -- Log the cleanup operation (optional)
    SELECT CONCAT('Cleaned up guest usage records older than 30 days at ', NOW()) AS cleanup_result;
    
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Create stored procedure for tracking guest usage
--

DELIMITER $$

CREATE PROCEDURE `TrackGuestUsage`(
    IN p_ip_address VARCHAR(45)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Insert or update usage count for today
    INSERT INTO `guest_usage` (`ip_address`, `usage_count`, `date`, `created_at`)
    VALUES (p_ip_address, 1, CURDATE(), NOW())
    ON DUPLICATE KEY UPDATE 
        `usage_count` = `usage_count` + 1,
        `updated_at` = NOW();
    
    COMMIT;
    
    -- Return current usage count for today
    SELECT `usage_count` 
    FROM `guest_usage` 
    WHERE `ip_address` = p_ip_address AND `date` = CURDATE();
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Create stored procedure to get current usage count for an IP
--

DELIMITER $$

CREATE PROCEDURE `GetGuestUsageCount`(
    IN p_ip_address VARCHAR(45)
)
BEGIN
    SELECT COALESCE(`usage_count`, 0) as current_usage
    FROM `guest_usage` 
    WHERE `ip_address` = p_ip_address AND `date` = CURDATE();
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Create event scheduler for automatic cleanup (runs daily at 2:00 AM)
--

SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS `daily_guest_usage_cleanup`
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '02:00:00')
DO
    CALL CleanupGuestUsage();

-- --------------------------------------------------------

--
-- Insert sample data for testing (optional - remove in production)
--

INSERT INTO `guest_usage` (`ip_address`, `usage_count`, `date`, `created_at`) VALUES
('192.168.1.100', 3, CURDATE(), NOW()),
('10.0.0.50', 1, CURDATE(), NOW()),
('203.0.113.25', 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
('198.51.100.42', 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- --------------------------------------------------------
--
-- Usage Instructions:
--
-- 1. To track guest usage:
--    CALL TrackGuestUsage('192.168.1.100');
--
-- 2. To get current usage count:
--    CALL GetGuestUsageCount('192.168.1.100');
--
-- 3. To manually cleanup old records:
--    CALL CleanupGuestUsage();
--
-- 4. To check usage before allowing submission (PHP example):
--    $result = mysqli_query($conn, "CALL GetGuestUsageCount('{$user_ip}')");
--    $usage = mysqli_fetch_assoc($result)['current_usage'];
--    if ($usage >= 5) { /* Show upgrade message */ }
--
-- --------------------------------------------------------
