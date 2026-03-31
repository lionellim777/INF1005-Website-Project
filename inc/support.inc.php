<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/security_utils.php'; // Bring in our centralized helpers!

// PHPMailer dependencies
require_once APP_ROOT . '/vendor/PHPMailer/src/Exception.php';
require_once APP_ROOT . '/vendor/PHPMailer/src/PHPMailer.php';
require_once APP_ROOT . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

function support_inquiry_types(): array
{
    return ['Contact', 'Feedback'];
}

function sanitize_contact_payload(array $source): array
{
    // Now cleanly relying on security_utils.php
    return [
        'inquiry_type' => normalize_single_line((string) ($source['inquiry_type'] ?? '')),
        'name'         => normalize_single_line((string) ($source['name'] ?? '')),
        'email'        => strtolower(normalize_single_line((string) ($source['email'] ?? ''))),
        'subject'      => normalize_single_line((string) ($source['subject'] ?? '')),
        'message'      => normalize_multiline((string) ($source['message'] ?? '')),
    ];
}

function validate_contact_payload(array $payload): array
{
    $errors = [];

    if (!in_array($payload['inquiry_type'], support_inquiry_types(), true)) {
        $errors['inquiry_type'] = 'Please choose a valid inquiry type.';
    }

    if ($payload['name'] === '') {
        $errors['name'] = 'Please enter your name.';
    } elseif (mb_strlen($payload['name']) > 100) {
        $errors['name'] = 'Your name must be 100 characters or fewer.';
    }

    if ($payload['email'] === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (mb_strlen($payload['email']) > 150 || !is_valid_email($payload['email'])) { // Using our centralized email check
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($payload['subject'] === '') {
        $errors['subject'] = 'Please enter a subject.';
    } elseif (mb_strlen($payload['subject']) > 150) {
        $errors['subject'] = 'The subject must be 150 characters or fewer.';
    }

    if ($payload['message'] === '') {
        $errors['message'] = 'Please enter a message.';
    } elseif (mb_strlen($payload['message']) > 2000) {
        $errors['message'] = 'The message must be 2000 characters or fewer.';
    }

    return $errors;
}

function build_customer_confirmation_html(array $payload): string
{
    // Assuming h() is defined in bootstrap.php. If not, swap these with htmlspecialchars()
    $siteName = h((string) (app_config()['site']['name'] ?? 'Pomegranate'));
    $inquiryType = h($payload['inquiry_type']);
    $name = h($payload['name']);
    $subject = h($payload['subject']);
    $message = nl2br(h($payload['message']));

    return <<<HTML
        <h2>Thanks for contacting {$siteName}</h2>
        <p>Hi {$name},</p>
        <p>We have received your {$inquiryType} message successfully.</p>
        <p><strong>Name:</strong> {$name}</p>
        <p><strong>Subject:</strong> {$subject}</p>
        <p><strong>Inquiry Type:</strong> {$inquiryType}</p>
        <p><strong>Message:</strong><br>{$message}</p>
        <p>Our team will review it and get back to you if needed.</p>
        HTML;
}

function send_customer_confirmation_email(array $payload): array
{
    $mailConfig = app_config()['mail'];
    $mailer = new PHPMailer(true);

    try {
        $mailer->isSMTP();
        $mailer->CharSet = 'UTF-8';
        $mailer->Host = $mailConfig['host'];
        $mailer->Port = (int) $mailConfig['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $mailConfig['username'];
        $mailer->Password = $mailConfig['password'];

        if ($mailConfig['encryption'] === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($mailConfig['encryption'] === 'tls') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mailer->setFrom($mailConfig['from_address'], $mailConfig['from_name']);
        $mailer->addAddress($payload['email'], $payload['name']);
        $mailer->addReplyTo($mailConfig['reply_to_address'], $mailConfig['reply_to_name']);
        $mailer->isHTML(true);
        $mailer->Subject = sprintf('We received your %s message: %s', $payload['inquiry_type'], $payload['subject']);
        $mailer->Body = build_customer_confirmation_html($payload);
        $mailer->AltBody = sprintf(
            "Hi %s,\n\nWe have received your %s message successfully.\n\nSubject: %s\n\nMessage:\n%s\n\nOur team will review it and get back to you if needed.",
            $payload['name'],
            $payload['inquiry_type'],
            $payload['subject'],
            $payload['message']
        );
        $mailer->send();

        return ['sent' => true, 'error' => null];
    } catch (MailerException $exception) {
        error_log('Support email error: ' . $exception->getMessage());

        return ['sent' => false, 'error' => $exception->getMessage()];
    }
}