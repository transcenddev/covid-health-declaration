<?php
/**
 * COVID-19 Health Declaration System - Daily Reset Script
 * 
 * Resets guest usage limits at midnight and performs maintenance cleanup
 * This script should be run via cron job or Windows Task Scheduler
 * 
 * @version 1.0
 * @date September 24, 2025
 * @author COVID-19 Health Declaration System
 */

// Security: Only allow execution from command line or specific IP
$allowed_execution = false;

// Check if running from command line
if (php_sapi_name() === 'cli') {
    $allowed_execution = true;
} else {
    // Allow execution from localhost only (for testing)
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $allowed_ips = ['127.0.0.1', '::1', 'localhost'];
    
    if (in_array($client_ip, $allowed_ips)) {
        $allowed_execution = true;
    }
}

// Security check: Require auth token for web execution
if (!$allowed_execution) {
    // Check for auth token in URL parameter (for web-based cron services)
    $auth_token = $_GET['token'] ?? '';
    $expected_token = hash('sha256', 'covid19_reset_' . date('Y-m-d')); // Daily rotating token
    
    if ($auth_token === $expected_token) {
        $allowed_execution = true;
    }
}

if (!$allowed_execution) {
    http_response_code(403);
    die('Access denied. This script can only be executed from authorized sources.');
}

// Set execution time limit for maintenance operations
set_time_limit(300); // 5 minutes max

// Include required files
require_once './includes/dbconn.inc.php';

// Initialize logging
$log_file = './logs/daily_reset.log';
$start_time = microtime(true);
$reset_date = date('Y-m-d H:i:s');

/**
 * Log message with timestamp
 */
function log_message($message, $level = 'INFO') {
    global $log_file;
    
    // Ensure logs directory exists
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    // Also output to console if running from CLI
    if (php_sapi_name() === 'cli') {
        echo $log_entry;
    }
}

/**
 * Reset daily usage counts for all guest users
 */
function reset_daily_limits($conn) {
    try {
        log_message("Starting daily usage limit reset...");
        
        // Get current count before reset for logging
        $count_sql = "SELECT COUNT(*) as total_records FROM guest_usage WHERE usage_today > 0";
        $count_result = mysqli_query($conn, $count_sql);
        
        if ($count_result) {
            $count_row = mysqli_fetch_assoc($count_result);
            $records_to_reset = $count_row['total_records'];
            mysqli_free_result($count_result);
        } else {
            $records_to_reset = 0;
            log_message("Could not count records to reset: " . mysqli_error($conn), 'WARNING');
        }
        
        // Reset all daily usage counts to 0
        $reset_sql = "UPDATE guest_usage SET usage_today = 0, last_usage = last_usage WHERE usage_today > 0";
        $reset_result = mysqli_query($conn, $reset_sql);
        
        if ($reset_result) {
            $affected_rows = mysqli_affected_rows($conn);
            log_message("Successfully reset daily limits for {$affected_rows} guest records (expected: {$records_to_reset})");
            
            // Update reset timestamp
            $update_timestamp_sql = "UPDATE guest_usage SET last_reset = NOW() WHERE usage_today = 0";
            mysqli_query($conn, $update_timestamp_sql);
            
            return [
                'success' => true,
                'records_reset' => $affected_rows,
                'message' => "Daily limits reset successfully"
            ];
        } else {
            $error = mysqli_error($conn);
            log_message("Failed to reset daily limits: {$error}", 'ERROR');
            
            return [
                'success' => false,
                'error' => $error,
                'message' => "Failed to reset daily limits"
            ];
        }
        
    } catch (Exception $e) {
        log_message("Exception during daily reset: " . $e->getMessage(), 'ERROR');
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'message' => "Exception occurred during reset"
        ];
    }
}

/**
 * Clean up old guest usage records (older than specified days)
 */
