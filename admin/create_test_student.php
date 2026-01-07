<?php
require_once 'config.php';

try {
    // Enable error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    // Start transaction
    $conn->begin_transaction();

    // First, create a test class if it doesn't exist
    $class_sql = "INSERT INTO classes (class_name, department, semester) 
                  SELECT 'BE Computer', 'Computer Engineering', 8 
                  WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'BE Computer')";
    
    if (!$conn->query($class_sql)) {
        throw new Exception("Error creating class: " . $conn->error);
    }
    
    // Get the class ID
    $class_result = $conn->query("SELECT id FROM classes WHERE class_name = 'BE Computer'");
    if (!$class_result) {
        throw new Exception("Error getting class ID: " . $conn->error);
    }
    
    $class_row = $class_result->fetch_object();
    if (!$class_row) {
        throw new Exception("Could not find the class 'BE Computer'");
    }
    
    $class_id = $class_row->id;
    
    // Create test student
    $email = "test.student@mgm.edu";
    $password = password_hash("password123", PASSWORD_DEFAULT);
    $roll_number = "2024CS001";
    $full_name = "Test Student";
    
    // First check if student exists
    $check_sql = "SELECT id FROM students WHERE email = ? OR roll_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Error preparing check statement: " . $conn->error);
    }
    
    $check_stmt->bind_param("ss", $email, $roll_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing student
        $student_sql = "UPDATE students 
                       SET password = ?, 
                           full_name = ?, 
                           class_id = ?, 
                           is_active = TRUE 
                       WHERE email = ? OR roll_number = ?";
        $stmt = $conn->prepare($student_sql);
        if (!$stmt) {
            throw new Exception("Error preparing update statement: " . $conn->error);
        }
        $stmt->bind_param("ssiss", $password, $full_name, $class_id, $email, $roll_number);
    } else {
        // Insert new student
        $student_sql = "INSERT INTO students 
                       (roll_number, email, password, full_name, class_id, is_active) 
                       VALUES (?, ?, ?, ?, ?, TRUE)";
        $stmt = $conn->prepare($student_sql);
        if (!$stmt) {
            throw new Exception("Error preparing insert statement: " . $conn->error);
        }
        $stmt->bind_param("ssssi", $roll_number, $email, $password, $full_name, $class_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing student statement: " . $stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "Test student account created/updated successfully!<br>";
    echo "Email: test.student@mgm.edu<br>";
    echo "Password: password123<br>";
    echo "Roll Number: 2024CS001<br>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "Error: " . $e->getMessage();
}
?> 