<?php
    require_once dirname(__DIR__) . '/inc/bootstrap.php';
    require_once dirname(__DIR__) . '/inc/support.inc.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomegranate | Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('/css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include dirname(__DIR__) . "/inc/nav.inc.php"; ?>

    <div class="container animated py-5" style="max-width: 700px;">
        <h4 class="fw-bold mb-4">Your Cart</h4>
        <hr>

        <div id="cartEmpty" class="text-center text-muted py-5" style="display:none;">
            <i class="bi bi-bag fs-1 mb-3 d-block"></i>
            <p>Your cart is empty.</p>
            <a href="/shop/catalog.php" class="btn btn-dark btn-sm">Browse Products</a>
        </div>

        <div id="cartItems"></div>

        <div id="cartFooter" class="pt-3 mt-3" style="display:none;">
            <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                <span>Total</span>
                <span id="cartTotal"></span>
            </div>
            <form id="checkoutForm" action="/shop/checkout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="amount" id="checkOut" value="0">
                <button type="submit" class="btn btn-dark w-100 fw-bold">Checkout</button>
            </form>
        </div>
    </div>

    <script src="/js/cart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<?php 
        include dirname(__DIR__) . '/inc/footer.inc.php'; 
?>
</html>