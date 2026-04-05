<?php
// Include PHPMailer classes from Composer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If composer hasn't been re-run successfully yet, this might fail, so we wrap in try-catch
function sendEmail($toEmail, $subject, $body) {
    // Try Composer autoloader first (Render Cloud deployment)
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    } 
    // Fallback to manual local loading (XAMPP local testing)
    elseif (file_exists(__DIR__ . '/PHPMailer/src/Exception.php')) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    } else {
        error_log("PHPMailer not found. Cannot send email.");
        return false;
    }
    
    // Safely attempt instantiation
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return false; // Prevent fatal crashes if dependencies are simply missing
    }

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thomasramesh@gmail.com';
        $mail->Password   = 'orwn ndvp rqpq fhjg'; // Use the App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 3; // Fast timeout for cloud firewalls (3s)

        // Recipients
        $mail->setFrom('thomasramesh@gmail.com', 'Vibly Support');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
