<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/security_utils.php';

// PHPMailer dependencies
require_once APP_ROOT . '/vendor/PHPMailer/src/Exception.php';
require_once APP_ROOT . '/vendor/PHPMailer/src/PHPMailer.php';
require_once APP_ROOT . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function support_inquiry_types(): array
{
    return ['Contact', 'Feedback'];
}

function sanitize_contact_payload(array $source): array
{
    return [
        'inquiry_type' => normalize_single_line((string) ($source['inquiry_type'] ?? '')),
        'name' => normalize_single_line((string) ($source['name'] ?? '')),
        'email' => strtolower(normalize_single_line((string) ($source['email'] ?? ''))),
        'subject' => normalize_single_line((string) ($source['subject'] ?? '')),
        'message' => normalize_multiline((string) ($source['message'] ?? '')),
    ];
}

function support_strlen(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value);
    }

    return strlen($value);
}

function support_contains_url(string $value): bool
{
    return preg_match('~(?:https?://|www\.|[a-z0-9.-]+\.[a-z]{2,})(?:/|\b)~iu', $value) === 1;
}

function support_url_count(string $value): int
{
    return preg_match_all('~(?:https?://|www\.|[a-z0-9.-]+\.[a-z]{2,})(?:/|\b)~iu', $value, $matches) ?: 0;
}

function support_honeypot_triggered(array $source): bool
{
    return trim((string) ($source['website'] ?? '')) !== '';
}

function support_submission_cooldown_seconds(): int
{
    return 15;
}

function support_submission_is_rate_limited(): bool
{
    $lastSubmittedAt = (int) ($_SESSION['support_last_submit_at'] ?? 0);

    return $lastSubmittedAt > 0
        && (time() - $lastSubmittedAt) < support_submission_cooldown_seconds();
}

function support_submission_rate_limit_message(): string
{
    $lastSubmittedAt = (int) ($_SESSION['support_last_submit_at'] ?? 0);
    $secondsRemaining = max(1, support_submission_cooldown_seconds() - (time() - $lastSubmittedAt));

    return sprintf('Please wait %d seconds before sending another message.', $secondsRemaining);
}

function mark_support_submission_attempt(): void
{
    $_SESSION['support_last_submit_at'] = time();
}

