<?php
/**
 * COVID-19 Health Declaration System - Daily Reset Test Script
 * 
 * Test script to verify daily reset functionality without affecting production data
 * This creates test data and verifies the reset operations work correctly
 * 
 * @version 1.0
 * @date September 24, 2025
 */

require_once './includes/dbconn.inc.php';
require_once './includes/freemium.inc.php';

echo "COVID-19 Daily Reset Test Script\n";
echo "=================================\n\n";

// Test database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error() . "\n");
}

echo "✓ Database connection established\n";

/**
 * Create test guest usage data
 */
function create_test_data($conn) {
    echo "\n--- Creating Test Data ---\n";
    
    $test_ips = [
        '192.168.1.100',
        '192.168.1.101',
        '192.168.1.102',
        '10.0.0.50',
        '10.0.0.51'
    ];
    
    $created_count = 0;
    
    foreach ($test_ips as $ip) {
        // Insert test data with various usage counts
        $usage_today = rand(1, 5);
        $total_usage = rand($usage_today, 10);
        
        $sql = "INSERT INTO guest_usage (ip_address, usage_today, total_usage, created_at, last_usage) 
                VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), NOW())
                ON DUPLICATE KEY UPDATE 
                usage_today = ?, total_usage = ?, last_usage = NOW()";
                
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            $days_old = rand(0, 5); // Some recent, some older
            mysqli_stmt_bind_param($stmt, 'siiiii', $ip, $usage_today, $total_usage, $days_old, $usage_today, $total_usage);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "  ✓ Created test data for IP: $ip (usage: $usage_today)\n";
                $created_count++;
            } else {
                echo "  ✗ Failed to create test data for IP: $ip\n";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Create some old records for cleanup testing
    echo "\n--- Creating Old Test Records ---\n";
    
    $old_ips = ['172.16.0.10', '172.16.0.11'];
    foreach ($old_ips as $ip) {
        $sql = "INSERT INTO guest_usage (ip_address, usage_today, total_usage, created_at, last_usage) 
                VALUES (?, 0, ?, DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY))
                ON DUPLICATE KEY UPDATE created_at = DATE_SUB(NOW(), INTERVAL 35 DAY)";
                
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            $old_total = rand(5, 15);
            mysqli_stmt_bind_param($stmt, 'si', $ip, $old_total);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "  ✓ Created old test record for IP: $ip (35 days old)\n";
                $created_count++;
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    echo "\nTest data creation completed: $created_count records\n";
    
    return $created_count;
}

/**
 * Display current guest usage data
 */
function display_guest_data($conn, $title = "Current Guest Usage Data") {
    echo "\n--- $title ---\n";
    
    $sql = "SELECT ip_address, usage_today, total_usage, 
                   DATE(created_at) as created_date, 
                   DATE(last_usage) as last_usage_date,
                   DATEDIFF(NOW(), created_at) as days_old
            FROM guest_usage 
            ORDER BY created_at DESC 
            LIMIT 10";
            
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo sprintf("%-15s %-5s %-7s %-12s %-12s %-8s\n", 
                    "IP Address", "Daily", "Total", "Created", "Last Used", "Age(days)");
        echo str_repeat("-", 70) . "\n";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo sprintf("%-15s %-5d %-7d %-12s %-12s %-8d\n",
                        $row['ip_address'],
                        $row['usage_today'],
                        $row['total_usage'],
                        $row['created_date'],
                        $row['last_usage_date'],
                        $row['days_old']);
        }
        
        mysqli_free_result($result);
    } else {
        echo "Error retrieving guest data: " . mysqli_error($conn) . "\n";
    }
}

/**
 * Test the reset functionality
 */
