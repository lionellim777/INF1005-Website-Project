<?php
session_start();

$amount = $_POST['amount'] ?? 0;
if(!$amount){ 
    header('Location: /cart.php'); 
    exit; 
}

$apiKey = 'test_e5fd75a730917ee79bb659d541ac470df52d2abdef2146a122579a1d77dbcadb';

$ch = curl_init('https://api.sandbox.hit-pay.com/v1/payment-requests');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'X-BUSINESS-API-KEY: ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'amount' => number_format($amount, 2, '.', ''),
        'currency'=> 'SGD',
        'redirect_url'=> 'http://pomeshop.duckdns.org/payment_status.php',
        'webhook' => 'http://pomeshop.duckdns.org/webhook.php',
        'purpose' => 'Pomegranate Order - Test',
    ]),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = json_decode(curl_exec($ch), true);

if(!empty($response['url'])){
    header('Location: ' . $response['url']);
    exit;
}else{
    header('Location: /payment_status.php?status=failed');
    exit;
}
?>