<?php
/**
 * Stripe Checkout Integration (Demo/Prototype)
 * 
 * WARNING: SSL peer verification is disabled for demo convenience.
 * Do not use in production without proper CA certificates.
 */

require_once dirname(__DIR__) . '/inc/auth_middleware.php';

// 2. Ensure user is logged in (approved)
require_login();

$amount = $_POST['amount'] ?? 0;
if (!$amount || floatval($amount) <= 0) {
    header('Location: /cart.php');
    exit;
}

$secretKey = 'sk_test_51TDiKpAM498sBT1MOxRd1giJRvlA0oKBahAvpwsQTyXU5dpfQiBPfE3PPFDHyPBmfEHwSCLUkb58UpriqWqRR0PL00ltwUPTjS'; 

$params = http_build_query([
    'payment_method_types[0]'                       => 'card',
    'line_items[0][price_data][currency]'           => 'sgd',
    'line_items[0][price_data][unit_amount]'        => round(floatval($amount) * 100),
    'line_items[0][price_data][product_data][name]' => 'Pomegranate Order',
    'line_items[0][quantity]'                       => 1,
    'mode'                                          => 'payment',
]);
$params .= '&success_url=' . urlencode('http://pomeshop.duckdns.org/payment_status.php?status=completed&reference={CHECKOUT_SESSION_ID}');
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
    // 4. Error handling – redirect to payment_status with failure
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