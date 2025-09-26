<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Debug Test</h2>";
echo "<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  table { border-collapse: collapse; width: 100%; margin: 10px 0; }
  th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
  th { background-color: #f2f2f2; }
  .success { color: green; }
  .error { color: red; }
  .info { color: blue; }
</style>";

// Include database connection
include './includes/dbconn.inc.php';

// Test connection
if ($conn->connect_error) {
    die("<span class='error'>❌ Connection failed: " . $conn->connect_error . "</span>");
}
echo "<span class='success'>✅ Database connection successful</span><br>";

// Test if table exists
$table_check = "SHOW TABLES LIKE 'records'";
$result = mysqli_query($conn, $table_check);
if (mysqli_num_rows($result) > 0) {
    echo "<span class='success'>✅ Table 'records' exists</span><br>";
} else {
    echo "<span class='error'>❌ Table 'records' does not exist</span><br>";
}

// Show table structure
echo "<h3>Table Structure:</h3>";
$desc_query = "DESCRIBE records";
$desc_result = mysqli_query($conn, $desc_query);
if ($desc_result) {
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($desc_result)) {
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
}

// Count existing records
$count_query = "SELECT COUNT(*) as total FROM records";
$count_result = mysqli_query($conn, $count_query);
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    echo "<br><span class='info'>📊 Current record count: " . $count_row['total'] . "</span><br>";
}

// Show some sample records
echo "<h3>Sample Records:</h3>";
$sample_query = "SELECT * FROM records LIMIT 3";
$sample_result = mysqli_query($conn, $sample_query);
if ($sample_result && mysqli_num_rows($sample_result) > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Diagnosed</th><th>Encountered</th><th>Vaccinated</th></tr>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['diagnosed'] . "</td>";
        echo "<td>" . $row['encountered'] . "</td>";
        echo "<td>" . $row['vaccinated'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<span class='info'>No records found in the table</span><br>";
}

// Test a simple insert
echo "<h3>Testing Simple Insert:</h3>";
$test_sql = "INSERT INTO records (email, full_name, gender, age, temp, diagnosed, encountered, vaccinated, nationality) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$test_stmt = mysqli_prepare($conn, $test_sql);

if ($test_stmt) {
    echo "<span class='success'>✅ Prepared statement created</span><br>";
    
    $test_email = "debug_" . time() . "@test.com";
    $test_name = "Debug Test User";
    $test_gender = "Other";
    $test_age = 25;
    $test_temp = 36.5;
    $test_diagnosed = "NO";
    $test_encounter = "NO";
    $test_vaccinated = "YES";
    $test_nationality = "Test Country";
    
    echo "<span class='info'>Test data prepared:</span><br>";
    echo "Email: $test_email<br>";
    echo "Name: $test_name<br>";
    echo "Gender: $test_gender<br>";
    echo "Age: $test_age<br>";
    echo "Temperature: $test_temp<br>";
    echo "Diagnosed: $test_diagnosed<br>";
    echo "Encounter: $test_encounter<br>";
    echo "Vaccinated: $test_vaccinated<br>";
    echo "Nationality: $test_nationality<br><br>";
    
    if (mysqli_stmt_bind_param($test_stmt, "sssidssss", 
        $test_email, $test_name, $test_gender, $test_age, $test_temp, 
        $test_diagnosed, $test_encounter, $test_vaccinated, $test_nationality)) {
        echo "<span class='success'>✅ Parameters bound</span><br>";
        
        if (mysqli_stmt_execute($test_stmt)) {
            $insert_id = mysqli_insert_id($conn);
            echo "<span class='success'>✅ Test insert successful! New record ID: $insert_id</span><br>";
            
            // Verify the insert by reading it back
            $verify_query = "SELECT * FROM records WHERE id = ?";
            $verify_stmt = mysqli_prepare($conn, $verify_query);
            if ($verify_stmt) {
                mysqli_stmt_bind_param($verify_stmt, "i", $insert_id);
                mysqli_stmt_execute($verify_stmt);
                $verify_result = mysqli_stmt_get_result($verify_stmt);
                if ($verify_row = mysqli_fetch_assoc($verify_result)) {
                    echo "<span class='success'>✅ Record verification successful:</span><br>";
                    echo "<table>";
                    echo "<tr><th>Field</th><th>Value</th></tr>";
                    foreach ($verify_row as $field => $value) {
                        echo "<tr><td>$field</td><td>$value</td></tr>";
                    }
                    echo "</table>";
                }
                mysqli_stmt_close($verify_stmt);
            }
        } else {
            echo "<span class='error'>❌ Test insert failed: " . mysqli_stmt_error($test_stmt) . "</span><br>";
            echo "<span class='error'>MySQL error: " . mysqli_error($conn) . "</span><br>";
        }
    } else {
        echo "<span class='error'>❌ Parameter binding failed</span><br>";
    }
    mysqli_stmt_close($test_stmt);
} else {
    echo "<span class='error'>❌ Prepared statement creation failed: " . mysqli_error($conn) . "</span><br>";
}

// Test ENUM values specifically
echo "<h3>Testing ENUM Values:</h3>";
$enum_test_sql = "INSERT INTO records (email, full_name, gender, age, temp, diagnosed, encountered, vaccinated, nationality) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$enum_stmt = mysqli_prepare($conn, $enum_test_sql);

if ($enum_stmt) {
    $test_cases = [
        ['YES', 'YES', 'YES'],
        ['NO', 'NO', 'NO'],
        ['Yes', 'Yes', 'Yes'],  // This should fail
        ['no', 'no', 'no']      // This should fail
    ];
    
    foreach ($test_cases as $i => $test_case) {
        $test_email = "enum_test_" . ($i + 1) . "_" . time() . "@test.com";
        $test_name = "ENUM Test " . ($i + 1);
        $test_gender = "Other";
        $test_age = 30;
        $test_temp = 36.7;
        $test_nationality = "Test Country";
        
        list($test_diagnosed, $test_encounter, $test_vaccinated) = $test_case;
        
        echo "<span class='info'>Testing ENUM values: $test_diagnosed, $test_encounter, $test_vaccinated</span><br>";
        
        if (mysqli_stmt_bind_param($enum_stmt, "sssidssss", 
            $test_email, $test_name, $test_gender, $test_age, $test_temp, 
            $test_diagnosed, $test_encounter, $test_vaccinated, $test_nationality)) {
            
            if (mysqli_stmt_execute($enum_stmt)) {
                $insert_id = mysqli_insert_id($conn);
                echo "<span class='success'>✅ ENUM test successful! ID: $insert_id</span><br>";
            } else {
                echo "<span class='error'>❌ ENUM test failed: " . mysqli_stmt_error($enum_stmt) . "</span><br>";
            }
        }
        echo "<br>";
    }
    mysqli_stmt_close($enum_stmt);
}

mysqli_close($conn);

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Try submitting your form at <a href='add.php'>add.php</a></li>";
echo "<li>Check the error log at <code>logs/php_errors.log</code></li>";
echo "<li>Monitor the database for new records in the dashboard</li>";
echo "</ol>";
?>