<?php
require_once "inc/auth.inc.php";

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please sign in to update your cart.']);
    exit;
}

if (!isUser()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only user accounts can update cart items.']);
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

try {
    requireValidCsrf((string)($payload['csrf_token'] ?? ''));

    $productId = filter_var(
        $payload['product_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $quantity = filter_var(
        $payload['quantity'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0, 'max_range' => 99]]
    );

    if ($productId === false || $quantity === false) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid cart update payload.']);
        exit;
    }

    $pdo = getDB();

    $productStmt = $pdo->prepare('
        SELECT id, stock
        FROM products
        WHERE id = ? AND is_active = 1
        LIMIT 1
    ');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Product is no longer available.']);
        exit;
    }

    if ($quantity > (int)$product['stock']) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Requested quantity exceeds available stock.']);
        exit;
    }

    if ($quantity === 0) {
        $deleteStmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
        $deleteStmt->execute([(int)getUserId(), $productId]);
    } else {
        $upsertStmt = $pdo->prepare('
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
        ');
        $upsertStmt->execute([(int)getUserId(), $productId, $quantity]);
    }

    $totalsStmt = $pdo->prepare('
        SELECT
            COALESCE(SUM(c.quantity), 0) AS item_count,
            COALESCE(SUM(c.quantity * COALESCE(p.sale_price, p.price)), 0) AS subtotal
        FROM cart c
        INNER JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?
    ');
    $totalsStmt->execute([(int)getUserId()]);
    $totals = $totalsStmt->fetch() ?: ['item_count' => 0, 'subtotal' => 0];

    echo json_encode([
        'success' => true,
        'count' => (int)($totals['item_count'] ?? 0),
        'total' => number_format((float)($totals['subtotal'] ?? 0), 2, '.', ''),
    ]);
} catch (RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('cart_update.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Cart update failed. Please try again.']);
}
