<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$errors = [];
$name = '';
$email = '';
$status = USER_STATUS_ACTIVE;
$password = '';
$confirmPassword = '';

if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $status = (string) ($_POST['status'] ?? USER_STATUS_ACTIVE);
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '') {
        $errors[] = t('validation.client_name_required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('validation.email_required');
    }
    if (!in_array($status, [USER_STATUS_ACTIVE, USER_STATUS_DISABLED], true)) {
        $status = USER_STATUS_ACTIVE;
    }

    $errors = array_merge($errors, validate_client_password($password, $confirmPassword));

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = t('validation.email_already_registered');
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
                'status' => $status,
            ]);
            $clientId = (int) db()->lastInsertId();
            flash('success', t('flash.client_created'));
            $_SESSION['created_client_password'] = $password;
            $_SESSION['created_client_email'] = $email;
            redirect('admin-client-detail.php?id=' . $clientId);
        }
    }
}

$pageTitle = t('page.add_client');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.super_admin')) ?></p>
    <h1><?= e(t('heading.add_client')) ?></h1>
    <p><?= e(t('desc.add_client')) ?></p>
</section>

<section class="settings-card">
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="admin-form-grid" data-client-create-form novalidate>
        <?= csrf_field() ?>
        <label>
            <span><?= e(t('label.client_name')) ?></span>
            <input type="text" name="name" value="<?= e($name) ?>" required>
        </label>
        <label>
            <span><?= e(t('label.client_email')) ?></span>
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label class="span-full">
            <span><?= e(t('label.password')) ?></span>
            <div class="password-field-row">
                <input
                    type="password"
                    name="password"
                    id="clientPassword"
                    value="<?= e($password) ?>"
                    minlength="8"
                    autocomplete="new-password"
                    data-client-password
                    required
                >
                <button type="button" class="btn btn-light btn-compact" data-toggle-client-password><?= e(t('button.show')) ?></button>
                <button type="button" class="btn btn-light" data-generate-client-password><?= e(t('button.generate')) ?></button>
            </div>
            <p class="field-helper"><?= e(t('helper.password_requirements')) ?></p>
            <div class="password-strength" data-password-strength aria-live="polite">
                <span class="password-strength-label"><?= e(t('password.strength_label')) ?> <strong data-strength-text><?= e(t('password.weak')) ?></strong></span>
                <span class="password-strength-bar" data-strength-bar aria-hidden="true"></span>
            </div>
            <p class="field-error" data-password-match-error hidden><?= e(t('password.match_error')) ?></p>
        </label>
        <label class="span-full">
            <span><?= e(t('label.confirm_password')) ?></span>
            <input
                type="password"
                name="confirm_password"
                id="clientConfirmPassword"
                value="<?= e($confirmPassword) ?>"
                minlength="8"
                autocomplete="new-password"
                data-client-confirm-password
                required
            >
        </label>
        <label>
            <span><?= e(t('meta.status')) ?></span>
            <select name="status">
                <option value="<?= e(USER_STATUS_ACTIVE) ?>"<?= selected($status, USER_STATUS_ACTIVE) ?>><?= e(t('status.active')) ?></option>
                <option value="<?= e(USER_STATUS_DISABLED) ?>"<?= selected($status, USER_STATUS_DISABLED) ?>><?= e(t('status.disabled')) ?></option>
            </select>
        </label>
        <div class="form-actions span-full">
            <button type="submit" class="btn btn-primary"><?= e(t('button.create_client')) ?></button>
            <a class="btn btn-light" href="<?= e(app_url('admin-clients.php')) ?>"><?= e(t('button.cancel')) ?></a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
