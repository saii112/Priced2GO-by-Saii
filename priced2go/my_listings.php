<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

// disables admin sell functionality
if ($_SESSION['role'] === 'admin') {
    header('Location: admin/index.php'); exit;
}

$seller_id = $_SESSION['user_id'];

//  fetch a specific users current listings
$stmt = $pdo->prepare(
    "SELECT * FROM listings
     WHERE seller_id = ? AND status = 'active'
     ORDER BY created_at DESC"
);
$stmt->execute([$seller_id]);
$my_listings = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container py-4">

    <!-- Page header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Active Listings</h2>
            <p class="text-muted mb-0">Items you currently have for sale</p>
        </div>
        <a href="sell.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>List New Item
        </a>
    </div>

    <?php if (empty($my_listings)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
            <p class="fs-5">You have no active listings.</p>
            <a href="sell.php" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-2"></i>List Your First Item
            </a>
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
            <?php foreach ($my_listings as $l): ?>
                <div class="col">
                    <div class="listing-card position-relative">
                        <!-- Link wraps image + body -->
                        <a href="listing.php?id=<?= $l['listing_id'] ?>" class="text-decoration-none text-dark d-block">
                            <?php
                                $raw = $l['image_path'] ?? '';
                                if ($raw) {
                                    $img = str_starts_with($raw, 'http')
                                         ? $raw
                                         : 'uploads/' . htmlspecialchars($raw);
                                } else {
                                    $img = 'https://placehold.co/400x400/e5e7eb/9ca3af?text=No+Image';
                                }
                            ?>
                            <img src="<?= $img ?>" alt="<?= htmlspecialchars($l['title']) ?>">
                            <div class="card-body">
                                <div class="card-cat"><?= htmlspecialchars($l['category']) ?></div>
                                <div class="card-title"><?= htmlspecialchars($l['title']) ?></div>
                                <?php if ($l['listing_type'] === 'auction'): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="badge-auction"><i class="bi bi-clock me-1"></i>Auction</span>
                                        <span class="fw-semibold text-dark">R<?= number_format($l['current_bid'] ?: $l['start_price'], 2) ?></span>
                                    </div>
                                    <div class="text-muted small mt-1">Current bid</div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="badge-fixed"><i class="bi bi-tag me-1"></i>Fixed Price</span>
                                        <span class="fw-semibold text-dark">R<?= number_format($l['price'], 2) ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Listed date -->
                                <div class="text-muted small mt-2">
                                    <i class="bi bi-calendar3 me-1"></i>Listed <?= date('d M Y', strtotime($l['created_at'])) ?>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="text-muted small mt-4">
            Showing <?= count($my_listings) ?> active listing<?= count($my_listings) !== 1 ? 's' : '' ?>.
            To remove a listing, contact an administrator or view it and wait for it to expire.
        </p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
