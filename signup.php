<?php
require_once "inc/auth.inc.php";

if (isLoggedIn()) { header('Location: ' . getRoleHomePath()); exit; }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = trim((string)($_POST['full_name'] ?? ''));
    $username  = trim($_POST['username'] ?? '');
    $email     = strtolower(trim((string)($_POST['email'] ?? '')));
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    try {
        requireValidCsrf($_POST['csrf_token'] ?? null);

        // Validate
        if (!$fullName)                            $errors[] = 'Full name is required.';
        if (strlen($fullName) > 100)            $errors[] = 'Full name must be 100 characters or fewer.';
        if (!preg_match('/^[a-zA-Z0-9 .,\-\'"]+$/', $fullName)) $errors[] = 'Full name contains unsupported characters.';
        if (!$username || strlen($username) < 3)   $errors[] = 'Username must be at least 3 characters.';
        if (strlen($username) > 50)                $errors[] = 'Username must be 50 characters or fewer.';
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = 'Username may only contain letters, numbers, and underscores.';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (strlen($password) < 8)                 $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password))     $errors[] = 'Password must contain an uppercase letter.';
        if (!preg_match('/[0-9]/', $password))     $errors[] = 'Password must contain a number.';
        if ($password !== $confirm)                $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $result = registerUser($username, $email, $password, $fullName);
            if ($result['success']) {
                header('Location: /login.php?registered=1');
                exit;
            }
            $errors[] = $result['error'];
        }
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    } catch (Throwable $e) {
        error_log('Signup error: ' . $e->getMessage());
        $errors[] = 'Signup is temporarily unavailable. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<?php include "inc/nav.inc.php"; ?>

<main id="main-content" class="auth-page" style="padding:2rem 0;">
    <div class="container">
        <div class="auth-card" style="max-width:500px;">
            <!-- Header -->
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:56px;height:56px;background:linear-gradient(135deg,#22d3ee,#818cf8);">
                    <i class="bi bi-person-plus fs-4 text-white"></i>
                </div>
                <h2 class="fw-bold mb-1">Create account</h2>
                <p class="text-white-50 small">Join Pomegranate — the future of tech shopping</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert-error-dark p-3 mb-4">
                <ul class="mb-0 ps-3" style="font-size:.85rem;">
                    <?php foreach ($errors as $err): ?>
                    <li><?= h($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="/signup.php" novalidate>
                <?= csrfInput() ?>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="signup_full_name" class="form-label text-white-50 small fw-semibold">Full Name</label>
                        <input type="text" name="full_name" id="signup_full_name" class="form-control-dark"
                               placeholder="John Doe" required
                               value="<?= h($_POST['full_name'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label for="signup_username" class="form-label text-white-50 small fw-semibold">Username</label>
                        <input type="text" name="username" id="signup_username" class="form-control-dark"
                               placeholder="johndoe123" required
                               value="<?= h($_POST['username'] ?? '') ?>"
                               autocomplete="username">
                    </div>
                    <div class="col-12">
                        <label for="signup_email" class="form-label text-white-50 small fw-semibold">Email Address</label>
                        <input type="email" name="email" id="signup_email" class="form-control-dark"
                               placeholder="you@example.com" required
                               value="<?= h($_POST['email'] ?? '') ?>"
                               autocomplete="email">
                    </div>
                    <div class="col-sm-6">
                        <label for="signup_password" class="form-label text-white-50 small fw-semibold">Password</label>
                        <input type="password" name="password" id="signup_password" class="form-control-dark"
                               placeholder="Min. 8 characters" required
                               autocomplete="new-password">
                    </div>
                    <div class="col-sm-6">
                        <label for="signup_confirm_password" class="form-label text-white-50 small fw-semibold">Confirm Password</label>
                        <input type="password" name="confirm_password" id="signup_confirm_password" class="form-control-dark"
                               placeholder="Repeat password" required
                               autocomplete="new-password">
                    </div>

                    <!-- Password strength hint -->
                    <div class="col-12">
                        <div id="pwd-strength" class="d-flex gap-1 mt-1">
                            <div class="strength-bar" style="height:3px;flex:1;border-radius:2px;background:rgba(255,255,255,.1);transition:all .3s;"></div>
                            <div class="strength-bar" style="height:3px;flex:1;border-radius:2px;background:rgba(255,255,255,.1);transition:all .3s;"></div>
                            <div class="strength-bar" style="height:3px;flex:1;border-radius:2px;background:rgba(255,255,255,.1);transition:all .3s;"></div>
                            <div class="strength-bar" style="height:3px;flex:1;border-radius:2px;background:rgba(255,255,255,.1);transition:all .3s;"></div>
                        </div>
                        <p id="strength-label" class="text-white-50 mt-1 mb-0" style="font-size:.72rem;"></p>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn-primary-glow w-100" style="justify-content:center;">
                            <i class="bi bi-person-check"></i> Create Account
                        </button>
                    </div>
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
document.getElementById('signup_password')?.addEventListener('input', function() {
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
        b.style.background = i < score ? colors[score - 1] : 'rgba(255,255,255,.1)';
    });
    label.textContent = val ? labels[score - 1] || '' : '';
    label.style.color = score > 0 ? colors[score - 1] : '';
});
</script>
</body>
</html>
