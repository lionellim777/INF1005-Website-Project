<?php
// 1. Boot the secure engine
require_once __DIR__ . '/inc/bootstrap.php';

// 2. Redirect if already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $role = $_SESSION['role'] ?? 'user';
    header('Location: ' . ($role === 'admin' ? '/admin/index.php'
        : ($role === 'employee' ? '/employee/index.php' : '/index.php')));
    exit;
}

// 3. Extract Zebra Session flash messages safely
$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['error_msg']);
$success = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);
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
    <link rel="stylesheet" href="css/user-ui.css"> 
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<main class="auth-page">
    <div class="container px-4">
        <div class="auth-card text-center reveal">
            <a href="/index.php" class="d-inline-block mb-4 hover-lift">
                <img src="assets/logo.png" alt="Logo" height="48">
            </a>
            
            <h2 class="fw-bold text-white mb-2">Welcome back</h2>
            <p class="text-white-50 mb-4">Enter your credentials to access your account.</p>

            <?php if ($error): ?>
                <div class="alert-error-dark p-2 small mb-4 text-start d-flex gap-2 align-items-center">
                    <i class="bi bi-exclamation-circle"></i> <?= h($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-success-dark p-2 small mb-4 text-start d-flex gap-2 align-items-center">
                    <i class="bi bi-check-circle"></i> <?= h($success) ?>
                </div>
            <?php endif; ?>

            <form action="process_login.php" method="POST" class="text-start">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

                <div class="mb-3">
                    <label class="form-label text-white-50 small fw-semibold" style="letter-spacing:.05em;">EMAIL</label>
                    <div class="search-bar-wrap">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="email" class="form-control-dark" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label text-white-50 small fw-semibold mb-0" style="letter-spacing:.05em;">PASSWORD</label>
                        <a href="#" class="text-cyan small text-decoration-none">Forgot?</a>
                    </div>
                    <div class="search-bar-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="pwd" id="password" class="form-control-dark" placeholder="••••••••" required>
                        <button type="button" class="btn btn-link text-white-50 position-absolute end-0 top-50 translate-middle-y text-decoration-none" onclick="togglePassword()" style="padding-right:1rem;">
                            <i class="bi bi-eye" id="pwd-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-glow w-100 py-2 d-flex justify-content-center align-items-center gap-2">
                    Log In <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p class="text-white-50 small mt-4 mb-0">
                Don't have an account? <a href="/signup.php" class="text-cyan text-decoration-none fw-semibold">Create one</a>
            </p>
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
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
</body>
</html>