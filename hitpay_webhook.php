<?php
require_once __DIR__ . '/inc/bootstrap.php';
session_write_close();

$salt = 'JDJ5JDEwJDEwbWVOZG1OL0ZvRVREaS5GVUhCMXVGSlE4elQzMEpNWUZKNFFSb3FQcHhBS2pNMjF6eXV1';

// 1. Identify version
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isV2 = strpos($userAgent, 'HitPay v2.0') !== false;

// 2. Capture raw input
$rawInput = file_get_contents('php://input');

// 3. Get signature
$sigV2 = $_SERVER['HTTP_HITPAY_SIGNATURE'] ?? '';
$sigV1 = $_POST['hmac'] ?? '';

$isValid = false;
if ($isV2 && !empty($sigV2)) {
    $computed = hash_hmac('sha256', $rawInput, $salt);
    $isValid = hash_equals($computed, $sigV2);
} elseif (!empty($sigV1)) {
    $data = $_POST;
    unset($data['hmac']);
    ksort($data);
    $sourceString = http_build_query($data);
    $computed = hash_hmac('sha256', $sourceString, $salt);
    $isValid = hash_equals($computed, $sigV1);
}

if (!$isValid) {
    error_log("HitPay Webhook: Invalid signature for $userAgent");
    http_response_code(401);
    exit;
}

// 4. Extract data
if ($isV2) {
    $payload = json_decode($rawInput, true);
    // V2: the actual data is inside 'data', event type in 'event'
    $event = $payload['event'] ?? '';
    $data = $payload['data'] ?? [];
    $status = $data['status'] ?? '';
    $reference = $data['reference'] ?? $data['reference_number'] ?? '';
    $error = $data['status_reason'] ?? $data['error_message'] ?? '';
} else {
    // V1: all POST fields are directly the data
    $data = $_POST;
    $status = $data['status'] ?? '';
    $reference = $data['reference'] ?? $data['reference_number'] ?? '';
    $error = $data['error_message'] ?? '';
    // For V1, we can also derive event from status
    $event = ($status === 'completed') ? 'payment_request.completed' : 'payment_request.failed';
}

// 5. Log the event for debugging
error_log("HitPay Webhook: event=$event, reference=$reference, status=$status, error=$error");

// 6. Update order status in your database
if ($reference) {
    $refEsc = $db_conn->real_escape_string($reference);
    if ($event === 'payment_request.completed' && $status === 'completed') {
        // Update order to 'paid'
        $db_conn->query("UPDATE orders SET payment_status='completed' WHERE reference='$refEsc'");
    } elseif ($event === 'payment_request.failed' || $status === 'failed') {
        // Update order to 'failed' and store error
        $errorEsc = $db_conn->real_escape_string($error);
        $db_conn->query("UPDATE orders SET payment_status='failed', failure_reason='$errorEsc' WHERE reference='$refEsc'");
    }
}

http_response_code(200);