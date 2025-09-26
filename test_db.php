<?php
// Simple database connection test
include './includes/dbconn.inc.php';

echo "<h1>Database Connection Test</h1>";

// Test connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>Connected successfully to database!</p>";
    
    // Test if records table exists
    $result = $conn->query("DESCRIBE records");
    if ($result) {
        echo "<h2>Records table structure:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Error describing table: " . $conn->error . "</p>";
    }
    
    // Test inserting a simple record
    echo "<h2>Testing Insert:</h2>";
    $test_sql = "INSERT INTO records (email, full_name, gender, age, temp, diagnosed, encountered, vaccinated, nationality) VALUES ('test@example.com', 'Test User', 'Male', 25, 36.5, 'NO', 'NO', 'YES', 'Test Country')";
    
    if ($conn->query($test_sql) === TRUE) {
        echo "<p style='color: green;'>Test record inserted successfully! ID: " . $conn->insert_id . "</p>";
        
        // Delete the test record
        $delete_sql = "DELETE FROM records WHERE email = 'test@example.com'";
        if ($conn->query($delete_sql) === TRUE) {
            echo "<p style='color: blue;'>Test record deleted successfully.</p>";
        }
    } else {
        echo "<p style='color: red;'>Error inserting test record: " . $conn->error . "</p>";
    }
}

$conn->close();
?>