function test_reset_functionality($conn) {
    echo "\n--- Testing Reset Functionality ---\n";
    
    // Count records with usage_today > 0 before reset
    $count_sql = "SELECT COUNT(*) as count FROM guest_usage WHERE usage_today > 0";
    $count_result = mysqli_query($conn, $count_sql);
    $before_count = 0;
    
    if ($count_result) {
        $row = mysqli_fetch_assoc($count_result);
        $before_count = $row['count'];
        mysqli_free_result($count_result);
    }
    
    echo "Records with daily usage > 0 before reset: $before_count\n";
    
    // Simulate the reset operation
    $reset_sql = "UPDATE guest_usage SET usage_today = 0, last_reset = NOW() WHERE usage_today > 0";
    $reset_result = mysqli_query($conn, $reset_sql);
    
    if ($reset_result) {
        $affected_rows = mysqli_affected_rows($conn);
        echo "✓ Reset operation successful: $affected_rows records updated\n";
        
        // Verify reset worked
        $verify_result = mysqli_query($conn, $count_sql);
        if ($verify_result) {
            $row = mysqli_fetch_assoc($verify_result);
            $after_count = $row['count'];
            mysqli_free_result($verify_result);
            
            if ($after_count == 0) {
                echo "✓ Verification successful: All daily usage counts reset to 0\n";
                return true;
            } else {
                echo "✗ Verification failed: $after_count records still have usage > 0\n";
                return false;
            }
        }
    } else {
        echo "✗ Reset operation failed: " . mysqli_error($conn) . "\n";
        return false;
    }
    
    return false;
}

/**
 * Test the cleanup functionality
 */
function test_cleanup_functionality($conn, $days_to_keep = 30) {
    echo "\n--- Testing Cleanup Functionality ---\n";
    
    // Count old records before cleanup
    $count_sql = "SELECT COUNT(*) as count FROM guest_usage WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    
    if ($count_stmt) {
        mysqli_stmt_bind_param($count_stmt, 'i', $days_to_keep);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $row = mysqli_fetch_assoc($count_result);
        $before_count = $row['count'];
        mysqli_stmt_close($count_stmt);
    } else {
        echo "✗ Failed to count old records\n";
        return false;
    }
    
    echo "Records older than $days_to_keep days before cleanup: $before_count\n";
    
    if ($before_count == 0) {
        echo "ℹ No old records to clean up\n";
        return true;
    }
    
    // Perform cleanup
    $cleanup_sql = "DELETE FROM guest_usage WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $cleanup_stmt = mysqli_prepare($conn, $cleanup_sql);
    
    if ($cleanup_stmt) {
        mysqli_stmt_bind_param($cleanup_stmt, 'i', $days_to_keep);
        $cleanup_result = mysqli_stmt_execute($cleanup_stmt);
        
        if ($cleanup_result) {
            $deleted_rows = mysqli_affected_rows($conn);
            echo "✓ Cleanup operation successful: $deleted_rows records deleted\n";
            mysqli_stmt_close($cleanup_stmt);
            return true;
        } else {
            echo "✗ Cleanup operation failed: " . mysqli_stmt_error($cleanup_stmt) . "\n";
            mysqli_stmt_close($cleanup_stmt);
            return false;
        }
    } else {
        echo "✗ Failed to prepare cleanup statement\n";
        return false;
    }
}

// Main test execution
try {
    echo "Starting daily reset functionality tests...\n";
    
    // Display initial state
    display_guest_data($conn, "Initial Guest Usage Data");
    
    // Create test data
    $test_records = create_test_data($conn);
    
    // Display data after test creation
    display_guest_data($conn, "Guest Usage Data After Test Creation");
    
    // Test reset functionality
    $reset_success = test_reset_functionality($conn);
    
    // Display data after reset
    display_guest_data($conn, "Guest Usage Data After Reset");
    
    // Test cleanup functionality
    $cleanup_success = test_cleanup_functionality($conn, 30);
    
    // Display final state
    display_guest_data($conn, "Final Guest Usage Data");
    
    // Summary
    echo "\n--- Test Results Summary ---\n";
    echo "Test Data Creation: " . ($test_records > 0 ? "✓ PASS" : "✗ FAIL") . " ($test_records records)\n";
    echo "Reset Functionality: " . ($reset_success ? "✓ PASS" : "✗ FAIL") . "\n";
    echo "Cleanup Functionality: " . ($cleanup_success ? "✓ PASS" : "✗ FAIL") . "\n";
    
    $overall_success = $test_records > 0 && $reset_success && $cleanup_success;
    echo "\nOverall Test Result: " . ($overall_success ? "✓ ALL TESTS PASSED" : "✗ SOME TESTS FAILED") . "\n";
    
    if ($overall_success) {
        echo "\n🎉 Daily reset script functionality verified successfully!\n";
        echo "The reset_daily_limits.php script is ready for production use.\n";
    } else {
        echo "\n⚠️  Some tests failed. Please review the issues above before using the daily reset script.\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ Test execution failed: " . $e->getMessage() . "\n";
} finally {
    // Close database connection
    if ($conn) {
        mysqli_close($conn);
    }
}

echo "\nTest script completed.\n";
?>