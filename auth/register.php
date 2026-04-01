<?php
/**
 * User Registration Page
 * Handles new account creation with validation and sanitization.
 */

require_once __DIR__ . '/auth_helper.php';
initSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . appUrl('/auth/dashboard.php'));
    exit;
}

$errors     = [];
$formData   = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];
$success    = false;
$isOffline  = isOfflineMode();

// ---------------------------------------------------------------------------
// PROCESS REGISTRATION
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    // CSRF check
    if (!verifyCsrf()) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {

        // Collect & sanitize
        $formData['first_name'] = sanitize($_POST['first_name'] ?? '');
        $formData['last_name']  = sanitize($_POST['last_name']  ?? '');
        $formData['email']      = strtolower(trim($_POST['email'] ?? ''));
        $formData['phone']      = sanitize($_POST['phone'] ?? '');
        $password               = $_POST['password']         ?? '';
        $confirmPassword        = $_POST['confirm_password'] ?? '';

        // --- Validation ---
        if (strlen($formData['first_name']) < 2) {
            $errors[] = 'First name must be at least 2 characters.';
        }
        if (strlen($formData['last_name']) < 2) {
            $errors[] = 'Last name must be at least 2 characters.';
        }
        if (!validateEmail($formData['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($formData['phone'] && !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $formData['phone'])) {
            $errors[] = 'Please enter a valid phone number.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[\d]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        // Check email uniqueness
        if (empty($errors)) {
            if ($isOffline) {
                if (offlineEmailExists($formData['email'])) {
                    $errors[] = 'An account with this email already exists.';
                }
            } else {
                $pdo  = getDBConnection();
                $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
                $stmt->execute([$formData['email']]);
                if ($stmt->fetch()) {
                    $errors[] = 'An account with this email already exists.';
                }
            }
        }

        // Create account
        if (empty($errors)) {
            if ($isOffline) {
                $newUserId = createOfflineUser(
                    $formData['first_name'],
                    $formData['last_name'],
                    $formData['email'],
                    $formData['phone'] ?: null,
                    $password,
                    2
                );
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

                $stmt = $pdo->prepare('
                    INSERT INTO users (first_name, last_name, email, phone, password_hash, role_id)
                    VALUES (:fn, :ln, :em, :ph, :pw, 2)
                ');
                $stmt->execute([
                    ':fn' => $formData['first_name'],
                    ':ln' => $formData['last_name'],
                    ':em' => $formData['email'],
                    ':ph' => $formData['phone'] ?: null,
                    ':pw' => $hash,
                ]);

                $newUserId = (int) $pdo->lastInsertId();
            }

            logActivity($newUserId, 'register', 'New user registered.');

            // Auto-login
            loginUser($formData['email'], $password);
            setFlash('success', 'Welcome to Pomegranate! Your account has been created.');
            header('Location: ' . appUrl('/auth/dashboard.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up – <?= SITE_NAME ?></title>
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
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">

                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="fw-bold text-center mb-1">Create Account</h2>
                            <p class="text-muted text-center mb-4">Join Pomegranate today</p>

                            <?php if ($isOffline): ?>
                                <div class="alert alert-info small" role="alert">
                                    Offline demo mode is active. New accounts are temporary and stored in session only.
                                </div>
                            <?php endif; ?>

                            <?php if ($errors): ?>
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($errors as $e): ?>
                                            <li><?= htmlspecialchars($e) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form id="registerForm" method="POST" action="" novalidate>
                                <?= csrfField() ?>

                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name"
                                               value="<?= htmlspecialchars($formData['first_name']) ?>" required minlength="2" autocomplete="given-name">
                                        <div class="invalid-feedback">At least 2 characters required.</div>
                                    </div>
                                    <div class="col-6">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name"
                                               value="<?= htmlspecialchars($formData['last_name']) ?>" required minlength="2" autocomplete="family-name">
                                        <div class="invalid-feedback">At least 2 characters required.</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= htmlspecialchars($formData['email']) ?>" required autocomplete="email">
                                    <div class="invalid-feedback">Please enter a valid email.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?= htmlspecialchars($formData['phone']) ?>" autocomplete="tel">
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password"
                                               required minlength="8" autocomplete="new-password">
                                        <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="password" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <!-- Password strength meter -->
                                    <div class="progress mt-2" style="height: 6px;" role="progressbar" aria-label="Password strength">
                                        <div id="pwStrengthBar" class="progress-bar" style="width: 0%;"></div>
                                    </div>
                                    <small id="pwStrengthText" class="text-muted"></small>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                               required autocomplete="new-password">
                                        <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="confirm_password" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Passwords do not match.</div>
                                </div>

                                <button type="submit" class="btn w-100 text-white fw-semibold py-2" style="background-color:#28666e;">
                                    Create Account
                                </button>
                            </form>

                            <p class="text-center text-muted mt-4 mb-0">
                                Already have an account?
                                <a href="<?= appUrl('/auth/login.php') ?>" class="fw-semibold" style="color:#28666e;">Log in</a>
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
    <script src="<?= appUrl('/js/auth.js') ?>"></script>
</body>
</html>
