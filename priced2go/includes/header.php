<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Priced2GO – Your Trusted C2C Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= str_contains($current, 'admin') ? '../' : '' ?>css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="<?= str_contains($current, 'admin') ? '../' : '' ?>index.php">
            <i class="bi bi-bag-heart-fill fs-4"></i> Priced2GO
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'index.php' ? 'active fw-medium text-primary' : '' ?>" href="<?= str_contains($current, 'admin') ? '../' : '' ?>index.php">Browse</a>
                </li>
                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'sell.php' ? 'active fw-medium text-primary' : '' ?>" href="<?= str_contains($current, 'admin') ? '../' : '' ?>sell.php">Sell Item</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'my_listings.php' ? 'active fw-medium text-primary' : '' ?>" href="<?= str_contains($current, 'admin') ? '../' : '' ?>my_listings.php">My Listings</a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-muted small"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="<?= str_contains($current, 'admin') ? '' : 'admin/' ?>index.php" class="btn btn-sm btn-outline-secondary">Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?= str_contains($current, 'admin') ? '../' : '' ?>logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                <?php else: ?>
                    <a href="<?= str_contains($current, 'admin') ? '../' : '' ?>login.php" class="btn btn-sm btn-outline-secondary">Login</a>
                    <a href="<?= str_contains($current, 'admin') ? '../' : '' ?>register.php" class="btn btn-sm btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main>
