<?php
// Add test data with different dates for testing time range filters
include './includes/dbconn.inc.php';
include './includes/security.inc.php';

// Initialize secure session
initializeSecureSession();

if (!isValidSession()) {
    echo "Please login first: <a href='signin.php'>Login</a>";
    exit();
}

echo "<h1>Add Test Data for Time Range Testing</h1>";

// Check if created_at column exists
$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM records LIKE 'created_at'");
if (mysqli_num_rows($columnCheck) == 0) {
    echo "<p><strong>Error:</strong> The 'created_at' column doesn't exist. Run the migration first:</p>";
    echo "<pre>ALTER TABLE records ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;</pre>";
    exit();
}

// Sample data with different dates
$testData = [
    // Today's records
    ["email" => "today1@test.com", "name" => "Today User 1", "gender" => "Male", "age" => 25, "temp" => 36.5, "diagnosed" => "NO", "encountered" => "NO", "vaccinated" => "YES", "nationality" => "Philippines", "date" => "NOW()"],
    ["email" => "today2@test.com", "name" => "Today User 2", "gender" => "Female", "age" => 30, "temp" => 37.8, "diagnosed" => "NO", "encountered" => "YES", "vaccinated" => "YES", "nationality" => "Philippines", "date" => "NOW()"],
    
    // Yesterday's records
    ["email" => "yesterday1@test.com", "name" => "Yesterday User 1", "gender" => "Male", "age" => 35, "temp" => 36.2, "diagnosed" => "NO", "encountered" => "NO", "vaccinated" => "NO", "nationality" => "Philippines", "date" => "DATE_SUB(NOW(), INTERVAL 1 DAY)"],
    ["email" => "yesterday2@test.com", "name" => "Yesterday User 2", "gender" => "Female", "age" => 28, "temp" => 38.1, "diagnosed" => "NO", "encountered" => "YES", "vaccinated" => "YES", "nationality" => "Philippines", "date" => "DATE_SUB(NOW(), INTERVAL 1 DAY)"],
    
    // Last week's records
    ["email" => "week1@test.com", "name" => "Week User 1", "gender" => "Male", "age" => 40, "temp" => 36.8, "diagnosed" => "NO", "encountered" => "NO", "vaccinated" => "YES", "nationality" => "Philippines", "date" => "DATE_SUB(NOW(), INTERVAL 5 DAY)"],
    ["email" => "week2@test.com", "name" => "Week User 2", "gender" => "Female", "age" => 33, "temp" => 37.2, "diagnosed" => "NO", "encountered" => "YES", "vaccinated" => "NO", "nationality" => "Philippines", "date" => "DATE_SUB(NOW(), INTERVAL 7 DAY)"],
    
    // Last month's records
    ["email" => "month1@test.com", "name" => "Month User 1", "gender" => "Male", "age" => 45, "temp" => 36.3, "diagnosed" => "NO", "encountered" => "NO", "vaccinated" => "YES", "nationality" => "Philippines", "date" => "DATE_SUB(NOW(), INTERVAL 20 DAY)"],
    ["email" => "month2@test.com", "name" => "Month User 2", "gender" => "Female", "age" => 38, "temp" => 37.9, "diagnosed" => "NO", "encountered" => "YES", "vaccinated" => "YES", "nationality" => "Philippines", "date" => "DATE_SUB(NOW(), INTERVAL 25 DAY)"]
];

$successCount = 0;
$errorCount = 0;

echo "<h2>Adding Test Records...</h2>";
echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>Email</th><th>Name</th><th>Age</th><th>Temperature</th><th>Date Expression</th><th>Status</th></tr>";

foreach ($testData as $record) {
    // Use direct SQL with date functions (since we're adding test data)
    $sql = "INSERT INTO records (email, full_name, gender, age, temp, diagnosed, encountered, vaccinated, nationality, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$record['date']})";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssidssss", 
            $record['email'], 
            $record['name'], 
            $record['gender'], 
            $record['age'], 
            $record['temp'], 
            $record['diagnosed'], 
            $record['encountered'], 
            $record['vaccinated'], 
            $record['nationality']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $successCount++;
            $status = "<span style='color: green;'>✓ Success</span>";
        } else {
            $errorCount++;
            $status = "<span style='color: red;'>✗ Error: " . mysqli_stmt_error($stmt) . "</span>";
        }
        mysqli_stmt_close($stmt);
    } else {
        $errorCount++;
        $status = "<span style='color: red;'>✗ Prepare Error: " . mysqli_error($conn) . "</span>";
    }
    
    echo "<tr>";
    echo "<td>{$record['email']}</td>";
    echo "<td>{$record['name']}</td>";
    echo "<td>{$record['age']}</td>";
    echo "<td>{$record['temp']}°C</td>";
    echo "<td>{$record['date']}</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Summary</h2>";
echo "<p><strong>Successfully added:</strong> $successCount records</p>";
echo "<p><strong>Errors:</strong> $errorCount records</p>";

if ($successCount > 0) {
    echo "<h2>Test the Filters Now!</h2>";
    echo "<p><a href='dashboard_admin.php' style='padding: 10px 20px; background: #07c297; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Dashboard</a>";
    echo "<a href='test_time_filters.php' style='padding: 10px 20px; background: #333; color: white; text-decoration: none; border-radius: 5px;'>Check Data</a></p>";
    
    echo "<h3>Test These Filters:</h3>";
    echo "<ul>";
    echo "<li><a href='dashboard_admin.php?range=today'>Today's Records</a> (should show " . count(array_filter($testData, function($r) { return $r['date'] === 'NOW()'; })) . " records)</li>";
    echo "<li><a href='dashboard_admin.php?range=7days'>Last 7 Days</a> (should show records from today + this week)</li>";
    echo "<li><a href='dashboard_admin.php?range=30days'>Last 30 Days</a> (should show all test records)</li>";
    echo "<li><a href='dashboard_admin.php?range=all'>All Time</a> (should show all records including existing ones)</li>";
    echo "</ul>";
}

// Show current count by time range
echo "<h2>Current Data Distribution</h2>";
$ranges = [
    'Today' => "SELECT COUNT(*) as count FROM records WHERE DATE(created_at) = CURDATE()",
    'Yesterday' => "SELECT COUNT(*) as count FROM records WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
    'Last 7 Days' => "SELECT COUNT(*) as count FROM records WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    'Last 30 Days' => "SELECT COUNT(*) as count FROM records WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    'All Time' => "SELECT COUNT(*) as count FROM records"
];

echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>Time Range</th><th>Record Count</th></tr>";

foreach ($ranges as $label => $query) {
    $result = mysqli_query($conn, $query);
    $count = $result ? mysqli_fetch_assoc($result)['count'] : 'Error';
    echo "<tr><td><strong>$label</strong></td><td>$count</td></tr>";
}

echo "</table>";
?>

<style>
table { font-family: Arial, sans-serif; }
th { background: #f0f0f0; padding: 8px; }
td { padding: 8px; }
h1, h2 { color: #333; }
</style>