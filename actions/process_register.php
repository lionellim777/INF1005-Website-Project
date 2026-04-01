<?php
require_once dirname(__DIR__) . '/inc/auth_middleware.php';
require_once dirname(__DIR__) . '/inc/security_utils.php';

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /signup.php");
    exit;
}

// 1. Sanitize Inputs
$fname = sanitize_input($_POST["fname"] ?? '');
$lname = sanitize_input($_POST["lname"] ?? '');
$email = sanitize_input($_POST["email"] ?? '');
$password = $_POST["password"] ?? $_POST["pwd"] ?? '';
$pwd_confirm = $_POST["pwd_confirm"] ?? '';

$errorMsg = "";

// 2. Validate Inputs
if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
    $errorMsg = "All fields are required.";
} elseif (!is_valid_email($email)) {
    $errorMsg = "Invalid email format.";
} elseif (!is_strong_password($password)) {
    $errorMsg = "Password must be at least 8 characters long.";
} elseif ($password !== $pwd_confirm) {
    $errorMsg = "Passwords do not match.";
}

// 3. Process the Database Insert if no errors
if (empty($errorMsg)) {
    // Check if email already exists
    $check_stmt = $db_conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $errorMsg = "An account with this email already exists.";
    } else {
        // Hash the password using bcrypt (Current PHP standard)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user into the database (Role defaults to 'user' automatically via SQL)
        $insert_stmt = $db_conn->prepare("INSERT INTO users (fname, lname, email, password) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("ssss", $fname, $lname, $email, $hashed_password);
        
        if ($insert_stmt->execute()) {
            // Success! Set a flash message and redirect to login
            global $session; // Access the Zebra_Session object
            $session->set_flashdata('success_msg', 'Registration successful! Please log in.');
            header("Location: /login.php");
            exit;
        } else {
            $errorMsg = "A database error occurred. Please try again later.";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// 4. Handle Errors (If validation failed, send them back to the form)
if (!empty($errorMsg)) {
    global $session;
    $session->set_flashdata('error_msg', $errorMsg);
    header("Location: /account/signup.php");
    exit;
}
?>