<?php
// 1. Boot the secure engine
require_once __DIR__ . '/inc/bootstrap.php';

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
    <link rel="stylesheet" href="css/user-ui.css"> 
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<main class="auth-page">
    <div class="container px-4">
        <div class="auth-card reveal">
            <div class="text-center mb-4">
                <a href="/index.php" class="d-inline-block mb-3 hover-lift">
                    <img src="assets/logo.png" alt="Logo" height="40">
                </a>
                <h2 class="fw-bold text-white mb-1">Create an account</h2>
                <p class="text-white-50 small">Join Pomegranate and explore premium tech.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error-dark p-2 small mb-4 text-start d-flex gap-2 align-items-center">
                    <i class="bi bi-exclamation-triangle"></i> <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form action="process_register.php" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

                <div class="col-sm-6">
                    <label class="form-label text-white-50 small fw-semibold">FIRST NAME</label>
                    <input type="text" name="fname" class="form-control-dark" placeholder="John" required>
                </div>
                
                <div class="col-sm-6">
                    <label class="form-label text-white-50 small fw-semibold">LAST NAME</label>
                    <input type="text" name="lname" class="form-control-dark" placeholder="Doe" required>
                </div>

                <div class="col-12">
                    <label class="form-label text-white-50 small fw-semibold">EMAIL ADDRESS</label>
                    <div class="search-bar-wrap">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="email" class="form-control-dark" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label text-white-50 small fw-semibold">PASSWORD</label>
                    <div class="search-bar-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="pwd" id="password" class="form-control-dark" placeholder="Min. 8 characters" required>
                    </div>
                    <div class="d-flex gap-1 mt-2">
                        <div class="strength-bar flex-grow-1" style="height:4px;border-radius:2px;background:rgba(255,255,255,.1);transition:all .3s;"></div>
                        <div class="strength-bar flex-grow-1" style="height:4px;border-radius:2px;background:rgba(255,255,255,.1);transition:all .3s;"></div>
                        <div class="strength-bar flex-grow-1" style="height:4px;border-radius:2px;background:rgba(255,255,255,.1);transition:all .3s;"></div>
                        <div class="strength-bar flex-grow-1" style="height:4px;border-radius:2px;background:rgba(255,255,255,.1);transition:all .3s;"></div>
                    </div>
                    <div id="strength-label" class="text-white-50" style="font-size:.7rem;margin-top:4px;">Password strength</div>
                </div>

                <div class="col-12">
                    <label class="form-label text-white-50 small fw-semibold">CONFIRM PASSWORD</label>
                    <div class="search-bar-wrap">
                        <i class="bi bi-shield-lock"></i>
                        <input type="password" name="pwd_confirm" class="form-control-dark" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="text-white-50 small d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input type="checkbox" required> I agree to the Terms
                        </label>
                    </div>
                    <button type="submit" class="btn-glow w-100" style="justify-content:center;">
                        <i class="bi bi-person-check"></i> Create Account
                    </button>
                </div>
            </form>

            <p class="text-center text-white-50 small mt-4 mb-0">
                Already have an account?
                <a href="/login.php" class="text-white text-decoration-none fw-semibold">Sign in</a>
            </p>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
// Password strength meter
document.getElementById('password')?.addEventListener('input', function() {
    const val    = this.value;
    const bars   = document.querySelectorAll('.strength-bar');
    const label  = document.getElementById('strength-label');
    let score = 0;
    if (val.length >= 8)             score++;
    if (/[A-Z]/.test(val))           score++;
    if (/[0-9]/.test(val))           score++;
    if (/[^a-zA-Z0-9]/.test(val))    score++;

    const colors = ['#f87171','#fbbf24','#34d399','#22d3ee'];
    const labels = ['Too weak','Fair','Good','Strong'];
    bars.forEach((b, i) => {
        b.style.background = i < score ? colors[score-1] : 'rgba(255,255,255,0.1)';
    });
    label.textContent = val ? labels[score-1] : 'Password strength';
    label.style.color = val ? colors[score-1] : 'rgba(255,255,255,0.5)';
});
</script>
</body>
</html>