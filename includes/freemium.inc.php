<?php
/**
 * COVID-19 Health Declaration System - Freemium Management
 * 
 * Core functions for managing guest usage limits and freemium model
 * Follows project MySQLi patterns with improved prepared statements
 * 
 * @version 1.0
 * @date September 24, 2025
 */

// Include database connection
require_once 'dbconn.inc.php';

// Freemium configuration
define('GUEST_DAILY_LIMIT', 3);
define('PREMIUM_DAILY_LIMIT', 999); // Effectively unlimited

/**
 * Check guest usage limit for given IP address
 * Returns remaining submissions allowed (0-3)
 * 
 * @param string $ip IP address to check
 * @return int Remaining submissions (0 means limit reached)
 */
function check_guest_limit($ip) {
    global $conn;
    
    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        die("Invalid IP address provided");
    }
    
    // Prepare statement to get current usage
    $stmt = mysqli_prepare($conn, "SELECT usage_count FROM guest_usage WHERE ip_address = ? AND date = CURDATE()");
    
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    // Bind parameters and execute
    mysqli_stmt_bind_param($stmt, "s", $ip);
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Execute failed: " . mysqli_error($conn));
    }
    
    // Get result
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        die("Get result failed: " . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    $current_usage = $row ? (int)$row['usage_count'] : 0;
    
    // Close statement
    mysqli_stmt_close($stmt);
    
    // Calculate remaining submissions
    $remaining = GUEST_DAILY_LIMIT - $current_usage;
    
    return max(0, $remaining);
}

/**
 * Record guest usage for given IP address
 * Increments daily count or creates new record if first usage today
 * 
 * @param string $ip IP address to record
 * @return bool True on success, dies on error
 */
function record_guest_usage($ip) {
    global $conn;
    
    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        die("Invalid IP address provided");
    }
    
    // Check if already at limit before recording
    if (check_guest_limit($ip) <= 0) {
        die("Daily usage limit exceeded for IP: " . htmlspecialchars($ip));
    }
    
    // Prepare statement for INSERT ... ON DUPLICATE KEY UPDATE
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO guest_usage (ip_address, usage_count, date, created_at) 
         VALUES (?, 1, CURDATE(), NOW()) 
         ON DUPLICATE KEY UPDATE 
         usage_count = usage_count + 1, 
         updated_at = NOW()"
    );
    
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    // Bind parameters and execute
    mysqli_stmt_bind_param($stmt, "s", $ip);
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Execute failed: " . mysqli_error($conn));
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
    
    return true;
}

/**
 * Get comprehensive usage statistics for given IP address
 * Returns array with current usage, limit, remaining, and status info
 * 
 * @param string $ip IP address to get stats for
 * @return array Usage statistics
 */
function get_usage_stats($ip) {
    global $conn;
    
    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        die("Invalid IP address provided");
    }
    
    // Prepare statement to get detailed usage info
    $stmt = mysqli_prepare($conn, 
        "SELECT usage_count, created_at, updated_at 
         FROM guest_usage 
         WHERE ip_address = ? AND date = CURDATE()"
    );
    
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    // Bind parameters and execute
    mysqli_stmt_bind_param($stmt, "s", $ip);
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Execute failed: " . mysqli_error($conn));
    }
    
    // Get result
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        die("Get result failed: " . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    
    // Close statement
    mysqli_stmt_close($stmt);
    
    // Build statistics array
    $current_usage = $row ? (int)$row['usage_count'] : 0;
    $remaining = max(0, GUEST_DAILY_LIMIT - $current_usage);
    
    $stats = [
        'ip_address' => $ip,
        'current_usage' => $current_usage,
        'daily_limit' => GUEST_DAILY_LIMIT,
        'remaining' => $remaining,
        'limit_reached' => ($remaining <= 0),
        'percentage_used' => round(($current_usage / GUEST_DAILY_LIMIT) * 100, 1),
        'first_usage_today' => $row ? $row['created_at'] : null,
        'last_usage_today' => $row ? $row['updated_at'] : null,
        'is_new_user' => !$row, // True if no usage record exists for today
        'status' => get_usage_status($remaining)
    ];
    
    return $stats;
}

/**
 * Get user-friendly status message based on remaining usage
 * 
 * @param int $remaining Remaining submissions
 * @return string Status message
 */
function get_usage_status($remaining) {
    if ($remaining <= 0) {
        return 'limit_reached';
    } elseif ($remaining == 1) {
        return 'last_submission';
    } elseif ($remaining == 2) {
        return 'low_usage';
    } else {
        return 'normal_usage';
    }
}

/**
 * Get client IP address with proxy support
 * Handles various proxy headers and fallbacks
 * 
 * @return string Client IP address
 */
function get_client_ip() {
    // Check for various proxy headers
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            
            // Validate the IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR even if it's a private IP (for local development)
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Check if user is authenticated (has premium access)
 * 
 * @return bool True if user is logged in (premium), false if guest
 */
function is_premium_user() {
    return isset($_SESSION['userId']) && !empty($_SESSION['userId']);
}

/**
 * Get usage limit based on user type
 * 
 * @return int Daily usage limit
 */
function get_user_limit() {
    return is_premium_user() ? PREMIUM_DAILY_LIMIT : GUEST_DAILY_LIMIT;
}

/**
 * Validate submission eligibility and return status
 * Combines authentication and usage limit checking
 * 
 * @param string $ip IP address to check (optional, will auto-detect if not provided)
 * @return array Eligibility status and information
 */
function check_submission_eligibility($ip = null) {
    if ($ip === null) {
        $ip = get_client_ip();
    }
    
    // Premium users have unlimited access
    if (is_premium_user()) {
        return [
            'allowed' => true,
            'user_type' => 'premium',
            'remaining' => PREMIUM_DAILY_LIMIT,
            'message' => 'Premium user - unlimited submissions'
        ];
    }
    
    // Check guest limits
    $remaining = check_guest_limit($ip);
    
    return [
        'allowed' => ($remaining > 0),
        'user_type' => 'guest',
        'remaining' => $remaining,
        'message' => $remaining > 0 
            ? "You have {$remaining} submission(s) remaining today" 
            : "Daily limit reached. Please sign up for unlimited access."
    ];
}

/**
 * Clean up old usage records (call periodically or via cron)
 * Removes records older than specified days
 * 
 * @param int $days Number of days to keep (default 30)
 * @return int Number of records deleted
 */
function cleanup_old_usage($days = 30) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "DELETE FROM guest_usage WHERE date < DATE_SUB(CURDATE(), INTERVAL ? DAY)");
    
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $days);
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Execute failed: " . mysqli_error($conn));
    }
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    return $affected_rows;
}

/**
 * Log freemium events for debugging and analytics
 * 
 * @param string $event Event type
 * @param string $ip IP address
 * @param array $data Additional data
 */
function log_freemium_event($event, $ip, $data = []) {
    // Simple file-based logging (can be enhanced with database logging)
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $ip,
        'data' => $data
    ];
    
    $log_file = '../logs/freemium.log';
    
    // Create logs directory if it doesn't exist
    if (!is_dir('../logs')) {
        mkdir('../logs', 0755, true);
    }
    
    // Append to log file
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

?>
