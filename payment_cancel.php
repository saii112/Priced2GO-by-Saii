<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$id = intval($_GET['id'] ?? 0);
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 text-center">
            <div class="bg-white border rounded-3 p-5 shadow-sm">
                <i class="bi bi-x-circle-fill text-danger" style="font-size:4rem"></i>
                <h2 class="fw-bold mt-3 mb-2">Payment Cancelled</h2>
                <p class="text-muted mb-4">You cancelled the payment. No money was charged.</p>
                <?php if ($id): ?>
                    <a href="listing.php?id=<?= $id ?>" class="btn btn-primary px-5 py-2 me-2">Go Back to Item</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-secondary px-4 py-2">Browse Listings</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
