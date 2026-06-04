<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// admins dont have their own listings
if ($_SESSION['role'] === 'admin') { header('Location: admin/index.php'); exit; }

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: my_listings.php'); exit; }

// get the listing and make sure this user actually owns it
$stmt = $pdo->prepare("SELECT * FROM listings WHERE listing_id = ? AND seller_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$l = $stmt->fetch();
if (!$l) { header('Location: my_listings.php'); exit; }

// grab the current images, fall back to the old single image
$images = [];
try {
    $imgStmt = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id = ? ORDER BY sort_order, image_id");
    $imgStmt->execute([$id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $images = [];
}
if (empty($images) && !empty($l['image_path'])) {
    $images = [$l['image_path']];
}

function resolveImg($p) {
    if (!$p) return 'https://placehold.co/300x300/e5e7eb/9ca3af?text=No+Image';
    return str_starts_with($p, 'http') ? $p : 'uploads/' . htmlspecialchars($p);
}

$error = '';
$cats = ['Electronics','Musical Instruments','Fashion','Sports','Accessories','Home & Kitchen'];
const MAX_IMAGES = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $cond     = $_POST['condition'] ?? '';
    $desc     = trim($_POST['description'] ?? '');
    $price    = floatval($_POST['price'] ?? 0);
    $is_fixed = ($l['listing_type'] === 'fixed');

    if (!$title || !$category || !$cond || !$desc || ($is_fixed && $price <= 0)) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        // if they upload new pics, those replace the old ones (max 5)
        $image_paths = [];
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
        }

        if (!$error) {
            // update the listing details (price only matters for fixed price)
            if ($is_fixed) {
                $pdo->prepare("UPDATE listings SET title=?, description=?, category=?, condition_label=?, price=? WHERE listing_id=? AND seller_id=?")
                    ->execute([$title, $desc, $category, $cond, $price, $id, $_SESSION['user_id']]);
            } else {
                $pdo->prepare("UPDATE listings SET title=?, description=?, category=?, condition_label=? WHERE listing_id=? AND seller_id=?")
                    ->execute([$title, $desc, $category, $cond, $id, $_SESSION['user_id']]);
            }

            // swap out all the images if new ones came through
            if (!empty($image_paths)) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS listing_images (
                    image_id   INT AUTO_INCREMENT PRIMARY KEY,
                    listing_id INT NOT NULL,
                    image_path VARCHAR(255) NOT NULL,
                    sort_order INT DEFAULT 0,
                    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
                )");
                $pdo->prepare("DELETE FROM listing_images WHERE listing_id = ?")->execute([$id]);
                $imgIns = $pdo->prepare("INSERT INTO listing_images (listing_id, image_path, sort_order) VALUES (?, ?, ?)");
                foreach ($image_paths as $idx => $p) {
                    $imgIns->execute([$id, $p, $idx]);
                }
                $pdo->prepare("UPDATE listings SET image_path = ? WHERE listing_id = ?")
                    ->execute([$image_paths[0], $id]);
            }

            header("Location: listing.php?id=$id");
            exit;
        }
    }

    // keep what they typed if something didnt validate
    $l['title'] = $title; $l['category'] = $category; $l['condition_label'] = $cond;
    $l['description'] = $desc; if ($l['listing_type'] === 'fixed') $l['price'] = $price;
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="bg-white border rounded-3 p-4 p-md-5 shadow-sm">

                <div class="d-flex align-items-center gap-3 mb-4">
                    <i class="bi bi-pencil-square fs-2 text-primary"></i>
                    <h2 class="fw-bold mb-0">Edit Listing</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <!-- Current + new images -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Product Images</label>

                        <?php if (!empty($images)): ?>
                            <div class="text-muted small mb-2">Current photos:</div>
                            <div class="row g-2 mb-2">
                                <?php foreach ($images as $imgPath): ?>
                                    <div class="col-4 col-md-3">
                                        <img src="<?= resolveImg($imgPath) ?>" class="rounded-3 w-100" style="aspect-ratio:1/1;object-fit:cover;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-muted small mb-2">If a photo above is blank, the file was lost — upload new ones below to replace them.</div>
                        <?php endif; ?>

                        <div class="dropzone" id="dropzone" onclick="document.getElementById('imageInput').click()">
                            <i class="bi bi-cloud-upload fs-1 text-muted d-block mb-2"></i>
                            <div class="text-muted">Click to upload new photos</div>
                            <div class="text-muted small">Up to 5 images. PNG, JPG, GIF up to 10MB each. Replaces current photos.</div>
                            <input type="file" name="images[]" id="imageInput" accept="image/*"
                                   class="d-none" multiple onchange="previewImage(event)">
                        </div>
                        <div id="previewWrap" class="d-none mt-2">
                            <div id="previewGrid" class="row g-2"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="removeImage()">
                                <i class="bi bi-x-lg"></i> Clear new images
                            </button>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Item Name *</label>
                        <input type="text" name="title" class="form-control"
                               value="<?= htmlspecialchars($l['title'] ?? '') ?>" required>
                    </div>

                    <!-- Category + Condition -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Category *</label>
                            <select name="category" class="form-select" required>
                                <?php foreach ($cats as $c): ?>
                                    <option value="<?= $c ?>" <?= (($l['category'] ?? '') === $c) ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Condition *</label>
                            <select name="condition" class="form-select" required>
                                <?php foreach (['Like New','Excellent','Good','Fair'] as $cnd): ?>
                                    <option value="<?= $cnd ?>" <?= (($l['condition_label'] ?? '') === $cnd) ? 'selected' : '' ?>><?= $cnd ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Description *</label>
                        <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($l['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Price (fixed listings only) -->
                    <?php if ($l['listing_type'] === 'fixed'): ?>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Price (ZAR) *</label>
                            <input type="number" name="price" class="form-control"
                                   min="1" step="0.01"
                                   value="<?= htmlspecialchars($l['price'] ?? '') ?>" required>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border small text-muted">
                            <i class="bi bi-info-circle me-1"></i>This is an auction listing, so the price and bids can't be edited here.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-3 mt-4">
                        <a href="my_listings.php" class="btn btn-outline-secondary flex-fill py-2">Cancel</a>
                        <button type="submit" class="btn btn-primary flex-fill py-2 fw-semibold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const MAX_IMAGES = 5;

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
