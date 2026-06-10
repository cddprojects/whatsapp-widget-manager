<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
redirect_if_authenticated();

$errors = [];
$email = '';

if (is_post()) {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (login_is_limited()) {
        $errors[] = 'Too many failed login attempts. Please wait a few minutes and try again.';
    } else {
        $stmt = db()->prepare('SELECT id, password FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, (string) $user['password'])) {
            login_user((int) $user['id']);
            redirect('dashboard.php');
        }

        record_failed_login();
        $errors[] = 'Invalid email or password.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-card">
    <h1>Login</h1>
    <p>Access your Click To Chat Manager dashboard.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>
            <span>Email</span>
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary btn-full">Login</button>
    </form>
    <p class="auth-link">Need an account? <a href="register.php">Register</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
