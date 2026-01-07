<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'feedback_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Import Process</h2>";

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql)) {
    echo "✅ Database created/verified successfully<br>";
} else {
    echo "❌ Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db(DB_NAME);

// Read and execute SQL file
$sql_file = 'feedback_system.sql';
if (file_exists($sql_file)) {
    echo "✅ SQL file found: $sql_file<br>";
    
    $sql_content = file_get_contents($sql_file);
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql_content);
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^(--|\/\*|SET|START|COMMIT)/', $statement)) {
            if ($conn->query($statement)) {
                $success_count++;
            } else {
                $error_count++;
                echo "❌ Error executing: " . substr($statement, 0, 50) . "... - " . $conn->error . "<br>";
            }
        }
    }
    
    echo "✅ SQL import completed: $success_count successful, $error_count errors<br>";
} else {
    echo "❌ SQL file not found: $sql_file<br>";
}

// Verify admin accounts after import
echo "<h3>Verifying Admin Accounts</h3>";
$stmt = $conn->prepare("SELECT id, username, email, is_active FROM admins");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "✅ Found " . $result->num_rows . " admin accounts:<br>";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}, Active: {$row['is_active']}<br>";
        }
    } else {
        echo "❌ No admin accounts found<br>";
    }
}

$conn->close();
echo "<br>✅ Database import process completed!";
?> 