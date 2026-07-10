<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$clientId = (int) ($_GET['id'] ?? 0);
$client = find_client_user($clientId);
if (!$client) {
    http_response_code(404);
    exit(t('error.client_not_found'));
}

$errors = [];
$name = (string) $client['name'];
$email = (string) $client['email'];
$status = (string) $client['status'];

if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $status = (string) ($_POST['status'] ?? USER_STATUS_ACTIVE);

    if ($name === '') {
        $errors[] = t('validation.name_required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('validation.email_required');
    }
    if (!in_array($status, [USER_STATUS_ACTIVE, USER_STATUS_DISABLED], true)) {
        $status = USER_STATUS_ACTIVE;
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $stmt->execute(['email' => $email, 'id' => $clientId]);
        if ($stmt->fetch()) {
            $errors[] = t('validation.email_in_use');
        } else {
            $stmt = db()->prepare(
                'UPDATE users SET name = :name, email = :email, status = :status
                 WHERE id = :id AND role = :role'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'status' => $status,
                'id' => $clientId,
                'role' => ROLE_CLIENT,
            ]);
            flash('success', t('flash.client_updated'));
            redirect('admin-client-detail.php?id=' . $clientId);
        }
    }
}

$pageTitle = t('page.edit_client');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.client_account')) ?></p>
    <h1><?= e(t('heading.edit_client')) ?></h1>
    <p><?= e(t('desc.edit_client')) ?></p>
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

    <form method="post" class="settings-form">
        <?= csrf_field() ?>
        <label>
            <span><?= e(t('label.name')) ?></span>
            <input type="text" name="name" value="<?= e($name) ?>" required>
        </label>
        <label>
            <span><?= e(t('label.email')) ?></span>
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label>
            <span><?= e(t('meta.status')) ?></span>
            <select name="status">
                <option value="active"<?= selected($status, 'active') ?>><?= e(t('status.active')) ?></option>
                <option value="disabled"<?= selected($status, 'disabled') ?>><?= e(t('status.disabled')) ?></option>
            </select>
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(t('button.save_changes')) ?></button>
            <a class="btn btn-light" href="<?= e(app_url('admin-client-detail.php', ['id' => (int) $clientId])) ?>"><?= e(t('button.cancel')) ?></a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
