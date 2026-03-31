<?php

return [
    'site' => [
        'name' => 'Pomegranate',
    ],
    'mail' => [
        'host' => getenv('SUPPORT_SMTP_HOST') ?: 'smtp.gmail.com',
        'port' => (int) (getenv('SUPPORT_SMTP_PORT') ?: 587),
        'username' => getenv('SUPPORT_SMTP_USER') ?: 'websys829@gmail.com',
        'password' => getenv('SUPPORT_SMTP_PASS') ?: 'kjjjgmlcpgrntxwx',
        'encryption' => getenv('SUPPORT_SMTP_ENCRYPTION') ?: 'tls',
        'from_address' => getenv('SUPPORT_MAIL_FROM') ?: 'websys829@gmail.com',
        'from_name' => getenv('SUPPORT_MAIL_FROM_NAME') ?: 'Pomegranate Support',
        'reply_to_address' => getenv('SUPPORT_REPLY_TO') ?: (getenv('SUPPORT_MAIL_FROM') ?: 'websys829@gmail.com'),
        'reply_to_name' => getenv('SUPPORT_REPLY_TO_NAME') ?: 'Pomegranate Support',
        'debug' => filter_var(getenv('SUPPORT_SMTP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL),
    ],
];
