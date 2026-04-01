<?php
    require_once __DIR__ . '/inc/bootstrap.php';
    require_once __DIR__ . '/inc/support.inc.php';

include "inc/page-top.inc.php"; 
?>

    <div class="container animated py-5" style="max-width: 700px;">
        <h4 class="fw-bold mb-4">Your Cart</h4>
        <hr>

        <div id="cartEmpty" class="text-center text-muted py-5" style="display:none;">
            <i class="bi bi-bag fs-1 mb-3 d-block"></i>
            <p>Your cart is empty.</p>
            <a href="/catalog.php" class="btn btn-dark btn-sm">Browse Products</a>
        </div>

        <div id="cartItems"></div>

        <div id="cartFooter" class="pt-3 mt-3" style="display:none;">
            <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                <span>Total</span>
                <span id="cartTotal"></span>
            </div>
            <form id="checkoutForm" action="/checkout.php" method="POST">
                <input type="hidden" name="amount" id="checkOut" value="0">
                <button type="submit" class="btn btn-dark w-100 mb-2">Checkout</button>
            </form>
        </div>
    </div>

    <script src="js/cart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<?php 
        include "inc/page-bottom.inc.php"; 
?>
</html>