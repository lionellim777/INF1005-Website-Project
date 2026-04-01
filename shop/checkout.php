<?php
require_once dirname(__DIR__). '/inc/bootstrap.php';

$amount = $_POST['amount'] ?? 0;
if(!$amount){ 
    header('Location: /cart.php'); 
    exit; 
}

$apiKey = 'test_13ab37b565272052e323eae670a59e297af4d24e87770540d7bcab4d0364cb14';

// ... top half of checkout.php stays the same

$ch = curl_init('https://api.sandbox.hit-pay.com/v1/payment-requests');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'X-BUSINESS-API-KEY: ' . $apiKey,
        'Content-Type: application/json',
        // Removed X-Requested-With just in case it triggers a server-side block
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'amount' => number_format($amount, 2, '.', ''),
        'currency'=> 'SGD',
        'redirect_url'=> 'https://pomeshop.duckdns.org/payment_status.php',
        'purpose' => 'Pomegranate Tech Order',
        'reference_number' => 'ORD-' . time(), 
        // We completely removed 'payment_methods' so it uses your account defaults
    ]),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

// Capture the raw response and the HTTP status code
$raw_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$response = json_decode($raw_response, true);

if (!empty($response['url'])) {
    // If it works, go to HitPay
    header('Location: ' . $response['url']);
    exit;
} else {
    // THE DEBUGGER: If it fails, print exactly what HitPay is complaining about
    echo "<div style='font-family: sans-serif; padding: 2rem;'>";
    echo "<h1>HitPay API Rejected the Request</h1>";
    echo "<p><strong>HTTP Status:</strong> " . $http_code . "</p>";
    echo "<h3>Raw API Response:</h3>";
    echo "<pre style='background: #f4f4f4; padding: 1rem; border-radius: 8px;'>";
    print_r($response);
    echo "</pre>";
    echo "<a href='/cart.php'>Go Back</a>";
    echo "</div>";
    exit;
}
?>