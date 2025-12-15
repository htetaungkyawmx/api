<?php
// Test if PHP is working
echo "PHP is working! Server time: " . date('Y-m-d H:i:s');

// Test database connection
$conn = new mysqli("localhost", "root", "", "fitness_tracker");
if ($conn->connect_error) {
    echo "<br>Database connection failed: " . $conn->connect_error;
} else {
    echo "<br>Database connected successfully!";
    
    // Test query
    $result = $conn->query("SHOW TABLES");
    echo "<br>Tables in database:";
    while ($row = $result->fetch_array()) {
        echo "<br>- " . $row[0];
    }
}
?>