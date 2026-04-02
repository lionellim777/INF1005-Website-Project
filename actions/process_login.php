<?php
require_once dirname(__DIR__) . '/inc/auth_middleware.php';
require_once dirname(__DIR__) . '/inc/security_utils.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /account/login.php");
    exit;
}

$email    = sanitize_input($_POST["email"] ?? '');
$password = $_POST["pwd"] ?? '';
$errorMsg = "";

if (empty($email) || empty($password)) {
    $errorMsg = "Email and Password are required.";
} else {
    $stmt = $db_conn->prepare("SELECT id, fname, lname, password, role FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Update last login before redirecting
            $upd = $db_conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $upd->bind_param("i", $user['id']);
            $upd->execute();

            $_SESSION['loggedin'] = true;
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['fname']    = $user['fname'];
            $_SESSION['lname']    = $user['lname'];
            $_SESSION['role']     = $user['role'];

            $role = strtolower($user['role']);
            if (in_array($role, ['admin', 'employee'])) {
                header("Location: /admin/index.php");
            } else {
                header("Location: /index.php");
            }
            exit;
        } else {
            $errorMsg = "Incorrect email or password.";
        }
    } else {
        $errorMsg = "Incorrect email or password.";
    }
    $stmt->close();
}

if (!empty($errorMsg)) {
    global $session;
    $session->set_flashdata('error_msg', $errorMsg);
    header("Location: /account/login.php");
    exit;
}
?>