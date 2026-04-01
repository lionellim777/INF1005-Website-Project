<?php
// 1. Boot the secure engine
require_once dirname(__DIR__) . '/inc/bootstrap.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) { 
    header('Location: /index.php'); 
    exit; 
}

// 2. Extract Zebra Session flash messages safely
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
    <title>Sign Up – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('css/user-ui.css') ?>"> 
    <link rel="stylesheet" href="<?= app_url('css/main.css') ?>">
    <style>
        /* Light Storefront Theme with Admin Accents */
        .auth-page {
            background: #cccccc; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .auth-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 12px 40px rgba(0,0,0,0.06);
            border-radius: 20px;
            padding: 2.5rem;
        }
        .form-control-light {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0;
            color: #333C6B !important;
            border-radius: 8px;
            padding: 0.6rem 1rem;
        }
        .form-control-light:focus {
            border-color: #335F6A !important;
            box-shadow: 0 0 0 0.2rem rgba(51, 95, 106, 0.15) !important;
            background: #ffffff !important;
        }
        .search-bar-wrap i { color: #94a3b8; }
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
    <div class="container px-4" style="max-width: 600px;">
        <div class="auth-card reveal">
            <div class="text-center mb-4">
                <a href="<?= app_url('index.php') ?>" class="d-inline-block mb-3 hover-lift">
                    <img src="<?= app_url('assets/logo.png') ?>" alt="Logo" height="48">
                </a>
                <h3 class="fw-bold text-deep mb-1">Create an account</h3>
                <p class="text-muted small">Join Pomegranate and explore premium tech.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 p-3 small mb-4 text-start d-flex gap-2 align-items-center rounded-3" style="background: rgba(248,113,113,0.15); color: #dc2626;">
                    <i class="bi bi-exclamation-triangle"></i> <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= app_url('actions/process_register.php') ?>" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

                <div class="col-sm-6">
                    <label class="form-label text-muted small fw-bold" style="letter-spacing:.05em;">FIRST NAME</label>
                    <input type="text" name="fname" class="form-control-light w-100" placeholder="John" required>
                </div>
                
                <div class="col-sm-6">
                    <label class="form-label text-muted small fw-bold" style="letter-spacing:.05em;">LAST NAME</label>
                    <input type="text" name="lname" class="form-control-light w-100" placeholder="Doe" required>
                </div>

                <div class="col-12 mt-3">
                    <label class="form-label text-muted small fw-bold mb-2" style="letter-spacing:.05em;">EMAIL ADDRESS</label>
                    <div class="search-bar-wrap position-relative d-flex align-items-center">
                        <i class="bi bi-envelope position-absolute ms-3"></i>
                        <input type="email" name="email" class="form-control-light w-100 ps-5" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <label class="form-label text-muted small fw-bold mb-2" style="letter-spacing:.05em;">PASSWORD</label>
                    <div class="search-bar-wrap position-relative d-flex align-items-center">
                        <i class="bi bi-lock position-absolute ms-3"></i>
                        <input type="password" name="pwd" id="password" class="form-control-light w-100 ps-5" placeholder="Min. 8 characters" required>
                    </div>
                    <div class="d-flex gap-1 mt-2">
                        <div class="strength-bar flex-grow-1" style="height:4px;border-radius:2px;background:#e2e8f0;transition:all .3s;"></div>
                        <div class="strength-bar flex-grow-1" style="height:4px;border-radius:2px;background:#e2e8f0;transition:all .3s;"></div>
                        <div class="strength-bar flex-grow-1" style="height:4px;border-radius:2px;background:#e2e8f0;transition:all .3s;"></div>
                        <div class="strength-bar flex-grow-1" style="height:4px;border-radius:2px;background:#e2e8f0;transition:all .3s;"></div>
                    </div>
                    <div id="strength-label" class="text-muted" style="font-size:.75rem;margin-top:6px;">Password strength</div>
                </div>

                <div class="col-12 mt-3">
                    <label class="form-label text-muted small fw-bold mb-2" style="letter-spacing:.05em;">CONFIRM PASSWORD</label>
                    <div class="search-bar-wrap position-relative d-flex align-items-center">
                        <i class="bi bi-shield-lock position-absolute ms-3"></i>
                        <input type="password" name="pwd_confirm" class="form-control-light w-100 ps-5" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="text-muted small d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input type="checkbox" required> I agree to the Terms of Service
                        </label>
                    </div>
                    <button type="submit" class="btn-glow w-100 py-3" style="justify-content:center;">
                        <i class="bi bi-person-check me-2"></i> Create Account
                    </button>
                </div>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Already have an account?
                <a href="<?= app_url('account/login.php') ?>" class="text-brand text-decoration-none fw-bold">Sign in</a>
            </p>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= app_url('js/main.js') ?>"></script>
<script>
// Password strength meter updated for light theme
document.getElementById('password')?.addEventListener('input', function() {
    const val    = this.value;
    const bars   = document.querySelectorAll('.strength-bar');
    const label  = document.getElementById('strength-label');
    let score = 0;
    if (val.length >= 8)             score++;
    if (/[A-Z]/.test(val))           score++;
    if (/[0-9]/.test(val))           score++;
    if (/[^a-zA-Z0-9]/.test(val))    score++;

    // Red, Amber, Green, Teal
    const colors = ['#ef4444','#f59e0b','#10b981','#335F6A']; 
    const labels = ['Too weak','Fair','Good','Strong'];
    
    bars.forEach((b, i) => {
        b.style.background = i < score ? colors[score-1] : '#e2e8f0';
    });
    label.textContent = val ? labels[score-1] : 'Password strength';
    label.style.color = val ? colors[score-1] : '#64748b';
});
</script>
</body>
</html>