<?php
require_once 'admin/config.php';

function checkTable($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($result->num_rows == 0) {
        echo "Table '$tableName' does not exist!<br>";
        return false;
    }
    
    $result = $conn->query("DESCRIBE $tableName");
    echo "Structure of table '$tableName':<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']}) " . 
             ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
             ($row['Key'] === 'PRI' ? ' PRIMARY KEY' : '') . "<br>";
    }
    echo "<br>";
    return true;
}

// Check all required tables
$requiredTables = [
    'feedback_submissions',
    'feedback_ratings',
    'feedback_text_responses',
    'questions',
    'courses',
    'classes'
];

$allTablesExist = true;
foreach ($requiredTables as $table) {
    if (!checkTable($conn, $table)) {
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "<br>Some tables are missing. Please run the SQL script in admin/database/feedback_tables.sql";
} else {
    echo "<br>All required tables exist.";
}

// Check if questions table has the required columns
$result = $conn->query("SHOW COLUMNS FROM questions LIKE 'category'");
if ($result->num_rows == 0) {
    echo "<br><br>Warning: 'category' column is missing in questions table!";
}

$result = $conn->query("SHOW COLUMNS FROM questions LIKE 'question_type'");
if ($result->num_rows == 0) {
    echo "<br>Warning: 'question_type' column is missing in questions table!";
}

$result = $conn->query("SHOW COLUMNS FROM questions LIKE 'is_active'");
if ($result->num_rows == 0) {
    echo "<br>Warning: 'is_active' column is missing in questions table!";
}
?> 