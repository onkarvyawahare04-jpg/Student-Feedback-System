<?php
require_once 'admin/config.php';

echo "<h2>🔍 Comprehensive Password Tester</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto;'>";

// Test database connection
if ($conn->ping()) {
    echo "<p style='color: green;'>✅ Database connection: SUCCESS</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection: FAILED</p>";
    exit();
}

// Get admin accounts
$stmt = $conn->prepare("SELECT id, username, password, email FROM admins ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Extended password list
    $extended_passwords = [
        // Original passwords
        'admin', 'password', '123456', 'admin123', 'password123',
        
        // Admin variations
        'admin@123', 'admin@mgm', 'mgm@admin', 'admin@2024', 'admin@2025',
        'superadmin', 'administrator', 'root', 'test', 'demo',
        
        // Common patterns
        'user', 'login', 'welcome', 'hello', '123456789', 'qwerty',
        'abc123', 'letmein', 'monkey', 'dragon', 'master', 'sunshine',
        
        // MGM specific
        'admin@mgm.edu', 'mgm@admin.edu', 'admin@feedback', 'feedback@admin',
        'mgm', 'mgmadmin', 'adminmgm', 'mgm123', 'mgm@123',
        
        // Akshay variations
        'Akshay@12', 'akshay@12', 'Akshay123', 'akshay123', 'Akshay@123',
        'akshay@123', 'akshay', 'Akshay', 'akshay@mgm', 'Akshay@mgm',
        
        // Admin variations
        'Admin@12', 'admin@12', 'Admin123', 'admin123', 'Admin@123',
        'admin@123', 'Admin@mgm', 'admin@mgm', 'Admin@feedback',
        
        // Super variations
        'Super@12', 'super@12', 'Super123', 'super123', 'Super@123',
        'super@123', 'Super@mgm', 'super@mgm', 'Super@feedback',
        
        // Year variations
        'admin@2023', 'admin@2024', 'admin@2025', 'admin@2026',
        'mgm@2023', 'mgm@2024', 'mgm@2025', 'mgm@2026',
        
        // Simple passwords
        '123', '1234', '12345', '123456', '1234567', '12345678',
        'password', 'pass', 'pass123', 'password123',
        
        // Common admin passwords
        'admin', 'administrator', 'root', 'superuser', 'master',
        'admin1', 'admin2', 'admin3', 'admin4', 'admin5',
        
        // Feedback specific
        'feedback', 'feedback123', 'feedback@123', 'feedback@mgm',
        'feedback@admin', 'admin@feedback', 'mgm@feedback',
        
        // College specific
        'college', 'college123', 'college@123', 'college@mgm',
        'mgm@college', 'admin@college', 'college@admin',
        
        // Default passwords
        'default', 'default123', 'default@123', 'default@mgm',
        'mgm@default', 'admin@default', 'default@admin',
        
        // System passwords
        'system', 'system123', 'system@123', 'system@mgm',
        'mgm@system', 'admin@system', 'system@admin',
        
        // Test passwords
        'test', 'test123', 'test@123', 'test@mgm',
        'mgm@test', 'admin@test', 'test@admin',
        
        // Demo passwords
        'demo', 'demo123', 'demo@123', 'demo@mgm',
        'mgm@demo', 'admin@demo', 'demo@admin',
        
        // User passwords
        'user', 'user123', 'user@123', 'user@mgm',
        'mgm@user', 'admin@user', 'user@admin',
        
        // Guest passwords
        'guest', 'guest123', 'guest@123', 'guest@mgm',
        'mgm@guest', 'admin@guest', 'guest@admin',
        
        // Manager passwords
        'manager', 'manager123', 'manager@123', 'manager@mgm',
        'mgm@manager', 'admin@manager', 'manager@admin',
        
        // Director passwords
        'director', 'director123', 'director@123', 'director@mgm',
        'mgm@director', 'admin@director', 'director@admin',
        
        // Principal passwords
        'principal', 'principal123', 'principal@123', 'principal@mgm',
        'mgm@principal', 'admin@principal', 'principal@admin',
        
        // HOD passwords
        'hod', 'hod123', 'hod@123', 'hod@mgm',
        'mgm@hod', 'admin@hod', 'hod@admin',
        
        // Faculty passwords
        'faculty', 'faculty123', 'faculty@123', 'faculty@mgm',
        'mgm@faculty', 'admin@faculty', 'faculty@admin',
        
        // Staff passwords
        'staff', 'staff123', 'staff@123', 'staff@mgm',
        'mgm@staff', 'admin@staff', 'staff@admin',
        
        // Student passwords
        'student', 'student123', 'student@123', 'student@mgm',
        'mgm@student', 'admin@student', 'student@admin',
        
        // Empty password
        '', ' ', '  ',
        
        // Single characters
        'a', 'A', '1', '0', '@', '#', '$', '%', '^', '&', '*',
        
        // Common patterns
        'qwerty', 'asdfgh', 'zxcvbn', 'qwerty123', 'asdfgh123',
        'password1', 'password2', 'password3', 'password4',
        'admin1', 'admin2', 'admin3', 'admin4', 'admin5',
        
        // Date patterns
        '2023', '2024', '2025', '2026', '2023@', '2024@', '2025@',
        '01/01', '01/01/2023', '01/01/2024', '01/01/2025',
        
        // Phone patterns
        '1234567890', '9876543210', '0000000000', '1111111111',
        
        // Email patterns
        'admin@gmail.com', 'admin@yahoo.com', 'admin@hotmail.com',
        'mgm@gmail.com', 'mgm@yahoo.com', 'mgm@hotmail.com'
    ];

    echo "<h3>🔐 Testing Extended Password List:</h3>";
    echo "<p>Testing " . count($extended_passwords) . " different passwords...</p>";
    
    $found_passwords = [];
    
    // Test each password against each admin account
    foreach ($extended_passwords as $test_password) {
        $stmt = $conn->prepare("SELECT id, username, password FROM admins ORDER BY id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if (password_verify($test_password, $row['password'])) {
                $found_passwords[$row['username']] = $test_password;
                echo "<p style='color: green;'>✅ <strong>FOUND!</strong> Username: <code>{$row['username']}</code> | Password: <code style='background: yellow; padding: 2px 5px; font-weight: bold;'>$test_password</code></p>";
            }
        }
    }
    
    // Display results
    echo "<h3>📋 Final Results:</h3>";
    if (count($found_passwords) > 0) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
        echo "<h4 style='color: #155724; margin-top: 0;'>🎉 Passwords Found!</h4>";
        foreach ($found_passwords as $username => $password) {
            echo "<div style='background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb;'>";
            echo "<strong>👤 Username:</strong> <code style='background: #e9ecef; padding: 2px 5px;'>$username</code><br>";
            echo "<strong>🔐 Password:</strong> <code style='background: #fff3cd; padding: 2px 5px; color: #856404; font-weight: bold;'>$password</code><br>";
            echo "<strong>🔗 Login URL:</strong> <a href='admin/login.php' target='_blank'>admin/login.php</a>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #f5c6cb;'>";
        echo "<h4 style='color: #721c24; margin-top: 0;'>❌ No Passwords Found</h4>";
        echo "<p>None of the " . count($extended_passwords) . " tested passwords matched any admin account.</p>";
        echo "</div>";
    }

    // Custom password test form
    echo "<h3>🔍 Test Your Own Password:</h3>";
    echo "<form method='POST' style='margin: 20px 0;'>";
    echo "<label for='custom_password'>Enter Password to Test: </label>";
    echo "<input type='text' id='custom_password' name='custom_password' placeholder='Type password here...' style='padding: 8px; margin: 5px; width: 250px; font-size: 14px;'>";
    echo "<input type='submit' value='Test Password' style='padding: 8px 15px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer; font-size: 14px;'>";
    echo "</form>";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['custom_password'])) {
        $custom_password = $_POST['custom_password'];
        echo "<h4>Testing custom password: '$custom_password'</h4>";
        
        $stmt = $conn->prepare("SELECT username, password FROM admins ORDER BY id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th style='padding: 10px; text-align: left;'>Username</th>";
        echo "<th style='padding: 10px; text-align: left;'>Test Result</th>";
        echo "</tr>";
        
        $found = false;
        while ($row = $result->fetch_assoc()) {
            $verify = password_verify($custom_password, $row['password']);
            if ($verify) $found = true;
            echo "<tr>";
            echo "<td style='padding: 10px;'><strong>{$row['username']}</strong></td>";
            echo "<td style='padding: 10px;'>" . ($verify ? "✅ MATCH! Password is correct!" : "❌ No Match") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($found) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #c3e6cb;'>";
            echo "<p style='color: #155724; margin: 0;'>🎉 <strong>SUCCESS!</strong> The password '$custom_password' works for at least one admin account!</p>";
            echo "</div>";
        }
    }

} else {
    echo "<p style='color: red;'>❌ No admin accounts found in database!</p>";
}

$conn->close();
echo "</div>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h2, h3, h4 {
    color: #333;
}
table {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th {
    background-color: #007bff !important;
    color: white !important;
}
tr:nth-child(even) {
    background-color: #f8f9fa;
}
tr:hover {
    background-color: #e9ecef;
}
</style> 