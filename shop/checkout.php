<?php
require_once dirname(__DIR__). '/inc/bootstrap.php';
require_once dirname(__DIR__). '/inc/auth_middleware.php';

// Force login to checkout
require_login();

$amount = $_POST['amount'] ?? 0;

if(!$amount || empty($_SESSION['cart'])){ 
    header('Location: ' . app_url('shop/cart.php')); 
    exit; 
}

// Match the session key used in your login/register process
$userId = $_SESSION['user_id'] ?? 0; 

// 2. Create the base order
$stmt = $db_conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_status) VALUES (?, ?, 'pending', 'pending')");
$stmt->bind_param("id", $userId, $amount);

if ($stmt->execute()) {
    $orderId = $db_conn->insert_id;
    
    // 3. Generate a unique Reference String
    $reference = 'ORD-' . $orderId . '-' . time();
    $updateStmt = $db_conn->prepare("UPDATE orders SET reference = ? WHERE id = ?");
    $updateStmt->bind_param("si", $reference, $orderId);
    $updateStmt->execute();

    // 4. Save the individual cart items
    $stmtItem = $db_conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $item) {
        $qty = $item['qty'] ?? $item['quantity'] ?? 1;
        $stmtItem->bind_param("iisid", $orderId, $item['id'], $item['name'], $qty, $item['price']);
        $stmtItem->execute();
    }
} else {
    die("Database error: Could not create order.");
}

// 5. HitPay API Request (Sandbox)
$apiKey = 'test_13ab37b565272052e323eae670a59e297af4d24e87770540d7bcab4d0364cb14';

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
        'redirect_url'=> app_url('payment_status.php'),
        'purpose' => 'Pomegranate Tech Order',
        'reference_number' => $reference, 
    ]),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$raw_response = curl_exec($ch);
curl_close($ch);

$response = json_decode($raw_response, true);

if (!empty($response['url'])) {
    header('Location: ' . $response['url']);
    exit;
} else {
    echo "<h1>Payment Gateway Error</h1>";
    echo "<pre>"; print_r($response); echo "</pre>";
    exit;
}
?>