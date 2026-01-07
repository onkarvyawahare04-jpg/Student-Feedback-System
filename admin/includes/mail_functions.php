<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendAppreciationEmail($teacherEmail, $teacherName, $subject, $content) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($teacherEmail, $teacherName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Create HTML version of the letter
        $htmlContent = '
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2>' . htmlspecialchars($subject) . '</h2>
            </div>
            <div style="margin-bottom: 20px; white-space: pre-wrap;">
                ' . nl2br(htmlspecialchars($content)) . '
            </div>
        </div>';

        $mail->Body = $htmlContent;
        $mail->AltBody = $content; // Plain text version

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
} 