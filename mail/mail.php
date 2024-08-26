<?php
// handle sending emails

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// function to send email
function sendEmail($subject, $body, $client, $multipleClient)
{
    $mail = new PHPMailer(true);
    try {
        // setup the email
        session_start();
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = $_ENV['MAIL_HOST'];
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['MAIL_PORT'];
        $mail->setFrom($_ENV['MAIL_USERNAME'], 'Lorawan Plant Monitor');

        // add recipient
        if ($multipleClient) {
            $client = explode(',', $client);
            foreach ($client as $c) {
                $mail->addAddress($c, 'User');
            }
        } else {
            $mail->addAddress($client, 'User');
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // send email
        $mail->send();
    } catch (Exception $e) {
        echo "Error: {$mail->ErrorInfo}";
    }
}
