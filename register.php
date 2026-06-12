<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
redirect_if_authenticated();

$errors = [];
$name = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered.';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, password, role, status, password_changed_at)
                 VALUES (:name, :email, :password, :role, :status, NOW())'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => ROLE_CLIENT,
                'status' => USER_STATUS_ACTIVE,
            ]);
            login_user((int) db()->lastInsertId());
            flash('success', 'Welcome! Your account has been created.');
            $loggedIn = current_user();
            if ($loggedIn) {
                redirect_after_login($loggedIn);
            }
            redirect('client-dashboard.php');
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-card">
    <h1>Create your account</h1>
    <p>Register as a client to manage your WhatsApp numbers.</p>
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
            <span>Name</span>
            <input type="text" name="name" value="<?= e($name) ?>" required>
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" minlength="8" required>
        </label>
        <label>
            <span>Confirm password</span>
            <input type="password" name="password_confirm" minlength="8" required>
        </label>
        <button type="submit" class="btn btn-primary btn-full">Create account</button>
    </form>
    <p class="auth-link">Already have an account? <a href="login.php">Login</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
