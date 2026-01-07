<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Enable error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    // Create classes table first (since students table depends on it)
    $create_classes = "CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_name VARCHAR(100) NOT NULL,
        department VARCHAR(50) NOT NULL,
        semester INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($create_classes)) {
        throw new Exception("Error creating classes table: " . $conn->error);
    }
    
    // Insert the default class if it doesn't exist
    $insert_class = "INSERT INTO classes (class_name, department, semester) 
                    SELECT 'BE Computer', 'Computer Engineering', 8 
                    WHERE NOT EXISTS (
                        SELECT 1 FROM classes WHERE class_name = 'BE Computer'
                    )";
    
    if (!$conn->query($insert_class)) {
        throw new Exception("Error inserting default class: " . $conn->error);
    }
    
    // Get the class ID - now we're sure it exists
    $class_result = $conn->query("SELECT id FROM classes WHERE class_name = 'BE Computer' LIMIT 1");
    if (!$class_result) {
        throw new Exception("Error getting class ID: " . $conn->error);
    }
    
    $class_row = $class_result->fetch_object();
    if (!$class_row) {
        throw new Exception("Could not find the BE Computer class");
    }
    
    $class_id = $class_row->id;
    
    // Create students table
    $create_students = "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        roll_number VARCHAR(20) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        class_id INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )";
    
    if (!$conn->query($create_students)) {
        throw new Exception("Error creating students table: " . $conn->error);
    }
    
    // Create a test student account
    $email = "test@mgm.edu";
    $roll_number = "2024CS001";
    $password = password_hash("test123", PASSWORD_DEFAULT);
    $full_name = "Test Student";
    
    // Check if test student exists
    $check_student = $conn->prepare("SELECT id FROM students WHERE email = ? OR roll_number = ?");
    $check_student->bind_param("ss", $email, $roll_number);
    $check_student->execute();
    $student_exists = $check_student->get_result()->num_rows > 0;
    
    if ($student_exists) {
        // Update existing student
        $update_student = $conn->prepare("UPDATE students SET 
            password = ?, 
            full_name = ?, 
            class_id = ?, 
            is_active = TRUE 
            WHERE email = ? OR roll_number = ?");
        $update_student->bind_param("ssiss", $password, $full_name, $class_id, $email, $roll_number);
        if (!$update_student->execute()) {
            throw new Exception("Error updating student: " . $update_student->error);
        }
    } else {
        // Insert new student
        $insert_student = $conn->prepare("INSERT INTO students 
            (email, roll_number, password, full_name, class_id) 
            VALUES (?, ?, ?, ?, ?)");
        $insert_student->bind_param("ssssi", $email, $roll_number, $password, $full_name, $class_id);
        if (!$insert_student->execute()) {
            throw new Exception("Error inserting student: " . $insert_student->error);
        }
    }
    
    echo "Database setup completed successfully!<br>";
    echo "Test student account created:<br>";
    echo "Email: test@mgm.edu<br>";
    echo "Password: test123<br>";
    echo "Roll Number: 2024CS001<br>";
    
} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
}
?> 