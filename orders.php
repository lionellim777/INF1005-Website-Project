<?php
require_once "inc/auth.inc.php";
requireLogin($_SERVER['REQUEST_URI']);

$orders = [];
$orderItemsByOrderId = [];
$err = '';

$statusFilter = strtolower(trim((string)($_GET['status'] ?? 'all')));
$allowedStatusFilters = ['all', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($statusFilter, $allowedStatusFilters, true)) {
    $statusFilter = 'all';
}

try {
    $pdo = getDB();

    $sql = '
        SELECT
            o.id,
            o.total,
            o.status,
            o.shipping_address,
            o.notes,
            o.created_at,
            o.updated_at,
            COUNT(oi.id) AS item_lines,
            COALESCE(SUM(oi.quantity), 0) AS total_items
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE o.user_id = ?
    ';
    $params = [(int)getUserId()];

    if ($statusFilter !== 'all') {
        $sql .= ' AND o.status = ?';
        $params[] = $statusFilter;
    }

    $sql .= ' GROUP BY o.id ORDER BY o.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    if (!empty($orders)) {
        $orderIds = array_map(static fn(array $order): int => (int)$order['id'], $orders);
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        $itemsStmt = $pdo->prepare(
            "SELECT oi.order_id, oi.quantity, oi.price, p.name AS product_name
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id IN ($placeholders)
             ORDER BY oi.order_id DESC, oi.id ASC"
        );
        $itemsStmt->execute($orderIds);

        foreach ($itemsStmt->fetchAll() as $item) {
            $orderId = (int)$item['order_id'];
            if (!isset($orderItemsByOrderId[$orderId])) {
                $orderItemsByOrderId[$orderId] = [];
            }
            $orderItemsByOrderId[$orderId][] = $item;
        }
    }
} catch (Throwable $e) {
    error_log('Orders page error: ' . $e->getMessage());
    $err = 'Purchase history is temporarily unavailable. Please try again later.';
}

$statusLabels = [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<?php include "inc/nav.inc.php"; ?>

<main id="main-content" style="padding:3rem 0 5rem;min-height:calc(100vh - 160px);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label"><i class="bi bi-receipt"></i> Orders</div>
            <h1 class="section-heading">Purchase <span class="text-gradient">History</span></h1>
            <p class="text-white-50 mb-0">Track your previous orders and their delivery status.</p>
        </div>

        <?php if ($err): ?>
        <div class="alert-error-dark p-3 mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= h($err) ?></span>
        </div>
        <?php endif; ?>

        <section class="glass-card p-4 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="filter-bar" aria-label="Status filters">
                    <?php foreach ($allowedStatusFilters as $filter): ?>
                    <?php
                        $label = $filter === 'all' ? 'All' : ($statusLabels[$filter] ?? ucfirst($filter));
                        $activeClass = $statusFilter === $filter ? ' active' : '';
                    ?>
                    <button type="button" class="filter-btn<?= $activeClass ?>" data-status-filter="<?= h($filter) ?>">
                        <?= h($label) ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div class="search-bar-wrap">
                    <i class="bi bi-search"></i>
                    <input type="search" id="order-search" class="form-control-dark" placeholder="Search order # or product" style="padding-left:2.5rem;min-width:240px;" aria-label="Search your orders">
                </div>
            </div>
        </section>

        <?php if (empty($orders) && !$err): ?>
        <section class="contact-form-card text-center">
            <i class="bi bi-bag-x text-white-50" style="font-size:2.2rem;"></i>
            <h2 class="h5 fw-bold mt-3 mb-2">No Orders Yet</h2>
            <p class="text-white-50 mb-4">When you place orders, they will appear here for easy tracking.</p>
            <a href="/catalog.php" class="btn-primary-glow" style="justify-content:center;">
                <i class="bi bi-grid-3x3-gap"></i> Browse Catalog
            </a>
        </section>
        <?php endif; ?>

        <div class="d-flex flex-column gap-3" id="orders-list">
            <?php foreach ($orders as $order): ?>
            <?php
                $orderId = (int)$order['id'];
                $status = strtolower((string)$order['status']);
                $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                $statusClass = 'status-' . h($status);
                $items = $orderItemsByOrderId[$orderId] ?? [];
                $searchBlobParts = [
                    'order#' . $orderId,
                    $status,
                ];
                foreach ($items as $item) {
                    $searchBlobParts[] = strtolower((string)($item['product_name'] ?? ''));
                }
                $searchBlob = implode(' ', $searchBlobParts);
            ?>
            <article class="contact-form-card order-card" data-order-status="<?= h($status) ?>" data-search="<?= h($searchBlob) ?>">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1">Order #<?= $orderId ?></h2>
                        <p class="text-white-50 small mb-0">
                            Placed <?= h(date('d M Y, H:i', strtotime((string)$order['created_at']))) ?>
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-badge <?= $statusClass ?>" style="text-transform:capitalize;">
                            <?= h($statusLabel) ?>
                        </span>
                        <span class="fw-bold text-white">$<?= number_format((float)$order['total'], 2) ?></span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <h3 class="h6 text-white-50 mb-2" style="font-size:.85rem;">Items (<?= (int)$order['total_items'] ?>)</h3>
                        <?php if (!empty($items)): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center gap-3 p-2 rounded" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);">
                                <div>
                                    <div class="small fw-semibold text-white mb-0"><?= h($item['product_name'] ?? 'Product removed') ?></div>
                                    <div class="text-white-50" style="font-size:.78rem;">Qty: <?= (int)$item['quantity'] ?></div>
                                </div>
                                <div class="small text-white fw-semibold">$<?= number_format((float)$item['price'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-white-50 small mb-0">No order line items available.</p>
                        <?php endif; ?>
                    </div>

                    <div class="col-12 col-lg-4">
                        <h3 class="h6 text-white-50 mb-2" style="font-size:.85rem;">Delivery</h3>
                        <p class="text-white small mb-2" style="line-height:1.5;">
                            <?= h($order['shipping_address'] ?: 'No shipping address provided.') ?>
                        </p>
                        <?php if (!empty($order['notes'])): ?>
                        <p class="text-white-50 small mb-0"><strong>Notes:</strong> <?= h($order['notes']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php include "inc/footer.inc.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
(function () {
    const cards = Array.from(document.querySelectorAll('.order-card'));
    const filterButtons = Array.from(document.querySelectorAll('[data-status-filter]'));
    const searchInput = document.getElementById('order-search');

    let activeStatus = '<?= h($statusFilter) ?>';
    let query = '';

    const applyFilters = () => {
        cards.forEach((card) => {
            const status = card.getAttribute('data-order-status') || '';
            const search = (card.getAttribute('data-search') || '').toLowerCase();

            const statusOk = activeStatus === 'all' || status === activeStatus;
            const queryOk = query === '' || search.includes(query);
            card.style.display = statusOk && queryOk ? '' : 'none';
        });
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            filterButtons.forEach((btn) => btn.classList.remove('active'));
            button.classList.add('active');
            activeStatus = button.getAttribute('data-status-filter') || 'all';
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            query = searchInput.value.trim().toLowerCase();
            applyFilters();
        });
    }

    applyFilters();
})();
</script>
</body>
</html>
