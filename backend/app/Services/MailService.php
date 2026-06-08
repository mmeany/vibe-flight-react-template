<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use App\Support\SubmissionLabels;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;

class MailService {
    private const SMTP_TIMEOUT = 15;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendAutoResponse(string $email, string $knownAs, string $category): bool {
        $greeting = $knownAs !== '' ? "Hi {$knownAs}," : 'Hi,';
        $categoryLabel = SubmissionLabels::categoryLabel($category);
        $subject = 'We received your message';
        $body = "{$greeting}\n\n"
            . "Thank you for contacting us regarding: {$categoryLabel}.\n\n"
            . "We have received your message and will get back to you if a response is needed.\n\n"
            . "Regards, the team at Just for Fun";

        return $this->send($email, $subject, $body);
    }

    public function sendFollowUp(string $email, string $knownAs, string $message): bool {
        $greeting = $knownAs !== '' ? "Hi {$knownAs}," : 'Hi,';
        $subject = 'Re: Your contact request';
        $body = "{$greeting}\n\n{$message}\n\nRegards, the team at Just for Fun";

        return $this->send($email, $subject, $body);
    }

    public function sendVerificationCode(string $email, string $username, string $code): bool {
        $subject = 'Your verification code';
        $body = "Hi {$username},\n\n"
            . "Your verification code is: {$code}\n\n"
            . "This code expires in 15 minutes.\n\n"
            . "If you did not request this, you can ignore this email.\n\n"
            . 'Regards, the team at Just for Fun';

        return $this->send($email, $subject, $body);
    }

    /**
     * @param string[] $adminEmails
     */
    public function sendAdminNewUserNotification(array $adminEmails, string $username, string $email): bool {
        $subject = 'New user registration';
        $body = "A new user has completed registration:\n\n"
            . "Username: {$username}\n"
            . "Email: {$email}\n";

        $allSent = true;
        foreach ($adminEmails as $adminEmail) {
            if (!$this->send($adminEmail, $subject, $body)) {
                $allSent = false;
            }
        }

        return $allSent;
    }

    private function send(string $toEmail, string $subject, string $body): bool {
        $start = microtime(true);
        $this->logger->info('smtp.send_start', ['event' => 'smtp.send_start', 'to' => $toEmail]);

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = AppConfig::getSmtpHost();
            $mail->Port = AppConfig::getSmtpPort();
            $mail->Timeout = self::SMTP_TIMEOUT;
            $mail->SMTPAuth = AppConfig::isSmtpAuth();

            if ($mail->SMTPAuth) {
                $mail->Username = AppConfig::getSmtpUser();
                $mail->Password = AppConfig::getSmtpPass();
            }

            $secure = AppConfig::getSmtpSecure();
            if ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom(AppConfig::getSmtpFromEmail(), AppConfig::getSmtpFromName());
            $mail->addAddress($toEmail);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            $mail->send();

            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $this->logger->info('smtp.send_complete', [
                'event' => 'smtp.send_complete',
                'to' => $toEmail,
                'duration_ms' => $durationMs,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('smtp.send_failed', [
                'event' => 'smtp.send_failed',
                'to' => $toEmail,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

}
