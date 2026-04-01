<?php
require_once __DIR__ . '/inc/bootstrap.php';
session_write_close();

$status = $_GET['status'] ?? 'unknown';
$reference = $_GET['reference'] ?? '';

$pageTitle = 'Pomegranate | Payment Status';
include __DIR__ . '/inc/page-top.inc.php';
?>

    <div class="container animated py-5 text-center">
        <?php if ($status === 'completed'): ?>
            <h4 class="fw-bold mt-3">Payment Successful!</h4>
            <p class="text-muted">Thanks for your order. Reference: <strong><?= htmlspecialchars($reference) ?></strong></p>
            <a href="/catalog.php" class="btn btn-dark mt-2">Continue Shopping</a>
            <script>
                localStorage.removeItem('cart');
            </script>
        <?php else: ?>
            <h4 class="fw-bold mt-3">Payment Failed</h4>
            <p class="text-muted">Something went wrong. Please try again.</p>
            <a href="/cart.php" class="btn btn-dark mt-2">Back to Cart</a>
        <?php endif; ?>
    </div>

    <?php 
        include "inc/footer.inc.php"; 
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cart.js"></script>
</body>
</html>