function cleanup_old_records($conn, $days_to_keep = 30) {
    try {
        log_message("Starting cleanup of records older than {$days_to_keep} days...");
        
        // Get count before cleanup for logging
        $count_sql = "SELECT COUNT(*) as total_old FROM guest_usage WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $count_stmt = mysqli_prepare($conn, $count_sql);
        
        if ($count_stmt) {
            mysqli_stmt_bind_param($count_stmt, 'i', $days_to_keep);
            mysqli_stmt_execute($count_stmt);
            $count_result = mysqli_stmt_get_result($count_stmt);
            $count_row = mysqli_fetch_assoc($count_result);
            $old_records = $count_row['total_old'];
            mysqli_stmt_close($count_stmt);
        } else {
            $old_records = 0;
            log_message("Could not count old records: " . mysqli_error($conn), 'WARNING');
        }
        
        // Delete old records
        $cleanup_sql = "DELETE FROM guest_usage WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $cleanup_stmt = mysqli_prepare($conn, $cleanup_sql);
        
        if ($cleanup_stmt) {
            mysqli_stmt_bind_param($cleanup_stmt, 'i', $days_to_keep);
            $cleanup_result = mysqli_stmt_execute($cleanup_stmt);
            
            if ($cleanup_result) {
                $deleted_rows = mysqli_affected_rows($conn);
                log_message("Successfully cleaned up {$deleted_rows} old records (expected: {$old_records})");
                mysqli_stmt_close($cleanup_stmt);
                
                return [
                    'success' => true,
                    'records_deleted' => $deleted_rows,
                    'message' => "Old records cleaned up successfully"
                ];
            } else {
                $error = mysqli_stmt_error($cleanup_stmt);
                log_message("Failed to clean up old records: {$error}", 'ERROR');
                mysqli_stmt_close($cleanup_stmt);
                
                return [
                    'success' => false,
                    'error' => $error,
                    'message' => "Failed to clean up old records"
                ];
            }
        } else {
            $error = mysqli_error($conn);
            log_message("Failed to prepare cleanup statement: {$error}", 'ERROR');
            
            return [
                'success' => false,
                'error' => $error,
                'message' => "Failed to prepare cleanup statement"
            ];
        }
        
    } catch (Exception $e) {
        log_message("Exception during cleanup: " . $e->getMessage(), 'ERROR');
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'message' => "Exception occurred during cleanup"
        ];
    }
}

/**
 * Optimize database tables after maintenance
 */
function optimize_database($conn) {
    try {
        log_message("Starting database optimization...");
        
        $optimize_sql = "OPTIMIZE TABLE guest_usage";
        $optimize_result = mysqli_query($conn, $optimize_sql);
        
        if ($optimize_result) {
            log_message("Database optimization completed successfully");
            
            return [
                'success' => true,
                'message' => "Database optimized successfully"
            ];
        } else {
            $error = mysqli_error($conn);
            log_message("Database optimization failed: {$error}", 'WARNING');
            
            return [
                'success' => false,
                'error' => $error,
                'message' => "Database optimization failed"
            ];
        }
        
    } catch (Exception $e) {
        log_message("Exception during optimization: " . $e->getMessage(), 'WARNING');
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'message' => "Exception occurred during optimization"
        ];
    }
}

/**
 * Generate daily reset report
 */
function generate_report($reset_result, $cleanup_result, $optimize_result, $execution_time) {
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'execution_time' => round($execution_time, 2),
        'operations' => [
            'reset' => $reset_result,
            'cleanup' => $cleanup_result,
            'optimize' => $optimize_result
        ]
    ];
    
    // Save report as JSON for potential API access
    $report_file = './logs/daily_reset_report_' . date('Y-m-d') . '.json';
    file_put_contents($report_file, json_encode($report, JSON_PRETTY_PRINT));
    
    return $report;
}

// Main execution
try {
    log_message("=== Daily Reset Script Started ===");
    log_message("Execution mode: " . (php_sapi_name() === 'cli' ? 'CLI' : 'Web'));
    
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    log_message("Database connection established successfully");
    
    // Perform daily reset
    $reset_result = reset_daily_limits($conn);
    
    // Perform cleanup (default: keep 30 days)
    $cleanup_result = cleanup_old_records($conn, 30);
    
    // Optimize database
    $optimize_result = optimize_database($conn);
    
    // Calculate execution time
    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;
    
    // Generate report
    $report = generate_report($reset_result, $cleanup_result, $optimize_result, $execution_time);
    
    // Final logging
    log_message("=== Daily Reset Script Completed ===");
    log_message("Total execution time: " . round($execution_time, 2) . " seconds");
    
    if ($reset_result['success'] && $cleanup_result['success']) {
        log_message("All operations completed successfully");
        $exit_code = 0;
    } else {
        log_message("Some operations failed - check logs for details", 'WARNING');
        $exit_code = 1;
    }
    
    // Close database connection
    mysqli_close($conn);
    
    // Output for web access
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $exit_code === 0 ? 'success' : 'warning',
            'message' => 'Daily reset completed',
            'report' => $report
        ]);
    }
    
    exit($exit_code);
    
} catch (Exception $e) {
    log_message("Fatal error: " . $e->getMessage(), 'ERROR');
    
    // Close database connection if it exists
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
    
    // Calculate execution time
    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;
    
    log_message("Script terminated after " . round($execution_time, 2) . " seconds");
    
    // Output for web access
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Daily reset failed',
            'error' => $e->getMessage(),
            'execution_time' => round($execution_time, 2)
        ]);
    }
    
    exit(2);
}
?>
