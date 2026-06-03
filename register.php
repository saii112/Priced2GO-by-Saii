<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (!$username || !$email || !$password || !$confirm) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // check if email already exists
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'buyer')");
            $stmt->execute([$username, $email, $hash]);
            $success = 'Account created! <a href="login.php">Login here</a>.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="auth-card text-center">
                <i class="bi bi-person-plus fs-1 text-primary mb-3 d-block"></i>
                <h2 class="fw-bold mb-1">Create Your Account</h2>
                <p class="text-muted mb-4">Join the Priced2GO marketplace</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger text-start"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success text-start"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium">Username</label>
                        <input type="text" name="username" class="form-control"
                               placeholder="Choose a username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium">Email Address</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="your@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium">Password</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Min. 6 characters" required>
                    </div>
                    <div class="mb-4 text-start">
                        <label class="form-label fw-medium">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="Repeat your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        Create Account
                    </button>
                </form>

                <p class="mt-3 text-muted small">
                    Already have an account? <a href="login.php" class="text-primary">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
