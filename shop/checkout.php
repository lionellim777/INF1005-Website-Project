<?php
/**
 * Stripe Checkout Integration (Demo/Prototype)
 * 
 * WARNING: SSL peer verification is disabled for demo convenience.
 * Do not use in production without proper CA certificates.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth_middleware.php';

require_login();

$amount = $_POST['amount'] ?? 0;
if (!$amount || floatval($amount) <= 0) {
    header('Location: /cart.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: /cart.php');
    exit;
}

// -------------------------------------------------------------------------
// Create order record before redirecting to Stripe
// -------------------------------------------------------------------------
global $db_conn;
if (!$db_conn || $db_conn->connect_error) {
    header('Location: /payment_status.php?status=failed');
    exit;
}

$totalAmount = floatval($amount);
$status = 'pending';
$paymentStatus = 'pending';
$createdAt = date('Y-m-d H:i:s');

$stmt = $db_conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_status, created_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("idsss", $userId, $totalAmount, $status, $paymentStatus, $createdAt);
if (!$stmt->execute()) {
    // Order creation failed
    header('Location: /payment_status.php?status=failed');
    exit;
}
$orderId = $stmt->insert_id;
$stmt->close();

// Set reference in the format ORD-{orderId}-MOCK (following mock data)
$ref = "ORD-{$orderId}-MOCK";
$refStmt = $db_conn->prepare("UPDATE orders SET reference = ? WHERE id = ?");
$refStmt->bind_param("si", $ref, $orderId);
$refStmt->execute();
$refStmt->close();

// -------------------------------------------------------------------------
// Build Stripe Checkout session
// -------------------------------------------------------------------------
$secretKey = 'sk_test_51TDiKpAM498sBT1MOxRd1giJRvlA0oKBahAvpwsQTyXU5dpfQiBPfE3PPFDHyPBmfEHwSCLUkb58UpriqWqRR0PL00ltwUPTjS';

$params = http_build_query([
    'payment_method_types[0]'                       => 'card',
    'line_items[0][price_data][currency]'           => 'sgd',
    'line_items[0][price_data][unit_amount]'        => round($totalAmount * 100),
    'line_items[0][price_data][product_data][name]' => 'Pomegranate Order',
    'line_items[0][quantity]'                       => 1,
    'mode'                                          => 'payment',
    'client_reference_id'                           => $orderId,   // store our order ID
]);
$params .= '&success_url=' . urlencode('http://pomeshop.duckdns.org/payment_status.php?status=completed&order_id=' . $orderId);
$params .= '&cancel_url=' . urlencode('http://pomeshop.duckdns.org/payment_status.php?status=canceled');

// SSL verification disabled for demo (see explanation above)
$context = stream_context_create([
    'http' => [
        'method'        => 'POST',
        'header'        => 'Authorization: Basic ' . base64_encode($secretKey . ':') . "\r\n" .
                           'Content-Type: application/x-www-form-urlencoded',
        'content'       => $params,
        'ignore_errors' => true,
    ],
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
    ]
]);

$response = @file_get_contents('https://api.stripe.com/v1/checkout/sessions', false, $context);
if ($response === false) {
    header('Location: /payment_status.php?status=failed');
    exit;
}

$data = json_decode($response, true);
if (empty($data['url'])) {
    header('Location: /payment_status.php?status=failed');
    exit;
}

header('Location: ' . $data['url']);
exit;