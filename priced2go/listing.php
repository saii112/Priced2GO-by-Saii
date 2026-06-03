<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT l.*, u.username AS seller_name
                        FROM listings l
                        JOIN users u ON u.user_id = l.seller_id
                        WHERE l.listing_id = ?");
$stmt->execute([$id]);
$l = $stmt->fetch();
if (!$l) { header('Location: index.php'); exit; }

// recent bids
$bids = $pdo->prepare("SELECT b.bid_amount, b.bid_time, u.username
                        FROM bids b JOIN users u ON u.user_id = b.bidder_id
                        WHERE b.listing_id = ?
                        ORDER BY b.bid_amount DESC LIMIT 5");
$bids->execute([$id]);
$recent_bids = $bids->fetchAll();

$error = $msg = '';

// handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php'); exit;
    }
    if ($_SESSION['role'] === 'admin') {
        $error = 'Administrator accounts cannot place bids.';
    } else {
    $amount = floatval($_POST['bid_amount']);
    $min    = floatval($l['current_bid'] ?: $l['start_price']) + 1;
    if ($amount < $min) {
        $error = "Bid must be at least R$min.";
    } else {
        $pdo->prepare("INSERT INTO bids (listing_id, bidder_id, bid_amount) VALUES (?,?,?)")
            ->execute([$id, $_SESSION['user_id'], $amount]);
        $pdo->prepare("UPDATE listings SET current_bid = ? WHERE listing_id = ?")
            ->execute([$amount, $id]);
        $l['current_bid'] = $amount;
        $msg = "Bid of R$amount placed successfully!";
        // Refresh bids
        $bids->execute([$id]);
        $recent_bids = $bids->fetchAll();
    }
    }
}

// time remaining helper for auction
function timeRemaining($end) {
    $diff = strtotime($end) - time();
    if ($diff <= 0) return 'Ended';
    $days  = floor($diff / 86400);
    $hours = floor(($diff % 86400) / 3600);
    return $days > 0 ? "$days days $hours hours" : "$hours hours";
}

$img = $l['image_path']
     ? (str_starts_with($l['image_path'], 'http') ? $l['image_path'] : 'uploads/'.htmlspecialchars($l['image_path']))
     : 'https://placehold.co/600x600/e5e7eb/9ca3af?text=No+Image';

include 'includes/header.php';
?>

<div class="container py-5">
    <a href="index.php" class="text-muted small mb-4 d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to listings
    </a>

    <div class="row g-5 mt-1">
        <!-- Image -->
        <div class="col-lg-6">
            <div class="product-image-wrap">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($l['title']) ?>">
            </div>
        </div>

        <!-- Details -->
        <div class="col-lg-6">
            <span class="badge bg-primary bg-opacity-10 text-primary mb-3"><?= htmlspecialchars($l['category']) ?></span>
            <h1 class="fw-bold mb-2"><?= htmlspecialchars($l['title']) ?></h1>
            <p class="text-muted mb-3"><i class="bi bi-person me-1"></i>Sold by <strong><?= htmlspecialchars($l['seller_name']) ?></strong></p>

            <div class="border-top border-bottom py-3 mb-4 d-flex justify-content-between">
                <span class="text-muted">Condition:</span>
                <span class="fw-medium"><?= htmlspecialchars($l['condition'] ?? 'Not specified') ?></span>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <?php if ($l['listing_type'] === 'auction'): ?>
                <!-- Auction box -->
                <div class="auction-box mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-hammer text-primary fs-5"></i>
                        <h5 class="mb-0 fw-semibold">Active Auction</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-warning mb-2">
                        <i class="bi bi-clock"></i>
                        <span class="fw-medium"><?= timeRemaining($l['end_time']) ?> remaining</span>
                    </div>
                    <div class="fs-2 fw-bold mb-1">R<?= number_format($l['current_bid'] ?: $l['start_price'], 2) ?></div>
                    <div class="text-muted small mb-4">Current highest bid</div>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $l['seller_id'] && $_SESSION['role'] !== 'admin'): ?>
                        <form method="POST" class="mb-4">
                            <label class="form-label fw-medium">Your Bid (ZAR)</label>
                            <div class="input-group">
                                <input type="number" name="bid_amount" class="form-control"
                                       placeholder="Min: R<?= floatval($l['current_bid'] ?: $l['start_price']) + 1 ?>"
                                       min="<?= floatval($l['current_bid'] ?: $l['start_price']) + 1 ?>"
                                       step="0.01" required>
                                <button type="submit" name="place_bid" class="btn btn-primary px-4">Place Bid</button>
                            </div>
                        </form>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="btn btn-primary w-100 mb-4">Login to Bid</a>
                    <?php endif; ?>

                    <!-- Recent bids -->
                    <?php if (!empty($recent_bids)): ?>
                        <div class="border-top pt-3">
                            <h6 class="fw-semibold mb-3">Recent Bids</h6>
                            <?php foreach ($recent_bids as $b): ?>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted"><?= htmlspecialchars($b['username']) ?></span>
                                    <div class="text-end">
                                        <div class="fw-semibold">R<?= number_format($b['bid_amount'], 2) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem"><?= date('d M H:i', strtotime($b['bid_time'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- Fixed price box -->
                <div class="fixed-box mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-tag text-success fs-5"></i>
                        <h5 class="mb-0 fw-semibold">Fixed Price</h5>
                    </div>
                    <div class="fs-2 fw-bold mb-4">R<?= number_format($l['price'], 2) ?></div>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $l['seller_id'] && $_SESSION['role'] !== 'admin'): ?>
                        <a href="checkout.php?id=<?= $l['listing_id'] ?>" class="btn btn-success w-100 py-2 fw-semibold">
                            <i class="bi bi-cart me-2"></i>Buy Now
                        </a>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="btn btn-success w-100 py-2 fw-semibold">Login to Buy</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Description -->
            <h5 class="fw-semibold mb-2">Description</h5>
            <p class="text-muted"><?= nl2br(htmlspecialchars($l['description'])) ?></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
