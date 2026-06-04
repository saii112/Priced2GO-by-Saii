<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// stop admins from using the sell page
if ($_SESSION['role'] === 'admin') {
    include 'includes/header.php';
    ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="bg-white border rounded-3 p-5 shadow-sm">
                    <i class="bi bi-shield-lock fs-1 text-danger d-block mb-3"></i>
                    <h4 class="fw-bold mb-2">Admin accounts cannot sell items</h4>
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

$error = $success = '';
$cats = ['Electronics','Musical Instruments','Fashion','Sports','Accessories','Home & Kitchen'];
const MAX_IMAGES = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $cond     = $_POST['condition'] ?? '';
    $desc     = trim($_POST['description'] ?? '');
    $type     = $_POST['listing_type'] ?? 'fixed';
    $price    = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration'] ?? 3);
    $end_time = null;

    if (!$title || !$category || !$cond || !$desc || $price <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        // deal with the image upload (up to 5 pics)
        $image_path  = null;   // first pic, stays on the listing for the thumbnail
        $image_paths = [];     // all the pics in order
        if (!empty($_FILES['images']['name'][0])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $count   = min(count($_FILES['images']['name']), MAX_IMAGES);
            for ($i = 0; $i < $count && !$error; $i++) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if (!in_array($_FILES['images']['type'][$i], $allowed)) {
                    $error = 'Only JPG, PNG, GIF and WEBP images are allowed.';
                } elseif ($_FILES['images']['size'][$i] > 10 * 1024 * 1024) {
                    $error = 'Each image must be under 10MB.';
                } else {
                    $ext  = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $name = uniqid('img_', true) . '.' . $ext;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], "uploads/$name")) {
                        $image_paths[] = $name;
                    }
                }
            }
            $image_path = $image_paths[0] ?? null;
        }

        if (!$error) {
            $start_price = ($type === 'auction') ? $price : null;
            $fixed_price = ($type === 'fixed')   ? $price : null;
            if ($type === 'auction') {
                $end_time = date('Y-m-d H:i:s', strtotime("+$duration days"));
            }

            $stmt = $pdo->prepare("INSERT INTO listings
                (seller_id, title, description, category, listing_type, price, start_price, current_bid, end_time, image_path, condition_label, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([
                $_SESSION['user_id'], $title, $desc, $category,
                $type, $fixed_price, $start_price, $start_price,
                $end_time, $image_path, $cond
            ]);
            $new_id  = $pdo->lastInsertId();

            // save all the pics in listing_images for the gallery
            if (!empty($image_paths)) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS listing_images (
                    image_id   INT AUTO_INCREMENT PRIMARY KEY,
                    listing_id INT NOT NULL,
                    image_path VARCHAR(255) NOT NULL,
                    sort_order INT DEFAULT 0,
                    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
                )");
                $imgStmt = $pdo->prepare("INSERT INTO listing_images (listing_id, image_path, sort_order) VALUES (?, ?, ?)");
                foreach ($image_paths as $idx => $p) {
                    $imgStmt->execute([$new_id, $p, $idx]);
                }
            }

            $success = true;
            header("Location: listing.php?id=$new_id");
            exit;
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="bg-white border rounded-3 p-4 p-md-5 shadow-sm">

                <div class="d-flex align-items-center gap-3 mb-4">
                    <i class="bi bi-box-seam fs-2 text-primary"></i>
                    <h2 class="fw-bold mb-0">List Your Item</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <!-- Listing type -->
                    <div class="mb-4">
                        <label class="form-label fw-medium">Listing Type</label>
                        <div class="row g-3" id="typeSelector">
                            <div class="col-6">
                                <div class="type-card selected" id="card-fixed" onclick="selectType('fixed')">
                                    <i class="bi bi-tag fs-3 text-primary d-block mb-1"></i>
                                    <div class="fw-semibold">Fixed Price</div>
                                    <div class="text-muted small">Sell at a set price</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="type-card" id="card-auction" onclick="selectType('auction')">
                                    <i class="bi bi-clock fs-3 text-primary d-block mb-1"></i>
                                    <div class="fw-semibold">Auction</div>
                                    <div class="text-muted small">Let buyers bid</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="listing_type" id="listing_type" value="fixed">
                    </div>

                    <!-- Image upload (up to 5) -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Product Images</label>
                        <div class="dropzone" id="dropzone" onclick="document.getElementById('imageInput').click()">
                            <i class="bi bi-cloud-upload fs-1 text-muted d-block mb-2"></i>
                            <div class="text-muted">Click to upload images</div>
                            <div class="text-muted small">Up to 5 images. PNG, JPG, GIF up to 10MB each</div>
                            <input type="file" name="images[]" id="imageInput" accept="image/*"
                                   class="d-none" multiple onchange="previewImage(event)">
                        </div>
                        <div id="previewWrap" class="d-none mt-2">
                            <div id="previewGrid" class="row g-2"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="removeImage()">
                                <i class="bi bi-x-lg"></i> Clear images
                            </button>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Item Name *</label>
                        <input type="text" name="title" class="form-control"
                               placeholder="What are you selling?"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <!-- Category + Condition -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Category *</label>
                            <select name="category" class="form-select" required>
                                <?php foreach ($cats as $c): ?>
                                    <option value="<?= $c ?>" <?= (($_POST['category'] ?? '') === $c) ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Condition *</label>
                            <select name="condition" class="form-select" required>
                                <?php foreach (['Like New','Excellent','Good','Fair'] as $cnd): ?>
                                    <option value="<?= $cnd ?>" <?= (($_POST['condition'] ?? 'Good') === $cnd) ? 'selected' : '' ?>><?= $cnd ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Description *</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Describe your item in detail…" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Price (fixed) -->
                    <div id="fixedFields" class="mb-3">
                        <label class="form-label fw-medium">Price (ZAR) *</label>
                        <input type="number" name="price" class="form-control"
                               placeholder="0.00" min="1" step="0.01"
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                    </div>

                    <!-- Auction fields -->
                    <div id="auctionFields" class="d-none mb-3">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-medium">Starting Bid (ZAR) *</label>
                                <input type="number" id="auctionPrice" class="form-control"
                                       placeholder="0.00" min="1" step="0.01">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-medium">Auction Duration *</label>
                                <select name="duration" class="form-select">
                                    <option value="1">1 Day</option>
                                    <option value="3" selected>3 Days</option>
                                    <option value="5">5 Days</option>
                                    <option value="7">7 Days</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <a href="index.php" class="btn btn-outline-secondary flex-fill py-2">Cancel</a>
                        <button type="submit" class="btn btn-primary flex-fill py-2 fw-semibold">List Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const MAX_IMAGES = 5;

function selectType(type) {
    document.getElementById('listing_type').value = type;
    document.getElementById('card-fixed').classList.toggle('selected', type === 'fixed');
    document.getElementById('card-auction').classList.toggle('selected', type === 'auction');
    document.getElementById('fixedFields').classList.toggle('d-none', type === 'auction');
    document.getElementById('auctionFields').classList.toggle('d-none', type === 'fixed');
    // sync the price field
    const priceInput = document.querySelector('[name="price"]');
    const auctionInput = document.getElementById('auctionPrice');
    if (type === 'auction') {
        priceInput.removeAttribute('required');
        auctionInput.oninput = () => { priceInput.value = auctionInput.value; };
        priceInput.value = auctionInput.value;
    } else {
        priceInput.setAttribute('required','required');
        auctionInput.oninput = null;
    }
}

function previewImage(event) {
    let files = Array.from(event.target.files);
    if (!files.length) return;
    if (files.length > MAX_IMAGES) {
        alert('You can upload a maximum of ' + MAX_IMAGES + ' images. Only the first ' + MAX_IMAGES + ' will be used.');
        files = files.slice(0, MAX_IMAGES);
    }
    const grid = document.getElementById('previewGrid');
    grid.innerHTML = '';
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const col = document.createElement('div');
            col.className = 'col-4 col-md-3';
            col.innerHTML = '<img class="rounded-3 w-100" style="aspect-ratio:1/1;object-fit:cover;" src="' + e.target.result + '">';
            grid.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
    document.getElementById('dropzone').classList.add('d-none');
    document.getElementById('previewWrap').classList.remove('d-none');
}

function removeImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('previewGrid').innerHTML = '';
    document.getElementById('dropzone').classList.remove('d-none');
    document.getElementById('previewWrap').classList.add('d-none');
}
</script>

<?php include 'includes/footer.php'; ?>
