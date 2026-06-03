<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current = basename($_SERVER['PHP_SELF']);
$inAdmin = str_contains($_SERVER['PHP_SELF'], '/admin/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Priced2GO – Your Trusted C2C Marketplace</title>
    <link rel="icon" type="image/svg+xml" href="<?= $inAdmin ? '../' : '' ?>favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $inAdmin ? '../' : '' ?>css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="<?= $inAdmin ? '../' : '' ?>index.php">
            <i class="bi bi-bag-heart-fill fs-4"></i> Priced2GO
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'index.php' ? 'active fw-medium text-primary' : '' ?>" href="<?= $inAdmin ? '../' : '' ?>index.php">Browse</a>
                </li>
                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'sell.php' ? 'active fw-medium text-primary' : '' ?>" href="<?= $inAdmin ? '../' : '' ?>sell.php">Sell Item</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'my_listings.php' ? 'active fw-medium text-primary' : '' ?>" href="<?= $inAdmin ? '../' : '' ?>my_listings.php">My Listings</a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-muted small"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="<?= $inAdmin ? '' : 'admin/' ?>index.php" class="btn btn-sm btn-outline-secondary">Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?= $inAdmin ? '../' : '' ?>logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                <?php else: ?>
                    <a href="<?= $inAdmin ? '../' : '' ?>login.php" class="btn btn-sm btn-outline-secondary">Login</a>
                    <a href="<?= $inAdmin ? '../' : '' ?>register.php" class="btn btn-sm btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main>
