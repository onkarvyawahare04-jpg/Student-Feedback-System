<?php
require_once 'admin/config.php';

echo "<h2>🔍 Admin Password Decoder</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto;'>";

// Test database connection
if ($conn->ping()) {
    echo "<p style='color: green;'>✅ Database connection: SUCCESS</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection: FAILED</p>";
    exit();
}

// Get all admin accounts
$stmt = $conn->prepare("SELECT id, username, password, email, full_name FROM admins ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<h3>🔐 Decoding Admin Passwords:</h3>";
    
    // Common passwords to test
    $common_passwords = [
        'admin',
        'password',
        '123456',
        'admin123',
        'password123',
        'admin@123',
        'admin@mgm',
        'mgm@admin',
        'admin@2024',
        'admin@2025',
        'superadmin',
        'administrator',
        'root',
        'test',
        'demo',
        'user',
        'login',
        'welcome',
        'hello',
        '123456789',
        'qwerty',
        'abc123',
        'letmein',
        'monkey',
        'dragon',
        'master',
        'sunshine',
        'princess',
        'admin@mgm.edu',
        'mgm@admin.edu',
        'admin@feedback',
        'feedback@admin',
        'Akshay@12',
        'akshay@12',
        'Akshay123',
        'akshay123',
        'Akshay@123',
        'akshay@123',
        'Admin@12',
        'admin@12',
        'Admin123',
        'admin123',
        'Admin@123',
        'admin@123',
        'Super@12',
        'super@12',
        'Super123',
        'super123',
        'Super@123',
        'super@123'
    ];

    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background-color: #007bff; color: white;'>";
    echo "<th style='padding: 10px; text-align: left;'>ID</th>";
    echo "<th style='padding: 10px; text-align: left;'>Username</th>";
    echo "<th style='padding: 10px; text-align: left;'>Email</th>";
    echo "<th style='padding: 10px; text-align: left;'>Password Hash</th>";
    echo "<th style='padding: 10px; text-align: left;'>Decoded Password</th>";
    echo "<th style='padding: 10px; text-align: left;'>Status</th>";
    echo "</tr>";

    while ($row = $result->fetch_assoc()) {
        $found_password = 'NOT FOUND';
        $status = '❌';
        
        // Test each common password
        foreach ($common_passwords as $test_password) {
            if (password_verify($test_password, $row['password'])) {
                $found_password = $test_password;
                $status = '✅';
                break;
            }
        }
        
        echo "<tr>";
        echo "<td style='padding: 10px;'>{$row['id']}</td>";
        echo "<td style='padding: 10px;'><strong>{$row['username']}</strong></td>";
        echo "<td style='padding: 10px;'>{$row['email']}</td>";
        echo "<td style='padding: 10px; font-family: monospace; font-size: 10px;'>{$row['password']}</td>";
        echo "<td style='padding: 10px; font-family: monospace; background-color: " . ($status == '✅' ? '#e8f5e8' : '#ffe6e6') . "; font-weight: bold;'>$found_password</td>";
        echo "<td style='padding: 10px;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Summary of found passwords
    echo "<h3>📋 Login Credentials Summary:</h3>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #007bff;'>";
    
    $stmt = $conn->prepare("SELECT username, password FROM admins ORDER BY id");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $found_password = 'NOT FOUND';
        
        // Test each common password
        foreach ($common_passwords as $test_password) {
            if (password_verify($test_password, $row['password'])) {
                $found_password = $test_password;
                break;
            }
        }
        
        echo "<div style='background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #dee2e6;'>";
        echo "<strong>👤 Username:</strong> <code style='background: #e9ecef; padding: 2px 5px;'>{$row['username']}</code><br>";
        echo "<strong>🔐 Password:</strong> <code style='background: #e9ecef; padding: 2px 5px; color: #dc3545; font-weight: bold;'>$found_password</code><br>";
        echo "<strong>🔗 Login URL:</strong> <a href='admin/login.php' target='_blank'>admin/login.php</a>";
        echo "</div>";
    }
    echo "</div>";

    // Add custom password test
    echo "<h3>🔍 Test Custom Password:</h3>";
    echo "<form method='POST' style='margin: 20px 0;'>";
    echo "<label for='custom_password'>Test Password: </label>";
    echo "<input type='text' id='custom_password' name='custom_password' placeholder='Enter password to test' style='padding: 5px; margin: 5px; width: 200px;'>";
    echo "<input type='submit' value='Test Password' style='padding: 5px 10px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer;'>";
    echo "</form>";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['custom_password'])) {
        $custom_password = $_POST['custom_password'];
        echo "<h4>Testing password: '$custom_password'</h4>";
        
        $stmt = $conn->prepare("SELECT username, password FROM admins ORDER BY id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th style='padding: 10px; text-align: left;'>Username</th>";
        echo "<th style='padding: 10px; text-align: left;'>Test Result</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $verify = password_verify($custom_password, $row['password']);
            echo "<tr>";
            echo "<td style='padding: 10px;'><strong>{$row['username']}</strong></td>";
            echo "<td style='padding: 10px;'>" . ($verify ? "✅ MATCH!" : "❌ No Match") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
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