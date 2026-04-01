<?php
/**
 * Login Page
 * Authenticates users with brute-force protection and secure sessions.
 */

require_once __DIR__ . '/auth_helper.php';
initSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . appUrl('/auth/dashboard.php'));
    exit;
}

$error     = '';
$emailVal  = '';
$isOffline = isOfflineMode();

// ---------------------------------------------------------------------------
// PROCESS LOGIN
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    if (!verifyCsrf()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $emailVal = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$emailVal || !$password) {
            $error = 'Please enter both email and password.';
        } else {
            $result = loginUser($emailVal, $password);
            if ($result === null) {
                // Success — redirect
                $redirect = $_SESSION['redirect_after_login'] ?? appUrl('/auth/dashboard.php');
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            }
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In – <?= SITE_NAME ?></title>
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
                            <h2 class="fw-bold text-center mb-1">Welcome Back</h2>
                            <p class="text-muted text-center mb-4">Log in to your account</p>

                            <?= renderFlash() ?>

                            <?php if ($isOffline): ?>
                                <div class="alert alert-info small" role="alert">
                                    Offline demo mode is active. Try `qa.admin@pomegranate.com` / `AdminTest@123`.
                                </div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <div id="clientLoginAlert" class="alert alert-warning d-none" role="alert"></div>

                            <form id="loginForm" method="POST" action="<?= appUrl('/auth/login.php') ?>" novalidate>
                                <?= csrfField() ?>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= htmlspecialchars($emailVal) ?>" required autocomplete="email" autofocus>
                                    <div class="invalid-feedback">Please enter a valid email.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password"
                                               required autocomplete="current-password">
                                        <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="password" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Password is required.</div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                    <a href="<?= appUrl('/auth/forgot_password.php') ?>" class="small" style="color:#28666e;">Forgot password?</a>
                                </div>

                                <button type="submit" class="btn w-100 text-white fw-semibold py-2" style="background-color:#28666e;">
                                    Log In
                                </button>
                            </form>

                            <hr class="my-4">

                            <p class="text-center text-muted mb-0">
                                Don't have an account?
                                <a href="<?= appUrl('/auth/register.php') ?>" class="fw-semibold" style="color:#28666e;">Sign up</a>
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../inc/footer.inc.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <script src="<?= appUrl('/js/auth.js?v=20260331a') ?>"></script>
</body>
</html>
