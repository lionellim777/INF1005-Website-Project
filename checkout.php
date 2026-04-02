<?php
session_start();
$amount = $_POST['amount'] ?? 0;
if(!$amount || floatval($amount) <= 0){
    header('Location: /cart.php');
    exit;
}

$secretKey = '';

$params = http_build_query([
    'payment_method_types[0]'=> 'card',
    'line_items[0][price_data][currency]'=> 'sgd',
    'line_items[0][price_data][unit_amount]'=> round(floatval($amount) * 100),
    'line_items[0][price_data][product_data][name]' => 'Pomegranate Order',
    'line_items[0][quantity]'=> 1,
    'mode' => 'payment',
]);
$params .= '&success_url=http://pomeshop.duckdns.org/payment_status.php?status=completed%26reference={CHECKOUT_SESSION_ID}';
$params .= '&cancel_url=http://pomeshop.duckdns.org/payment_status.php?status=canceled';

$context = stream_context_create([
    'http'=>[
        'method' => 'POST',
        'header' => 'Authorization: Basic ' . base64_encode($secretKey . ':') . "\r\n" .
                     'Content-Type: application/x-www-form-urlencoded',
        'content'=> $params,
        'ignore_errors' => true,
    ],
    'ssl'=>[
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

$response = json_decode(file_get_contents('https://api.stripe.com/v1/checkout/sessions', false, $context), true);

if(!empty($response['url'])){
    header('Location: ' . $response['url']);
    exit;
}
?>