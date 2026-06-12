<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$clientId = (int) ($_GET['id'] ?? 0);
$client = find_client_user($clientId);
if (!$client) {
    http_response_code(404);
    exit('Client not found.');
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

$pageTitle = 'Reset Client Password';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Client account</p>
    <h1>Reset password</h1>
    <p>Generate a temporary password for <?= e($client['name']) ?>.</p>
</section>

<section class="settings-card">
    <?php if ($generatedPassword !== null): ?>
        <div class="alert alert-warning">
            Copy this password now. It will not be shown again.
        </div>
        <div class="temp-password-box">
            <span class="meta-label">Temporary password</span>
            <code><?= e($generatedPassword) ?></code>
        </div>
        <div class="form-actions">
            <a class="btn btn-primary" href="admin-client-detail.php?id=<?= (int) $clientId ?>">Back to client</a>
        </div>
    <?php else: ?>
        <p>This will generate a new temporary password and invalidate the client’s current password.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Generate temporary password</button>
                <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $clientId ?>">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
