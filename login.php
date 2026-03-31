<?php
require_once "inc/auth.inc.php";

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getRole();
    header('Location: ' . ($role === 'admin' ? '/admin/index.php'
        : ($role === 'employee' ? '/employee/index.php' : '/index.php')));
    exit;
}

$error = '';
$redirect = h($_GET['redirect'] ?? '/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check (simple token approach)
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            $dest = $result['role'] === 'admin'    ? '/admin/index.php'
                  : ($result['role'] === 'employee' ? '/employee/index.php'
                  : (filter_var($redirect, FILTER_VALIDATE_URL) ? $redirect : '/index.php'));
            header('Location: ' . $dest);
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<?php include "inc/nav.inc.php"; ?>

<main class="auth-page">
    <div class="container">
        <div class="auth-card">
            <!-- Header -->
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:56px;height:56px;background:linear-gradient(135deg,#22d3ee,#818cf8);">
                    <i class="bi bi-lock fs-4 text-white"></i>
                </div>
                <h2 class="fw-bold mb-1">Welcome back</h2>
                <p class="text-white-50 small">Sign in to your Pomegranate account</p>
            </div>

            <?php if ($error): ?>
            <div class="alert-error-dark p-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <span><?= h($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
            <div class="alert-success-dark p-3 mb-4 d-flex align-items-center gap-2 auto-dismiss">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                Account created! Log in below.
            </div>
            <?php endif; ?>

            <form method="POST" action="/login.php" novalidate>
                <input type="hidden" name="redirect" value="<?= h($redirect) ?>">

                <div class="mb-3">
                    <label class="form-label text-white-50 small fw-semibold">Username or Email</label>
                    <input type="text" name="username" class="form-control-dark"
                           placeholder="Enter username or email" required
                           value="<?= h($_POST['username'] ?? '') ?>"
                           autocomplete="username">
                </div>

                <div class="mb-4">
                    <label class="form-label text-white-50 small fw-semibold d-flex justify-content-between">
                        Password
                        <a href="#" class="text-white-50 text-decoration-none" style="font-size:.8rem;">Forgot password?</a>
                    </label>
                    <div class="position-relative">
                        <input type="password" name="password" id="password" class="form-control-dark"
                               placeholder="Enter password" required autocomplete="current-password">
                        <button type="button" onclick="togglePassword()"
                                class="btn-ghost border-0 position-absolute end-0 top-50 translate-middle-y me-2 p-1"
                                style="background:transparent;" aria-label="Toggle password">
                            <i class="bi bi-eye" id="pwd-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary-glow w-100" style="justify-content:center;">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>

            <p class="text-center text-white-50 small mt-4 mb-0">
                Don't have an account?
                <a href="/signup.php" class="text-white text-decoration-none fw-semibold">Create one</a>
            </p>

            <!-- Demo credentials hint -->
            <div class="mt-4 p-3 rounded-3" style="background:rgba(34,211,238,.05);border:1px solid rgba(34,211,238,.15);">
                <p class="text-white-50 small mb-2 fw-semibold" style="font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;">
                    <i class="bi bi-info-circle me-1"></i> Demo credentials
                </p>
                <div class="d-flex flex-column gap-1" style="font-size:.8rem;">
                    <span class="text-white-50"><span class="text-white fw-semibold">Admin:</span> admin / Password1!</span>
                    <span class="text-white-50"><span class="text-white fw-semibold">Employee:</span> employee1 / Password1!</span>
                    <span class="text-white-50"><span class="text-white fw-semibold">Customer:</span> johndoe / Password1!</span>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwd-eye');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
