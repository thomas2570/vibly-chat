<?php
// Include PHPMailer classes from Composer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If composer hasn't been re-run successfully yet, this might fail, so we wrap in try-catch
function sendEmail($toEmail, $subject, $body) {
    // Ensure Composer dependencies are loaded (this solves the Render cloud pathing issue)
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }
    
    $mail = new PHPMailer(true);

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
