<?php
// Test script to verify trend calculations are working
include './includes/dbconn.inc.php';

// Test the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Testing Trend Calculation Function</h2>";

// Test if created_at column exists
$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM records LIKE 'created_at'");
echo "<p>Created_at column exists: " . (mysqli_num_rows($columnCheck) > 0 ? "YES" : "NO") . "</p>";

// Test basic data retrieval
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM records WHERE DATE(created_at) = CURDATE()");
$today_count = mysqli_fetch_assoc($result)['total'];
echo "<p>Records today: $today_count</p>";

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM records WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$yesterday_count = mysqli_fetch_assoc($result)['total'];
echo "<p>Records yesterday: $yesterday_count</p>";

// Test date range queries
$result = mysqli_query($conn, "SELECT DATE(created_at) as date, COUNT(*) as count FROM records GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 5");
echo "<h3>Recent Records by Date:</h3>";
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>{$row['date']}: {$row['count']} records</li>";
}
echo "</ul>";

// Test trend calculation (simplified version)
function testTrendCalculation($conn) {
    $current = ['total' => 0, 'encountered' => 0, 'vaccinated' => 0, 'fever' => 0, 'adults' => 0, 'international' => 0];
    $previous = ['total' => 0, 'encountered' => 0, 'vaccinated' => 0, 'fever' => 0, 'adults' => 0, 'international' => 0];
    
    // Current day data
    $currentQuery = "SELECT * FROM records WHERE DATE(created_at) = CURDATE()";
    $currentResult = mysqli_query($conn, $currentQuery);
    
    if ($currentResult) {
        while ($row = mysqli_fetch_assoc($currentResult)) {
            $current['total']++;
            if ($row['encountered'] == 'YES') $current['encountered']++;
            if ($row['vaccinated'] == 'YES') $current['vaccinated']++;
            if ($row['temp'] > 37.5) $current['fever']++;
            if ($row['age'] >= 18) $current['adults']++;
            
            $nationality = strtolower(trim($row['nationality']));
            $philippineVariants = ['philippines', 'philippine', 'filipino', 'pilipino'];
            $isFilipino = false;
            foreach ($philippineVariants as $variant) {
                if (strpos($nationality, $variant) !== false) {
                    $isFilipino = true;
                    break;
                }
            }
            if (!$isFilipino) $current['international']++;
        }
        mysqli_free_result($currentResult);
    }
    
    // Previous day data
    $previousQuery = "SELECT * FROM records WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    $previousResult = mysqli_query($conn, $previousQuery);
    
    if ($previousResult) {
        while ($row = mysqli_fetch_assoc($previousResult)) {
            $previous['total']++;
            if ($row['encountered'] == 'YES') $previous['encountered']++;
            if ($row['vaccinated'] == 'YES') $previous['vaccinated']++;
            if ($row['temp'] > 37.5) $previous['fever']++;
            if ($row['age'] >= 18) $previous['adults']++;
            
            $nationality = strtolower(trim($row['nationality']));
            $philippineVariants = ['philippines', 'philippine', 'filipino', 'pilipino'];
            $isFilipino = false;
            foreach ($philippineVariants as $variant) {
                if (strpos($nationality, $variant) !== false) {
                    $isFilipino = true;
                    break;
                }
            }
            if (!$isFilipino) $previous['international']++;
        }
        mysqli_free_result($previousResult);
    }
    
    return ['current' => $current, 'previous' => $previous];
}

echo "<h3>Test Trend Calculation:</h3>";
$trendData = testTrendCalculation($conn);
echo "<pre>";
print_r($trendData);
echo "</pre>";

// Calculate sample trend percentages
$currentTotal = $trendData['current']['total'];
$previousTotal = $trendData['previous']['total'];

if ($previousTotal > 0) {
    $percentage = (($currentTotal - $previousTotal) / $previousTotal) * 100;
    $arrow = $percentage > 0 ? '▲' : ($percentage < 0 ? '▼' : '―');
    echo "<p>Total Records Trend: $arrow " . number_format($percentage, 1) . "%</p>";
} else {
    echo "<p>Total Records Trend: ― (no previous data)</p>";
}

mysqli_close($conn);
echo "<p><strong>Test completed successfully!</strong></p>";
?>