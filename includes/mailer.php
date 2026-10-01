<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../lib/PHPMailer.php';
require_once __DIR__ . '/../lib/SMTP.php';
require_once __DIR__ . '/../lib/Exception.php';

define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'watanschoolsystem956@gmail.com');
define('SMTP_PASS',   'wvcqmcsfolclvoul');
define('FROM_EMAIL',  'watanschoolsystem956@gmail.com');
define('FROM_NAME',   'Watan School System');

function send_mail($to, $toName, $subject, $htmlBody) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to, $toName ?: $to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return 'Mailer Error: ' . $mail->ErrorInfo;
    }
}