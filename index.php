<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Admin: handle delete listing from browse page
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete_listing' &&
    isset($_SESSION['role']) && $_SESSION['role'] === 'admin'
) {
    $lid = intval($_GET['id']);
    $pdo->prepare("UPDATE listings SET status = 'expired' WHERE listing_id = ?")->execute([$lid]);
    header('Location: index.php'); exit;
}

$category = $_GET['category'] ?? 'All';
$search   = trim($_GET['search'] ?? '');

$sql    = "SELECT * FROM listings WHERE status = 'active'";
$params = [];

if ($category !== 'All') {
    $sql .= " AND category = ?";
    $params[] = $category;
}
if ($search !== '') {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$cats = ['All','Electronics','Musical Instruments','Fashion','Sports','Accessories','Home & Kitchen'];
include 'includes/header.php';
?>

<div class="container py-4">

    <!-- Search bar -->
    <form method="GET" class="mb-4">
        <div class="input-group input-group-lg shadow-sm">
            <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" name="search" class="form-control border-start-0 ps-0"
                   placeholder="Search for items…" value="<?= htmlspecialchars($search) ?>">
            <?php if ($category !== 'All'): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <?php endif; ?>
            <button class="btn btn-primary px-4" type="submit">Search</button>
        </div>
    </form>

    <!-- Seller quick-action button (not shown to guests or admins) -->
    <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') !== 'admin'): ?>
    <div class="d-flex gap-2 mb-4">
        <a href="my_listings.php" class="btn btn-outline-primary">
            <i class="bi bi-list-ul me-2"></i>My Active Listings
        </a>
        <a href="sell.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>List New Item
        </a>
    </div>
    <?php endif; ?>

    <!-- Category filters -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <?php foreach ($cats as $cat): ?>
            <a href="?category=<?= urlencode($cat) ?><?= $search ? '&search='.urlencode($search) : '' ?>"
               class="cat-btn <?= $category === $cat ? 'active' : '' ?>">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Listing grid -->
    <?php if (empty($listings)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
            <p class="fs-5">No listings found. <a href="sell.php">Be the first to sell something!</a></p>
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
            <?php foreach ($listings as $l): ?>
                <div class="col">
                    <div class="listing-card position-relative">
                        <a href="listing.php?id=<?= $l['listing_id'] ?>" class="d-block text-decoration-none text-dark">
                        <?php
                            $img = $l['image_path']
                                ? 'uploads/'.htmlspecialchars($l['image_path'])
                                : 'https://placehold.co/400x400/e5e7eb/9ca3af?text=No+Image';
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
                        </div>
                        </a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <div class="px-3 pb-3">
                                <a href="?action=delete_listing&id=<?= $l['listing_id'] ?>"
                                   class="btn btn-sm btn-outline-danger w-100"
                                   onclick="return confirm('Remove this listing from the marketplace?')">
                                    <i class="bi bi-trash me-1"></i>Remove Listing
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
