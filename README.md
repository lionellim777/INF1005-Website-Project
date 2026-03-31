# INF1005 Website Project

## Contact and feedback form

The website now includes:

- A unified Contact and Feedback form on `contact.php`
- Server-side validation and sanitization in PHP
- Honeypot spam filtering and short session-based submission throttling
- Customer confirmation emails sent through bundled PHPMailer

## Configure email delivery

The form handler is already implemented. To actually send email, deploy the PHP site to your Google Cloud LAMP server and set these environment variables on that server:

- `SUPPORT_SMTP_HOST`
- `SUPPORT_SMTP_PORT`
- `SUPPORT_SMTP_USER`
- `SUPPORT_SMTP_PASS`
- `SUPPORT_SMTP_ENCRYPTION`
- `SUPPORT_MAIL_FROM`
- `SUPPORT_MAIL_FROM_NAME`
- `SUPPORT_REPLY_TO`
- `SUPPORT_REPLY_TO_NAME`
- `SUPPORT_SMTP_DEBUG` (optional, use `1` only for local troubleshooting)

Recommended values:

- Use your SMTP provider's authenticated submission settings, usually port `587` with `tls`
- Use a real sender address for `SUPPORT_MAIL_FROM`, such as `no-reply@your-domain`
- Keep `SUPPORT_REPLY_TO` as a monitored mailbox if you want staff replies to go somewhere real

If you do not set them, the project falls back to placeholder defaults in `inc/config.php`, and the form will fail to send confirmation emails until real SMTP values are configured.

Security note:

- Do not commit a real SMTP password or Gmail app password into `launch.json` or any tracked file
- If a real app password was ever saved in the repo, generate a new one in Google and replace the old one

## Deployment notes

- The server must allow outbound connections to your SMTP provider on port `587` or `465`
- You do not need to run your own mail server for this project
- After deployment, test with a real email address and check both inbox and spam

## Validation and protection notes

- CSRF protection is enforced on every submission
- Name, subject, email, and message fields are validated and sanitized server-side
- The form rejects obvious spam patterns such as links in the name or subject and too many links in the message
- A hidden honeypot field helps detect bot submissions
- A short session cooldown reduces repeated rapid submissions from the same browser session

## Report notes

You can reuse the points below when describing this feature in your project report:

- The contact and feedback page uses a single shared PHP form flow with server-side validation and sanitization before any email is sent.
- Cross-site request forgery protection is implemented with a session-based CSRF token that is generated on the server and verified on form submission.
- Input sanitization trims values, strips HTML tags, normalizes whitespace, and validates length limits before the data is processed.
- Additional anti-spam protection is implemented with a hidden honeypot field and a short session-based submission cooldown to reduce repeated automated submissions.
- The form performs both client-side and server-side checks. Browser validation gives the user immediate feedback, while PHP validation remains the authoritative security layer.
- Customer confirmation emails are sent through PHPMailer using authenticated SMTP, which allows the application to send a confirmation message after a successful submission.
- The confirmation email is intentionally generic and does not echo the full submitted message back to the user, which keeps the email simpler and reduces unnecessary exposure of submitted content.
- For development and demonstration, the form is configured to use a dedicated Gmail account. In production, the same flow can be moved to environment variables or a different SMTP provider without changing the form logic.
