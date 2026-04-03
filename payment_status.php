<?php
$status = $_GET['status'] ?? 'unknown';
$reference = $_GET['reference'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment 2 Payment Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php 
        include "inc/nav.inc.php"; 
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
        <h4 class="fw-bold mt-3">
            <?= $status === 'canceled' ? 'Payment Cancelled' : 'Payment Failed' ?>
        </h4>
        <p class="text-muted">
            <?= $status === 'canceled' 
                ? 'You cancelled the payment.' 
                : 'Something went wrong. Please try again.' ?>
        </p>
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