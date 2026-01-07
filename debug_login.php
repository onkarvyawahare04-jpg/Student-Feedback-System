<?php
session_start();
require_once 'admin/config.php';

echo "<h2>Login Debug Information</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
if ($conn->ping()) {
    echo "✅ Database connection: SUCCESS<br>";
} else {
    echo "❌ Database connection: FAILED<br>";
    echo "Error: " . $conn->error . "<br>";
}

// Test 2: Check if admins table exists
echo "<h3>2. Admins Table Check</h3>";
$result = $conn->query("SHOW TABLES LIKE 'admins'");
if ($result->num_rows > 0) {
    echo "✅ Admins table exists<br>";
} else {
    echo "❌ Admins table does not exist<br>";
}

// Test 3: Check admin accounts
echo "<h3>3. Admin Accounts Check</h3>";
$stmt = $conn->prepare("SELECT id, username, email, is_active, is_super_admin FROM admins");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "✅ Found " . $result->num_rows . " admin accounts:<br>";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}, Active: {$row['is_active']}, Super Admin: {$row['is_super_admin']}<br>";
        }
    } else {
        echo "❌ No admin accounts found<br>";
    }
} else {
    echo "❌ Error preparing statement: " . $conn->error . "<br>";
}

// Test 4: Test specific admin account
echo "<h3>4. Test Admin Account (admin)</h3>";
$stmt = $conn->prepare("SELECT id, username, password, is_active FROM admins WHERE username = 'admin'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    if ($admin) {
        echo "✅ Admin account found<br>";
        echo "- ID: {$admin['id']}<br>";
        echo "- Username: {$admin['username']}<br>";
        echo "- Active: {$admin['is_active']}<br>";
        echo "- Password hash length: " . strlen($admin['password']) . "<br>";
    } else {
        echo "❌ Admin account not found<br>";
    }
} else {
    echo "❌ Error preparing statement: " . $conn->error . "<br>";
}

// Test 5: Password verification test
echo "<h3>5. Password Verification Test</h3>";
$password = 'Akshay@12';
$hash = '$2y$10$Dc0IeqS.5u9aTOZyGkuZBO/AKDBBRasgSMYsAsFD5eg.MDTn/n3Mu';
$verify = password_verify($password, $hash);
echo "Password: $password<br>";
echo "Hash: $hash<br>";
echo "Verification result: " . ($verify ? "✅ SUCCESS" : "❌ FAILED") . "<br>";

// Test 6: Check if login form is being submitted
echo "<h3>6. Form Submission Test</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✅ Form submitted via POST<br>";
    echo "Username: " . ($_POST['username'] ?? 'NOT SET') . "<br>";
    echo "Password: " . (isset($_POST['password']) ? 'SET' : 'NOT SET') . "<br>";
} else {
    echo "ℹ️ No POST data (this is normal if you just loaded the page)<br>";
}

$conn->close();
?> 