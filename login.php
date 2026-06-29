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
        $errors[] = t('login.error.too_many_attempts');
    } else {
        $stmt = db()->prepare('SELECT id, password, role, status FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && (string) $user['status'] === USER_STATUS_DISABLED) {
            $errors[] = t('login.error.account_disabled');
            record_failed_login();
        } elseif ($user && password_verify($password, (string) $user['password'])) {
            login_user((int) $user['id']);
            $loggedIn = current_user();
            if ($loggedIn) {
                redirect_after_login($loggedIn);
            }
        } else {
            record_failed_login();
            $errors[] = t('login.error.invalid_credentials');
        }
    }
}

$pageTitle = t('page.login');
require __DIR__ . '/includes/header.php';
?>

<div class="auth-card">
    <h1><?= e(t('login.title')) ?></h1>
    <p><?= e(t('login.subtitle')) ?></p>
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
            <span><?= e(t('label.email')) ?></span>
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label>
            <span><?= e(t('label.password')) ?></span>
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary btn-full"><?= e(t('button.login')) ?></button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
