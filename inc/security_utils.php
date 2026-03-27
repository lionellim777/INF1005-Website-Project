<?php
/**
 * Pomegranate Security Utilities
 * Include this file whenever you need to process user input.
 */

// 1. Core Sanitization (Prevents XSS)
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    // Convert special characters to HTML entities to prevent Cross-Site Scripting (XSS)
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// 2. Email Validation
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 3. Password Strength Validation
function is_strong_password($password) {
    // Minimum 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    // You can add more rules here later (e.g., must contain numbers/uppercase)
    return true;
}
?>