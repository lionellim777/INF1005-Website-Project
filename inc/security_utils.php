<?php
/**
 * Pomegranate Security Utilities
 */

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_strong_password($password) {
    if (strlen($password) < 8) return false;
    return true;
}

// String normalize functions from suppoer.inc.php

function normalize_single_line(string $value): string
{
    $value = trim($value);
    $value = strip_tags($value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return trim($value);
}

function normalize_multiline(string $value): string
{
    $value = trim($value);
    $value = strip_tags($value);
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace("/[ \t]+\n/", "\n", $value) ?? $value;
    $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;

    return trim($value);
}
?>