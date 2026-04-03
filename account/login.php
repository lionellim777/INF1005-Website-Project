<?php
// 1. Boot the secure engine
require_once dirname(__DIR__) . '/inc/bootstrap.php';

// 2. Redirect if already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $role = $_SESSION['role'] ?? 'user';
    header('Location: ' . ($role === 'admin' ? '/admin/index.php'
        : ($role === 'employee' ? '/admin/orders.php' : '/index.php')));
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
    <link rel="stylesheet" href="<?= app_url('css/user-ui.css') ?>"> 
    <link rel="stylesheet" href="<?= app_url('css/main.css') ?>">
    <style>
        /* Light Storefront Theme with Admin Accents */
        .auth-page {
            background: #cccccc; /* Storefront light background */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 12px 40px rgba(0,0,0,0.06);
            border-radius: 20px;
            padding: 3rem 2.5rem;
        }
        .form-control-light {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0;
            color: #333C6B !important;
            border-radius: 8px;
        }
        .form-control-light:focus {
            border-color: #335F6A !important;
            box-shadow: 0 0 0 0.2rem rgba(51, 95, 106, 0.15) !important;
            background: #ffffff !important;
        }
        .search-bar-wrap i {
            color: #94a3b8;
        }
        .btn-glow {
            background: linear-gradient(135deg, #335F6A, #333C6B);
            border: none;
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(51, 95, 106, 0.3);
            color: #fff;
        }
        .text-brand { color: #335F6A !important; }
        .text-brand:hover { color: #333C6B !important; text-decoration: underline !important; }
        .text-deep { color: #333C6B !important; }
    </style>
</head>
<body>

<main class="auth-page">
    <div class="container px-4" style="max-width: 480px;">
        <div class="auth-card text-center reveal">
            <a href="<?= app_url('index.php') ?>" class="d-inline-block mb-4 hover-lift">
                <img src="<?= app_url('assets/logo.png') ?>" alt="Logo" height="52">
            </a>
            
            <h3 class="fw-bold text-deep mb-2">Welcome back</h3>
            <p class="text-muted mb-4 small">Enter your credentials to access your account.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 p-3 small mb-4 text-start d-flex gap-2 align-items-center rounded-3" style="background: rgba(248,113,113,0.15); color: #dc2626;">
                    <i class="bi bi-exclamation-circle"></i> <?= h($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success border-0 p-3 small mb-4 text-start d-flex gap-2 align-items-center rounded-3" style="background: rgba(52,211,153,0.15); color: #059669;">
                    <i class="bi bi-check-circle"></i> <?= h($success) ?>
                </div>
            <?php endif; ?>

            <form action="<?= app_url('actions/process_login.php') ?>" method="POST" class="text-start">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

                <div class="mb-4">
                    <label for="login-email" class="form-label text-muted small fw-bold mb-2" style="letter-spacing:.08em;">EMAIL ADDRESS</label>
                    <div class="search-bar-wrap position-relative d-flex align-items-center">
                        <i class="bi bi-envelope position-absolute ms-3"></i>
                        <input type="email" name="email" id="login-email" class="form-control-light w-100 py-2 ps-5 pe-3" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="password" class="form-label text-muted small fw-bold mb-0" style="letter-spacing:.08em;">PASSWORD</label>
                        <a href="<?= app_url('account/forgot_password.php') ?>" class="text-brand small text-decoration-none fw-semibold">Forgot?</a>
                    </div>
                    <div class="search-bar-wrap position-relative d-flex align-items-center">
                        <i class="bi bi-lock position-absolute ms-3"></i>
                        <input type="password" name="pwd" id="password" class="form-control-light w-100 py-2 ps-5 pe-5" placeholder="••••••••" required>
                        <button type="button" class="btn btn-link text-muted position-absolute end-0 text-decoration-none px-3" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="pwd-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-glow w-100 py-3 d-flex justify-content-center align-items-center gap-2">
                    Log In <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p class="text-muted small mt-4 mb-0">
                Don't have an account? <a href="<?= app_url('account/signup.php') ?>" class="text-brand text-decoration-none fw-bold">Create one</a>
            </p>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= app_url('js/main.js') ?>"></script>
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
