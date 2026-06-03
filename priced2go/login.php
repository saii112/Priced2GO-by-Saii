<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="auth-card text-center">
                <i class="bi bi-box-arrow-in-right fs-1 text-primary mb-3 d-block"></i>
                <h2 class="fw-bold mb-1">Login to Priced2GO</h2>
                <p class="text-muted mb-4">Welcome back!</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger text-start"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium">Email Address</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="your@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-4 text-start">
                        <label class="form-label fw-medium">Password</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        Login
                    </button>
                </form>

                <p class="mt-3 text-muted small">
                    Don't have an account? <a href="register.php" class="text-primary">Sign up here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
