<?php
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit;
}
$isAdmin = true;

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $aid = intval($_GET['id']);
    switch ($_GET['action']) {
        case 'delete_user':
            $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'")->execute([$aid]); break;
        case 'suspend_user':
            $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?")->execute([$aid]); break;
        case 'activate_user':
            $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?")->execute([$aid]); break;
        case 'delete_listing':
            $pdo->prepare("UPDATE listings SET status = 'expired' WHERE listing_id = ?")->execute([$aid]); break;
        case 'approve_listing':
            $pdo->prepare("UPDATE listings SET status = 'active' WHERE listing_id = ?")->execute([$aid]); break;
    }
    header('Location: index.php'); exit;
}

// Stats
$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_listings = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue  = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='paid'")->fetchColumn();

// Data
$users       = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$listings_all = $pdo->query("SELECT l.*, u.username AS seller_name FROM listings l JOIN users u ON u.user_id = l.seller_id ORDER BY l.created_at DESC")->fetchAll();
$transactions = $pdo->query("SELECT o.*, u.username AS buyer_name, l.title AS listing_title FROM orders o JOIN users u ON u.user_id = o.buyer_id JOIN listings l ON l.listing_id = o.listing_id ORDER BY o.created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Admin Dashboard</h2>
        <p class="text-muted">Manage the Priced2GO marketplace</p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-label">Total Revenue</div>
                    <i class="bi bi-coin text-muted"></i>
                </div>
                <div class="stat-num">R<?= number_format($total_revenue, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-label">Active Users</div>
                    <i class="bi bi-people text-muted"></i>
                </div>
                <div class="stat-num"><?= $total_users ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-label">Total Listings</div>
                    <i class="bi bi-bag text-muted"></i>
                </div>
                <div class="stat-num"><?= $total_listings ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-label">Total Orders</div>
                    <i class="bi bi-graph-up text-muted"></i>
                </div>
                <div class="stat-num"><?= $total_orders ?></div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs admin-tabs mb-3" id="adminTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-listings">Listings</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-users">Users</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-transactions">Transactions</a>
        </li>
    </ul>

    <div class="tab-content">

        <!-- Listings tab -->
        <div class="tab-pane fade show active" id="tab-listings">
            <div class="bg-white border rounded-3 p-3 shadow-sm">
                <h5 class="fw-semibold mb-3">Product Management</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Seller</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listings_all as $li): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($li['title']) ?></td>
                                    <td><?= htmlspecialchars($li['seller_name']) ?></td>
                                    <td><?= ucfirst($li['listing_type']) ?></td>
                                    <td>R<?= number_format($li['listing_type'] === 'auction' ? $li['current_bid'] : $li['price'], 2) ?></td>
                                    <td>
                                        <?php
                                        $sc = ['active'=>'success','sold'=>'secondary','expired'=>'danger'];
                                        $c = $sc[$li['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $c ?>"><?= ucfirst($li['status']) ?></span>
                                    </td>
                                    <td>
                                        <a href="../listing.php?id=<?= $li['listing_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        <?php if ($li['status'] === 'active'): ?>
                                            <a href="?action=delete_listing&id=<?= $li['listing_id'] ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Remove this listing?')">Remove</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users tab -->
        <div class="tab-pane fade" id="tab-users">
            <div class="bg-white border rounded-3 p-3 shadow-sm">
                <h5 class="fw-semibold mb-3">User Management</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= ucfirst($u['role']) ?></span></td>
                                    <td>
                                        <?php $st = $u['status'] ?? 'active'; ?>
                                        <span class="badge bg-<?= $st === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($st) ?></span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <?php if (($u['status'] ?? 'active') === 'active'): ?>
                                                <a href="?action=suspend_user&id=<?= $u['user_id'] ?>" class="btn btn-sm btn-outline-warning">Suspend</a>
                                            <?php else: ?>
                                                <a href="?action=activate_user&id=<?= $u['user_id'] ?>" class="btn btn-sm btn-outline-success">Activate</a>
                                            <?php endif; ?>
                                            <a href="?action=delete_user&id=<?= $u['user_id'] ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this user?')">Delete</a>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Transactions tab -->
        <div class="tab-pane fade" id="tab-transactions">
            <div class="bg-white border rounded-3 p-3 shadow-sm">
                <h5 class="fw-semibold mb-3">Transaction History</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Item</th>
                                <th>Buyer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td class="fw-medium">#<?= str_pad($t['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= htmlspecialchars($t['listing_title']) ?></td>
                                    <td><?= htmlspecialchars($t['buyer_name']) ?></td>
                                    <td class="fw-semibold">R<?= number_format($t['total_amount'], 2) ?></td>
                                    <td>
                                        <?php $tc = ['paid'=>'success','pending'=>'warning','cancelled'=>'danger']; ?>
                                        <span class="badge bg-<?= $tc[$t['status']] ?? 'secondary' ?>"><?= ucfirst($t['status']) ?></span>
                                    </td>
                                    <td><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
