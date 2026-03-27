<?php

return [
    'site' => [
        'name' => 'Pomegranate',
    ],
    'mail' => [
        'host' => getenv('SUPPORT_SMTP_HOST') ?: 'smtp.example.com',
        'port' => (int) (getenv('SUPPORT_SMTP_PORT') ?: 587),
        'username' => getenv('SUPPORT_SMTP_USER') ?: 'your-smtp-username',
        'password' => getenv('SUPPORT_SMTP_PASS') ?: 'your-smtp-password',
        'encryption' => getenv('SUPPORT_SMTP_ENCRYPTION') ?: 'tls',
        'from_address' => getenv('SUPPORT_MAIL_FROM') ?: 'no-reply@example.com',
        'from_name' => getenv('SUPPORT_MAIL_FROM_NAME') ?: 'Pomegranate Support',
        'reply_to_address' => getenv('SUPPORT_REPLY_TO') ?: (getenv('SUPPORT_MAIL_FROM') ?: 'no-reply@example.com'),
        'reply_to_name' => getenv('SUPPORT_REPLY_TO_NAME') ?: 'Pomegranate Support',
    ],
];
