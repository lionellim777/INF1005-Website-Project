# INF1005 Website Project

## Contact and feedback form

The website now includes:

- A unified Contact and Feedback form on `index.php`
- Server-side validation and sanitization in PHP
- Customer confirmation emails sent through bundled PHPMailer

## Configure the app

Set these environment variables before running the site:

- `SUPPORT_SMTP_HOST`
- `SUPPORT_SMTP_PORT`
- `SUPPORT_SMTP_USER`
- `SUPPORT_SMTP_PASS`
- `SUPPORT_SMTP_ENCRYPTION`
- `SUPPORT_MAIL_FROM`
- `SUPPORT_MAIL_FROM_NAME`
- `SUPPORT_REPLY_TO`
- `SUPPORT_REPLY_TO_NAME`

If you do not set them, the project falls back to placeholder defaults in `inc/config.php`.
