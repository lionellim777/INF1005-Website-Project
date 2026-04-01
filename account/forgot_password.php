<?php
require_once dirname(__DIR__) . '/inc/bootstrap.php';

// Load PHPMailer manually (since we aren't using Composer autoloader)
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/Exception.php';
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    
    if ($email) {
        $stmt = $db_conn->prepare("SELECT id, fname FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            // 1. Generate Secure Token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // 2. Save to Database
            $update = $db_conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->bind_param("ssi", $token, $expires, $user['id']);
            $update->execute();

            // 3. Send Email via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // E.g., smtp.gmail.com or sandbox.smtp.mailtrap.io
                $mail->SMTPAuth   = true;
                $mail->Username   = 'YOUR_EMAIL@gmail.com'; // REPLACE THIS
                $mail->Password   = 'YOUR_APP_PASSWORD';    // REPLACE THIS
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('support@pomegranate.com', 'Pomegranate Support');
                $mail->addAddress($email, $user['fname']);

                $resetLink = "https://pomeshop.duckdns.org/account/reset_password.php?token=" . $token;

                $mail->isHTML(true);
                $mail->Subject = 'Pomegranate - Password Reset Request';
                $mail->Body    = "
                    <div style='font-family: sans-serif;'>
                        <h2>Password Reset Request</h2>
                        <p>Hi {$user['fname']},</p>
                        <p>We received a request to reset your password. Click the link below to set a new one:</p>
                        <p><a href='{$resetLink}' style='background: #28666e; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
                        <p><small>This link will expire in 1 hour. If you didn't request this, safely ignore this email.</small></p>
                    </div>
                ";

                $mail->send();
                $msg = "If an account exists for that email, a reset link has been sent.";
            } catch (Exception $e) {
                error_log("Mailer Error: {$mail->ErrorInfo}");
                $err = "There was a server error sending the email. Please try again later.";
            }
        } else {
            // Prevent email enumeration by showing the same success message
            $msg = "If an account exists for that email, a reset link has been sent.";
        }
    } else {
        $err = "Please enter a valid email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Pomegranate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/user-ui.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-page">
        <div class="container">
            <div class="auth-card">
                <div class="text-center mb-4">
                    <img src="/assets/logo.png" alt="Logo" height="48" class="mb-3 rounded-3">
                    <h3 class="fw-bold" style="color: var(--brand-primary);">Forgot Password</h3>
                    <p class="text-muted">Enter your email and we'll send you a reset link.</p>
                </div>

                <?php if ($msg): ?><div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                <?php if ($err): ?><div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($err) ?></div><?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="color: var(--text-secondary);">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-dark" required placeholder="name@example.com">
                    </div>
                    <button type="submit" class="btn w-100 text-white fw-bold py-2 mb-3" style="background-color: var(--brand-primary); border-radius: 8px;">
                        Send Reset Link
                    </button>
                    <div class="text-center">
                        <a href="/account/login.php" class="text-decoration-none" style="color: var(--text-secondary); font-weight: 500;">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>