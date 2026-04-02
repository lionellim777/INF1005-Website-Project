<?php
require_once "inc/auth.inc.php";
requireLogin($_SERVER['REQUEST_URI']);

$profile = [
    'id' => getUserId(),
    'username' => getUsername(),
    'email' => $_SESSION['email'] ?? '',
    'full_name' => getFullName(),
    'created_at' => null,
    'last_login' => null,
];

$msg = '';
$err = '';

try {
    $pdo = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireValidCsrf($_POST['csrf_token'] ?? null);
        $action = $_POST['action'] ?? '';
        $userId = (int) getUserId();

        if ($action === 'update_profile') {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));

            if ($fullName === '') {
                $err = 'Full name is required.';
            } elseif (strlen($fullName) > 100) {
                $err = 'Full name must be 100 characters or fewer.';
            } elseif (!preg_match('/^[a-zA-Z0-9 .,\-\'\"]+$/', $fullName)) {
                $err = 'Full name contains unsupported characters.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = 'Enter a valid email address.';
            } else {
                $dupStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
                $dupStmt->execute([$email, $userId]);
                if ($dupStmt->fetch()) {
                    $err = 'That email address is already in use.';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?');
                    $stmt->execute([$fullName, $email, $userId]);

                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['email'] = $email;
                    header('Location: /profile.php?msg=' . urlencode('Profile details updated successfully.'));
                    exit;
                }
            }
        }

        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $err = 'Please fill in all password fields.';
            } elseif ($newPassword !== $confirmPassword) {
                $err = 'New password and confirmation do not match.';
            } elseif (strlen($newPassword) < 8) {
                $err = 'New password must be at least 8 characters.';
            } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                $err = 'New password must include at least one uppercase letter and one number.';
            } else {
                $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $row = $stmt->fetch();

                if (!$row || !password_verify($currentPassword, (string)$row['password_hash'])) {
                    $err = 'Current password is incorrect.';
                } elseif (password_verify($newPassword, (string)$row['password_hash'])) {
                    $err = 'New password must be different from your current password.';
                } else {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $updateStmt->execute([$hash, $userId]);
                    header('Location: /profile.php?msg=' . urlencode('Password changed successfully.'));
                    exit;
                }
            }
        }
    }

    $profileStmt = $pdo->prepare('SELECT id, username, email, full_name, created_at, last_login FROM users WHERE id = ? LIMIT 1');
    $profileStmt->execute([(int)getUserId()]);
    $dbProfile = $profileStmt->fetch();

    if ($dbProfile) {
        $profile = array_merge($profile, $dbProfile);
    }
} catch (RuntimeException $e) {
    $err = $e->getMessage();
} catch (Throwable $e) {
    error_log('Profile page error: ' . $e->getMessage());
    $err = 'Profile settings are temporarily unavailable. Please try again later.';
}

$flashMsg = $_GET['msg'] ?? $msg;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<?php include "inc/nav.inc.php"; ?>

<main id="main-content" style="padding:3rem 0 5rem;min-height:calc(100vh - 160px);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label"><i class="bi bi-person-gear"></i> Account</div>
            <h1 class="section-heading">Profile <span class="text-gradient">Settings</span></h1>
            <p class="text-white-50 mb-0">Manage your account details and sign-in security.</p>
        </div>

        <?php if ($flashMsg): ?>
        <div class="alert-success-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= h($flashMsg) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($err): ?>
        <div class="alert-error-dark p-3 mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= h($err) ?></span>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <section class="contact-form-card h-100">
                    <h2 class="h5 fw-bold mb-3">Profile Details</h2>
                    <form method="POST" action="/profile.php" novalidate>
                        <?= csrfInput() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label for="full_name" class="form-label text-white-50 small fw-semibold">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control-dark"
                                   maxlength="100" required value="<?= h($profile['full_name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label text-white-50 small fw-semibold">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control-dark"
                                   maxlength="100" required value="<?= h($profile['email'] ?? '') ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white-50 small fw-semibold">Username</label>
                            <input type="text" class="form-control-dark" value="<?= h($profile['username'] ?? '') ?>" disabled aria-disabled="true">
                        </div>

                        <button type="submit" class="btn-primary-glow" style="justify-content:center;">
                            <i class="bi bi-save2"></i> Save Profile
                        </button>
                    </form>
                </section>
            </div>

            <div class="col-12 col-lg-5">
                <section class="contact-form-card mb-4">
                    <h2 class="h5 fw-bold mb-3">Change Password</h2>
                    <form method="POST" action="/profile.php" novalidate>
                        <?= csrfInput() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label for="current_password" class="form-label text-white-50 small fw-semibold">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control-dark" required autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label text-white-50 small fw-semibold">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control-dark" required minlength="8" autocomplete="new-password">
                            <p class="text-white-50 small mt-2 mb-0">Use at least 8 characters, with one uppercase letter and one number.</p>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label text-white-50 small fw-semibold">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control-dark" required minlength="8" autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn-outline-glow" style="justify-content:center;">
                            <i class="bi bi-shield-lock"></i> Update Password
                        </button>
                    </form>
                </section>

                <section class="glass-card p-4">
                    <h2 class="h6 fw-bold mb-3">Account Snapshot</h2>
                    <dl class="mb-0" style="display:grid;grid-template-columns:auto 1fr;gap:.55rem 1rem;">
                        <dt class="text-white-50">Role</dt>
                        <dd class="mb-0 text-white"><?= h(getRoleLabel()) ?></dd>

                        <dt class="text-white-50">Joined</dt>
                        <dd class="mb-0 text-white">
                            <?= !empty($profile['created_at']) ? h(date('d M Y', strtotime((string)$profile['created_at']))) : 'N/A' ?>
                        </dd>

                        <dt class="text-white-50">Last Login</dt>
                        <dd class="mb-0 text-white">
                            <?= !empty($profile['last_login']) ? h(date('d M Y H:i', strtotime((string)$profile['last_login']))) : 'N/A' ?>
                        </dd>
                    </dl>
                </section>
            </div>
        </div>
    </div>
</main>

<?php include "inc/footer.inc.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
