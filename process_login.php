<?php
require_once 'inc/auth_middleware.php';
require_once 'inc/security_utils.php';

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
    $stmt = $db_conn->prepare("SELECT id, fname, lname, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            
            // SECURITY CRITICAL: Regenerate Session ID to prevent Session Fixation!
            global $session;
            $session->regenerate_id();
            
            // Populate the secure Zebra Session
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['lname'] = $user['lname'];
            $_SESSION['role'] = $user['role']; // 'user', 'employee', or 'admin'
            
            // Redirect to the homepage or dashboard
            header("Location: /index.php");
            exit;
        } else {
            $errorMsg = "Incorrect email or password.";
        }
    } else {
        $errorMsg = "Incorrect email or password."; // Do not reveal if email exists!
    }
    $stmt->close();
}

// Handle Errors
if (!empty($errorMsg)) {
    global $session;
    $session->set_flashdata('error_msg', $errorMsg);
    header("Location: /login.php");
    exit;
}
?>