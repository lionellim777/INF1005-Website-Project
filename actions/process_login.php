<?php
require_once dirname(__DIR__) . '/inc/auth_middleware.php';
require_once dirname(__DIR__) . '/inc/security_utils.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /login.php");
    exit;
}

$email = sanitize_input($_POST["email"] ?? '');
$password = $_POST["pwd"] ?? '';
$errorMsg = "";

if (empty($email) || empty($password)) {
    $errorMsg = "Email and Password are required.";
} else {
    // Securely fetch user data
    $stmt = $db_conn->prepare("SELECT id, fname, lname, password, role FROM users WHERE email = ? AND is_active = 1 ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            
            global $session;
            
            // Populate the secure Zebra Session
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['lname'] = $user['lname'];
            $_SESSION['role'] = $user['role']; // 'user', 'employee', or 'admin'
            
            // Redirect to the homepage or dashboard
            $role = strtolower($user['role']);
            if (in_array($role, ['admin', 'employee'])) {
                header("Location: /admin/index.php");
            } else {
                header("Location: /index.php"); // Fallback for customers
            }
            exit;
            exit;
        } else {
            $errorMsg = "Incorrect email or password.";
        }
        // Update last login timestamp
        $db_conn->query("UPDATE users SET last_login = NOW() WHERE id = " . (int)$user['id']);
    } else {
        $errorMsg = "Incorrect email or password."; // Do not reveal if email exists!
    }
    $stmt->close();
}

// Handle Errors
if (!empty($errorMsg)) {
    global $session;
    $session->set_flashdata('error_msg', $errorMsg);
    header("Location: /account/login.php");
    exit;
}
?>