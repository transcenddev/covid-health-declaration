<?php
// Simple test to check if time range filters are working
include './includes/dbconn.inc.php';
include './includes/security.inc.php';

// Initialize secure session
initializeSecureSession();

if (!isValidSession()) {
    echo "Please login first: <a href='signin.php'>Login</a>";
    exit();
}

echo "<h1>Time Range Filter Test</h1>";

// Check if created_at column exists
$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM records LIKE 'created_at'");
if (mysqli_num_rows($columnCheck) == 0) {
    echo "<p><strong>Warning:</strong> The 'created_at' column doesn't exist. Time filters won't work properly.</p>";
    echo "<p>Run the database migration: <code>database/add_created_at_column.sql</code></p>";
} else {
    echo "<p><strong>Good:</strong> The 'created_at' column exists.</p>";
}

// Test different time ranges
$tests = [
    'all' => 'All Records',
    'today' => 'Today (DATE(created_at) = CURDATE())',
    '7days' => 'Last 7 days (created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY))',
    '30days' => 'Last 30 days (created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))'
];

echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>Time Range</th><th>Query</th><th>Count</th><th>Sample Dates</th></tr>";

foreach ($tests as $range => $description) {
    $condition = '';
    switch ($range) {
        case 'today':
            $condition = 'WHERE DATE(created_at) = CURDATE()';
            break;
        case '7days':
            $condition = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            break;
        case '30days':
            $condition = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            break;
        case 'all':
        default:
            $condition = '';
            break;
    }
    
    // Count records
    $countQuery = "SELECT COUNT(*) as count FROM records $condition";
    $countResult = mysqli_query($conn, $countQuery);
    $count = $countResult ? mysqli_fetch_assoc($countResult)['count'] : 'Error';
    
    // Get sample dates
    $dateQuery = "SELECT DATE(created_at) as date, COUNT(*) as count FROM records $condition GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 3";
    $dateResult = mysqli_query($conn, $dateQuery);
    $sampleDates = [];
    if ($dateResult) {
        while ($row = mysqli_fetch_assoc($dateResult)) {
            $sampleDates[] = $row['date'] . ' (' . $row['count'] . ')';
        }
    }
    
    echo "<tr>";
    echo "<td><strong>$range</strong></td>";
    echo "<td>$description</td>";
    echo "<td>$count</td>";
    echo "<td>" . implode('<br>', $sampleDates) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Show current datetime for reference
echo "<p><strong>Current Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test button
echo "<p><a href='dashboard_admin.php' style='padding: 10px 20px; background: #07c297; color: white; text-decoration: none; border-radius: 5px;'>Back to Dashboard</a></p>";
?>