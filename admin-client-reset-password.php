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

$generatedPassword = null;

if (is_post()) {
    verify_csrf();
    $generatedPassword = generate_temporary_password();
    $stmt = db()->prepare(
        'UPDATE users SET password = :password, password_changed_at = NOW()
         WHERE id = :id AND role = :role'
    );
    $stmt->execute([
        'password' => password_hash($generatedPassword, PASSWORD_DEFAULT),
        'id' => $clientId,
        'role' => ROLE_CLIENT,
    ]);
}

$pageTitle = t('page.reset_client_password');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.client_account')) ?></p>
    <h1><?= e(t('heading.reset_password')) ?></h1>
    <p><?= e(t('desc.reset_password', ['name' => $client['name']])) ?></p>
</section>

<section class="settings-card">
    <?php if ($generatedPassword !== null): ?>
        <div class="alert alert-warning">
            <?= e(t('alert.copy_password_now')) ?>
        </div>
        <div class="temp-password-box">
            <span class="meta-label"><?= e(t('meta.temporary_password')) ?></span>
            <code><?= e($generatedPassword) ?></code>
        </div>
        <div class="form-actions">
            <a class="btn btn-primary" href="admin-client-detail.php?id=<?= (int) $clientId ?>"><?= e(t('button.back_to_client')) ?></a>
        </div>
    <?php else: ?>
        <p><?= e(t('desc.reset_password_confirm')) ?></p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= e(t('button.generate_temporary_password')) ?></button>
                <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $clientId ?>"><?= e(t('button.cancel')) ?></a>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
