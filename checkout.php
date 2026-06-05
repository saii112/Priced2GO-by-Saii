<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Admins cannot buy items
if ($_SESSION['role'] === 'admin') {
    include 'includes/header.php';
    ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="bg-white border rounded-3 p-5 shadow-sm">
                    <i class="bi bi-shield-lock fs-1 text-danger d-block mb-3"></i>
                    <h4 class="fw-bold mb-2">Admin accounts cannot buy items</h4>
                    <p class="text-muted mb-4">Administrator accounts are restricted from buying or selling on the marketplace. Use the Admin Panel to manage the platform.</p>
                    <a href="admin/index.php" class="btn btn-primary">Go to Admin Panel</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT l.*, u.username AS seller_name
                        FROM listings l
                        JOIN users u ON u.user_id = l.seller_id
                        WHERE l.listing_id = ? AND l.status = 'active'");
$stmt->execute([$id]);
$l = $stmt->fetch();
if (!$l) { header('Location: index.php'); exit; }

// Payfast sandbox
$merchant_id  = '10048639';
$merchant_key = 'yj45y8yq0lmx1';
$passphrase   = 'SAIIGOTPRICED2GO';
$payfast_url  = 'https://sandbox.payfast.co.za/eng/process';
//

// price calculations
$final_price = $l['listing_type'] === 'auction'
             ? floatval($l['current_bid'])
             : floatval($l['price']);
$service_fee = round($final_price * 0.05, 2);
$shipping    = 90.00;
$total       = round($final_price + $service_fee + $shipping, 2);

// get buyer informstion
$buyer_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$buyer_stmt->execute([$_SESSION['user_id']]);
$buyer = $buyer_stmt->fetch();

// create a pending order to track a payment
$m_payment_id = 'P2GO-' . time() . '-' . $id;
$pdo->prepare("INSERT INTO orders (buyer_id, listing_id, total_amount, status, payment_ref)
               VALUES (?, ?, ?, 'pending', ?)")
    ->execute([$_SESSION['user_id'], $id, $total, $m_payment_id]);

// payfast data array
$data = [
    'merchant_id'      => $merchant_id,
    'merchant_key'     => $merchant_key,
    'return_url'       => 'https://priced2go-website-production.up.railway.app/payment_return.php',
    'cancel_url'       => 'https://priced2go-website-production.up.railway.app/payment_cancel.php?id=' . $id,
    'notify_url'       => 'https://priced2go-website-production.up.railway.app/payment_notify.php',
    'name_first'       => $buyer['username'],
    'email_address'    => $buyer['email'],
    'm_payment_id'     => $m_payment_id,
    'amount'           => number_format($total, 2, '.', ''),
    'item_name'        => substr($l['title'], 0, 100),
    'item_description' => substr($l['description'] ?? '', 0, 255),
];

// md5 signature, skip empty values like payfasts own example
foreach ($data as $k => $v) { $data[$k] = trim($v); }   // trim so posted values match what we sign
$pfOutput = '';
foreach ($data as $key => $val) {
    if ($val !== '') {
        $pfOutput .= $key . '=' . urlencode($val) . '&';
    }
}
$getString = substr($pfOutput, 0, -1);
if (!empty($passphrase)) {
    $getString .= '&passphrase=' . urlencode(trim($passphrase));
}
$data['signature'] = md5($getString);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">

        <!-- Order Summary (right column) -->
        <div class="col-lg-4 order-lg-2">
            <div class="bg-white border rounded-3 p-4 shadow-sm sticky-top" style="top:90px">
                <h5 class="fw-semibold mb-3">Order Summary</h5>
                <?php
                    $img = $l['image_path']
                         ? 'uploads/'.htmlspecialchars($l['image_path'])
                         : 'https://placehold.co/80x80/e5e7eb/9ca3af?text=Item';
                ?>
                <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($l['title']) ?>"
                         class="rounded" style="width:70px;height:70px;object-fit:cover">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($l['title']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($l['category']) ?></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span>Item Price:</span><span>R<?= number_format($final_price, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span>Service Fee (5%):</span><span>R<?= number_format($service_fee, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between text-muted small mb-3">
                    <span>Shipping:</span><span>R<?= number_format($shipping, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between fw-bold border-top pt-3">
                    <span>Total:</span>
                    <span class="text-primary fs-5">R<?= number_format($total, 2) ?></span>
                </div>
                <div class="text-success small mt-3 d-flex align-items-center gap-1">
                    <i class="bi bi-shield-lock"></i> Secured by PayFast
                </div>
            </div>
        </div>

        <!-- PayFast redirect form (left column) -->
        <div class="col-lg-8 order-lg-1">
            <div class="bg-white border rounded-3 p-4 p-md-5 shadow-sm">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-lock-fill text-success fs-4"></i>
                    <h3 class="fw-bold mb-0">Secure Checkout</h3>
                </div>
                <p class="text-muted mb-4">
                    You will be redirected to <strong>PayFast</strong> to complete your
                    payment securely. PayFast is South Africa's leading payment gateway.
                </p>

                <div class="alert alert-info small mb-4">
                    <strong>Sandbox Mode:</strong> Use PayFast's test card details on the
                    next page. No real money is charged.
                </div>

                <div class="border rounded-3 p-4 mb-4 bg-light">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div class="text-muted small">Item</div>
                            <div class="fw-semibold small"><?= htmlspecialchars(substr($l['title'], 0, 20)) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Buyer</div>
                            <div class="fw-semibold small"><?= htmlspecialchars($buyer['username']) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Total</div>
                            <div class="fw-semibold text-primary">R<?= number_format($total, 2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Posts directly to PayFast sandbox -->
                <form action="<?= $payfast_url ?>" method="POST">
                    <?php foreach ($data as $key => $value): ?>
                        <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold fs-5">
                        <i class="bi bi-lock me-2"></i>Pay R<?= number_format($total, 2) ?> with PayFast
                    </button>
                </form>

                <a href="listing.php?id=<?= $id ?>" class="btn btn-outline-secondary w-100 mt-2 py-2">
                    Cancel and go back
                </a>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
