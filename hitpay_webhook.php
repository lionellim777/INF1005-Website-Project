<?php
/**
 * HITPAY UNIFIED WEBHOOK HANDLER
 * Supports both v1.0 (Form) and v2.0 (JSON)
 */
require_once __DIR__ . '/inc/bootstrap.php';
session_write_close(); // Release lock immediately

$salt = 'JDJ5JDEwJDEwbWVOZG1OL0ZvRVREaS5GVUhCMXVGSlE4elQzMEpNWUZKNFFSb3FQcHhBS2pNMjF6eXV1'; // Ensure this is your NEW salt

// 1. Identify the Version
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isV2 = (strpos($userAgent, 'HitPay v2.0') !== false);

// 2. Capture Data and Signature
$payload = file_get_contents('php://input');
// Headers in PHP are accessed via HTTP_ prefix + Uppercase + Underscore
$sigV2 = $_SERVER['HTTP_HITPAY_SIGNATURE'] ?? ''; 
$sigV1 = $_POST['hmac'] ?? '';

// 3. Verification Logic
$isValid = false;

if ($isV2 && !empty($sigV2)) {
    // V2.0: Hash the entire raw JSON payload
    $computed = hash_hmac('sha256', $payload, $salt);
    $isValid = hash_equals($computed, $sigV2);
} elseif (!empty($sigV1)) {
    // V1.0: Hash the POST fields (excluding hmac)
    $data = $_POST;
    unset($data['hmac']);
    ksort($data);
    $source_string = http_build_query($data);
    $computed = hash_hmac('sha256', $source_string, $salt);
    $isValid = hash_equals($computed, $sigV1);
}

if (!$isValid) {
    error_log("HitPay Webhook: Signature mismatch for " . $userAgent);
    http_response_code(401);
    exit;
}

// 4. Process Data
$data = $isV2 ? json_decode($payload, true) : $_POST;
$status = $data['status'] ?? '';
$reference = $db_conn->real_escape_string($data['reference'] ?? $data['reference_number'] ?? '');

if ($status === 'completed') {
    // Logic for successful payment (e.g., $db_conn->query(...))
    error_log("Payment Verified: " . $reference);
}

http_response_code(200);