function validate_contact_payload(array $payload): array
{
    $errors = [];

    if (!in_array($payload['inquiry_type'], support_inquiry_types(), true)) {
        $errors['inquiry_type'] = 'Please choose a valid inquiry type.';
    }

    if ($payload['name'] === '') {
        $errors['name'] = 'Please enter your name.';
    } elseif (support_strlen($payload['name']) < 2) {
        $errors['name'] = 'Your name must be at least 2 characters long.';
    } elseif (support_strlen($payload['name']) > 100) {
        $errors['name'] = 'Your name must be 100 characters or fewer.';
    } elseif (support_contains_url($payload['name'])) {
        $errors['name'] = 'Your name cannot contain a website address.';
    }

    if ($payload['email'] === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (support_strlen($payload['email']) > 150 || !is_valid_email($payload['email'])) { // Using our centralized email check
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($payload['subject'] === '') {
        $errors['subject'] = 'Please enter a subject.';
    } elseif (support_strlen($payload['subject']) < 3) {
        $errors['subject'] = 'The subject must be at least 3 characters long.';
    } elseif (support_strlen($payload['subject']) > 150) {
        $errors['subject'] = 'The subject must be 150 characters or fewer.';
    } elseif (support_contains_url($payload['subject'])) {
        $errors['subject'] = 'Please remove website links from the subject.';
    }

    if ($payload['message'] === '') {
        $errors['message'] = 'Please enter a message.';
    } elseif (support_strlen($payload['message']) < 10) {
        $errors['message'] = 'Your message must be at least 10 characters long.';
    } elseif (support_strlen($payload['message']) > 2000) {
        $errors['message'] = 'The message must be 2000 characters or fewer.';
    } elseif (support_url_count($payload['message']) > 2) {
        $errors['message'] = 'Please keep the message focused and remove extra links.';
    }

    return $errors;
}

function validate_mail_config(array $mailConfig): ?string
{
    $requiredFields = [
        'host',
        'port',
        'username',
        'password',
        'from_address',
        'from_name',
        'reply_to_address',
        'reply_to_name',
    ];

    foreach ($requiredFields as $field) {
        $value = trim((string) ($mailConfig[$field] ?? ''));

        if ($value === '') {
            return 'Mail delivery is not configured yet.';
        }
    }

    $placeholderValues = [
        'smtp.example.com',
        'your-smtp-username',
        'your-smtp-password',
        'no-reply@example.com',
    ];

    foreach (['host', 'username', 'password', 'from_address', 'reply_to_address'] as $field) {
        if (in_array((string) ($mailConfig[$field] ?? ''), $placeholderValues, true)) {
            return 'Mail delivery is still using placeholder SMTP settings.';
        }
    }

    if (!in_array((string) ($mailConfig['encryption'] ?? ''), ['', 'tls', 'ssl'], true)) {
        return 'Mail delivery encryption must be set to tls, ssl, or left empty.';
    }

    if ((int) ($mailConfig['port'] ?? 0) <= 0) {
        return 'Mail delivery port must be a valid positive number.';
    }

    return null;
}

function build_customer_confirmation_html(array $payload): string
{
    $siteName = h((string) (app_config()['site']['name'] ?? 'Pomegranate'));
    $inquiryType = h($payload['inquiry_type']);
    $name = h($payload['name']);
    $replyToAddress = h((string) (app_config()['mail']['reply_to_address'] ?? ''));

    return <<<HTML
        <div style="margin:0;padding:24px;background-color:#f4f7f9;font-family:Arial,sans-serif;color:#16313d;">
            <div style="max-width:640px;margin:0 auto;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 32px rgba(9,39,58,0.12);">
                <div style="padding:28px 32px;background:linear-gradient(135deg,#285f6b,#3f7f8b);color:#ffffff;">
                    <p style="margin:0 0 10px;font-size:12px;letter-spacing:0.16em;text-transform:uppercase;opacity:0.8;">Pomegranate Support</p>
                    <h1 style="margin:0;font-size:28px;line-height:1.25;">We received your {$inquiryType} submission</h1>
                </div>
                <div style="padding:32px;">
                    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hi {$name},</p>
                    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">This is an automated confirmation to let you know your form was submitted successfully.</p>
                    <div style="margin:24px 0;padding:18px 20px;border-radius:16px;background-color:#f1f6f8;border:1px solid rgba(40,102,110,0.14);">
                        <p style="margin:0 0 8px;font-size:14px;color:#5d7682;">Submission summary</p>
                        <p style="margin:0;font-size:18px;font-weight:700;color:#285f6b;">{$inquiryType}</p>
                    </div>
                    <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Our team will review it and get back to you if needed. Please allow 2 to 3 business days for a response.</p>
                    <p style="margin:0;font-size:15px;line-height:1.7;">If you need to follow up, reply to <a href="mailto:{$replyToAddress}" style="color:#285f6b;text-decoration:none;font-weight:700;">{$replyToAddress}</a>.</p>
                </div>
                <div style="padding:20px 32px;background-color:#f8fbfc;border-top:1px solid rgba(22,49,61,0.08);font-size:13px;color:#5d7682;">
                    Sent automatically by {$siteName}.
                </div>
            </div>
        </div>
        HTML;
}

function build_customer_confirmation_text(array $payload): string
{
    $siteName = (string) (app_config()['site']['name'] ?? 'Pomegranate');
    $replyToAddress = (string) (app_config()['mail']['reply_to_address'] ?? '');

    return sprintf(
        "Hi %s,\n\nWe have received your %s submission successfully.\n\nThis is an automated confirmation email to let you know your form was submitted.\n\nOur team will review it and get back to you if needed. Please allow 2 to 3 business days for a response.\n\nIf you need to follow up, reply to %s.\n\nRegards,\n%s",
        $payload['name'],
        $payload['inquiry_type'],
        $replyToAddress,
        $siteName
    );
}

function send_customer_confirmation_email(array $payload): array
{
    $mailConfig = app_config()['mail'];
    $mailer = new PHPMailer(true);
    $configError = validate_mail_config($mailConfig);

    if ($configError !== null) {
        error_log('Support email config error: ' . $configError);

        return ['sent' => false, 'error' => $configError];
    }

    try {
        $mailer->isSMTP();
        $mailer->CharSet = 'UTF-8';
        $mailer->Host = $mailConfig['host'];
        $mailer->Port = (int) $mailConfig['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $mailConfig['username'];
        $mailer->Password = $mailConfig['password'];

        if ($mailConfig['debug'] && PHP_SAPI === 'cli-server') {
            $mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            $mailer->Debugoutput = static function (string $message, int $level): void {
                error_log(sprintf('Support SMTP debug [%d]: %s', $level, trim($message)));
            };
        }

        if ($mailConfig['encryption'] === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($mailConfig['encryption'] === 'tls') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mailer->setFrom($mailConfig['from_address'], $mailConfig['from_name']);
        $mailer->addAddress($payload['email'], $payload['name']);
        $mailer->addReplyTo($mailConfig['reply_to_address'], $mailConfig['reply_to_name']);
        $mailer->isHTML(true);
        $mailer->Subject = sprintf('%s received your %s submission', $mailConfig['from_name'], $payload['inquiry_type']);
        $mailer->Body = build_customer_confirmation_html($payload);
        $mailer->AltBody = build_customer_confirmation_text($payload);
        $mailer->send();

        return ['sent' => true, 'error' => null];
    } catch (MailerException $exception) {
        error_log('Support email error: ' . $exception->getMessage());

        return ['sent' => false, 'error' => $exception->getMessage()];
    }
}