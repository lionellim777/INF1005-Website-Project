<?php
/**
 * Forgot Password Page
 * Generates a password reset token (in production would send via email).
 */

require_once __DIR__ . '/auth_helper.php';
initSession();

if (isLoggedIn()) {
    header('Location: ' . appUrl('/auth/dashboard.php?tab=settings'));
    exit;
}

$submitted = false;
$error     = '';
$isOffline = isOfflineMode();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid form submission.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            if ($isOffline) {
                $user = offlineFindUserByEmail($email);
                if ($user && !empty($user['is_active'])) {
                    logActivity((int)$user['user_id'], 'password_reset_request', 'Password reset requested (offline mode).');
                }
            } else {
                $pdo  = getDBConnection();
                $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND is_active = 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Generate token
                    $token     = bin2hex(random_bytes(32));
                    $tokenHash = password_hash($token, PASSWORD_BCRYPT);

                    // Invalidate previous tokens
                    $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')
                        ->execute([$user['user_id']]);

                    // Store new token
                    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                        ->execute([$user['user_id'], $tokenHash]);

                    logActivity($user['user_id'], 'password_reset_request', 'Password reset requested.');
                }
            }

            // Always show success to prevent email enumeration
            $submitted = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password – <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= appUrl('/css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../inc/nav.inc.php'; ?>

    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-7 col-lg-5 col-xl-4">

                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4 p-md-5">
                            <?php if ($submitted): ?>
                                <div class="text-center">
                                    <i class="bi bi-envelope-check fs-1" style="color:#28666e;"></i>
                                    <h4 class="fw-bold mt-3">Check Your Email</h4>
                                    <p class="text-muted">If an account exists with that email, we've sent password reset instructions.</p>
                                    <a href="<?= appUrl('/auth/login.php') ?>" class="btn text-white mt-2" style="background-color:#28666e;">Back to Login</a>
                                </div>
                            <?php else: ?>
                                <h2 class="fw-bold text-center mb-1">Reset Password</h2>
                                <p class="text-muted text-center mb-4">Enter your email and we'll send you instructions.</p>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>

                                <form method="POST" action="" novalidate>
                                    <?= csrfField() ?>
                                    <div class="mb-4">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" required autofocus>
                                    </div>
                                    <button type="submit" class="btn w-100 text-white fw-semibold py-2" style="background-color:#28666e;">
                                        Send Reset Link
                                    </button>
                                </form>

                                <p class="text-center mt-4 mb-0">
                                    <a href="<?= appUrl('/auth/login.php') ?>" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../inc/footer.inc.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
