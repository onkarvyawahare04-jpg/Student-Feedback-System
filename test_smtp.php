<?php
// Report all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
// If this file is in 'admin', adjust to '../vendor/autoload.php'

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h1>SMTP Extended Debug</h1>";

// 1. Check OpenSSL
if (!extension_loaded('openssl')) {
    echo "<p style='color:red'>CRITICAL: OpenSSL extension is NOT loaded. TLS/SSL will not work.</p>";
} else {
    echo "<p style='color:green'>OpenSSL is loaded.</p>";
}

// 2. Check DNS
$host = 'smtp.gmail.com';
$ip = gethostbyname($host);
echo "<p>DNS Lookup for $host: $ip</p>";
if ($ip == $host) {
    echo "<p style='color:red'>DNS Lookup FAILED. Check server DNS/Internet.</p>";
}

// 3. Try Connection (Port 587)
echo "<h3>Test 1: Port 587 (STARTTLS)</h3>";
$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = 2; // Verbose
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'feedbacksystem450@gmail.com';
    $mail->Password   = 'xqpdqiobrarjxohf'; // Verified App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587;
    $mail->setFrom('feedbacksystem450@gmail.com', 'Debug 587');
    $mail->addAddress('feedbacksystem450@gmail.com');
    $mail->Subject = 'Test Email - Port 587';
    $mail->Body = 'This is a test email from InfinityFree to verify SMTP authentication.';
    $mail->send();
    echo "<p style='color:green'>SUCCESS on Port 587!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>FAILED on 587: " . htmlspecialchars($mail->ErrorInfo) . "</p>";
}

// 4. Try Connection (Port 465)
echo "<h3>Test 2: Port 465 (SMTPS)</h3>";
$mail2 = new PHPMailer(true);
try {
    $mail2->SMTPDebug = 2;
    $mail2->isSMTP();
    $mail2->Host       = 'smtp.gmail.com';
    $mail2->SMTPAuth   = true;
    $mail2->Username   = 'feedbacksystem450@gmail.com';
    $mail2->Password   = 'fojrzaqckukzitiu';
    $mail2->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
    $mail2->Port       = 465;
    $mail2->setFrom('feedbacksystem450@gmail.com', 'Debug 465');
    $mail2->addAddress('feedbacksystem450@gmail.com');
    $mail2->Subject = 'Test Email - Port 465';
    $mail2->Body = 'This is a test email from InfinityFree to verify SMTP authentication.';
    $mail2->send();
    echo "<p style='color:green'>SUCCESS on Port 465!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>FAILED on 465: " . htmlspecialchars($mail2->ErrorInfo) . "</p>";
}
