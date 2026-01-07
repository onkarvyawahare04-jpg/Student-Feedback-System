<?php
require_once 'admin/config.php';

echo "<h2>🔐 Admin Accounts Decoder</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>";

// Test database connection
if ($conn->ping()) {
    echo "<p style='color: green;'>✅ Database connection: SUCCESS</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection: FAILED</p>";
    exit();
}

    // Get all admin accounts
    $stmt = $conn->prepare("SELECT id, username, password, email, full_name, is_active, is_super_admin, last_login, created_at FROM admins ORDER BY id");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<h3>📋 All Admin Accounts in Database:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th style='padding: 10px; text-align: left;'>ID</th>";
        echo "<th style='padding: 10px; text-align: left;'>Username</th>";
        echo "<th style='padding: 10px; text-align: left;'>Password (Plain Text)</th>";
        echo "<th style='padding: 10px; text-align: left;'>Password Hash</th>";
        echo "<th style='padding: 10px; text-align: left;'>Email</th>";
        echo "<th style='padding: 10px; text-align: left;'>Full Name</th>";
        echo "<th style='padding: 10px; text-align: left;'>Status</th>";
        echo "<th style='padding: 10px; text-align: left;'>Super Admin</th>";
        echo "<th style='padding: 10px; text-align: left;'>Last Login</th>";
        echo "</tr>";

        while ($row = $result->fetch_assoc()) {
            // Test password verification to get the actual password
            $actual_password = 'Akshay@12'; // We know this is the password for all accounts
            $verify = password_verify($actual_password, $row['password']);
            
            echo "<tr>";
            echo "<td style='padding: 10px;'>{$row['id']}</td>";
            echo "<td style='padding: 10px;'><strong>{$row['username']}</strong></td>";
            echo "<td style='padding: 10px; font-family: monospace; background-color: #e8f5e8; font-weight: bold;'>" . ($verify ? $actual_password : 'UNKNOWN') . "</td>";
            echo "<td style='padding: 10px; font-family: monospace; font-size: 10px;'>{$row['password']}</td>";
            echo "<td style='padding: 10px;'>{$row['email']}</td>";
            echo "<td style='padding: 10px;'>{$row['full_name']}</td>";
            echo "<td style='padding: 10px;'>" . ($row['is_active'] ? "✅ Active" : "❌ Inactive") . "</td>";
            echo "<td style='padding: 10px;'>" . ($row['is_super_admin'] ? "✅ Yes" : "❌ No") . "</td>";
            echo "<td style='padding: 10px;'>{$row['last_login']}</td>";
            echo "</tr>";
        }
        echo "</table>";

    // Summary of login credentials
    echo "<h3>🔑 Login Credentials Summary:</h3>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #007bff;'>";
    echo "<h4 style='color: #007bff; margin-top: 0;'>📝 All Admin Login Details:</h4>";
    
    $stmt = $conn->prepare("SELECT username, email FROM admins ORDER BY id");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        echo "<div style='background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #dee2e6;'>";
        echo "<strong>👤 Username:</strong> <code style='background: #e9ecef; padding: 2px 5px;'>{$row['username']}</code><br>";
        echo "<strong>🔐 Password:</strong> <code style='background: #e9ecef; padding: 2px 5px; color: #dc3545; font-weight: bold;'>Akshay@12</code><br>";
        echo "<strong>📧 Email:</strong> {$row['email']}<br>";
        echo "<strong>🔗 Login URL:</strong> <a href='admin/login.php' target='_blank'>admin/login.php</a>";
        echo "</div>";
    }
    echo "</div>";

    // Test password verification for each account
    echo "<h3>🔍 Password Verification Test:</h3>";
    echo "<p><strong>Testing password: Akshay@12</strong></p>";
    
    $stmt = $conn->prepare("SELECT id, username, password FROM admins");
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 10px; text-align: left;'>Username</th>";
    echo "<th style='padding: 10px; text-align: left;'>Password Test</th>";
    echo "<th style='padding: 10px; text-align: left;'>Result</th>";
    echo "</tr>";

    while ($row = $result->fetch_assoc()) {
        $password = 'Akshay@12';
        $verify = password_verify($password, $row['password']);
        
        echo "<tr>";
        echo "<td style='padding: 10px;'><strong>{$row['username']}</strong></td>";
        echo "<td style='padding: 10px; font-family: monospace;'>$password</td>";
        echo "<td style='padding: 10px;'>" . ($verify ? "✅ SUCCESS" : "❌ FAILED") . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Generate new password hashes if needed
    echo "<h3>🔄 Generate New Password Hash:</h3>";
    echo "<form method='POST' style='margin: 20px 0;'>";
    echo "<label for='new_password'>New Password: </label>";
    echo "<input type='text' id='new_password' name='new_password' value='Akshay@12' style='padding: 5px; margin: 5px;'>";
    echo "<input type='submit' value='Generate Hash' style='padding: 5px 10px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer;'>";
    echo "</form>";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $verify_new = password_verify($new_password, $new_hash);
        
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Generated Hash:</h4>";
        echo "<p><strong>Password:</strong> $new_password</p>";
        echo "<p><strong>Hash:</strong> <code style='background: #e9ecef; padding: 2px 5px;'>$new_hash</code></p>";
        echo "<p><strong>Verification:</strong> " . ($verify_new ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
        echo "</div>";
    }

} else {
    echo "<p style='color: red;'>❌ No admin accounts found in database!</p>";
}

// Show database info
echo "<h3>📊 Database Information:</h3>";
echo "<p><strong>Database Name:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
echo "<p><strong>Total Admin Accounts:</strong> " . $result->num_rows . "</p>";

$conn->close();

echo "</div>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h2, h3 {
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