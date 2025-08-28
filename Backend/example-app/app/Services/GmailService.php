<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class GmailService
{
    protected $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // Configuración SMTP
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = env('MAIL_USERNAME');
        $this->mail->Password = env('MAIL_PASSWORD'); // Requiere contraseña de aplicación
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
    }

    /**
     * @throws Exception
     */
    public function sendEmail($to, $subject, $body)
    {
        $this->mail->setFrom('lucascabjnmro2@gmail.com', 'LucasRD');
        $this->mail->addAddress($to);
        $this->mail->Subject = $subject;
        $this->mail->Body = $body;

        return $this->mail->send();
    }
}
