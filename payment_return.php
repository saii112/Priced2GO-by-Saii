<?php
// payment_return.php,the buyer lands here after paying
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// look up the buyers latest order to show the right message
$status = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE buyer_id = ? ORDER BY order_id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $order = $stmt->fetch();
    $status = $order['status'] ?? null;
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 text-center">
            <div class="bg-white border rounded-3 p-5 shadow-sm">
                <?php if ($status === 'paid'): ?>
                    <i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>
                    <h2 class="fw-bold mt-3 mb-2">Payment Successful!</h2>
                    <p class="text-muted mb-1">Your payment was processed by PayFast.</p>
                    <p class="text-muted small mb-4">Your order has been confirmed and the seller has been notified.</p>
                <?php else: ?>
                    <i class="bi bi-hourglass-split text-warning" style="font-size:4rem"></i>
                    <h2 class="fw-bold mt-3 mb-2">Payment Processing</h2>
                    <p class="text-muted small mb-4">We're confirming your payment. Refresh in a moment.</p>
                <?php endif; ?>
                <a href="index.php" class="btn btn-primary px-5 py-2">Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
