<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$pageTitle = 'Settings';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Super admin</p>
    <h1>Settings</h1>
    <p>System configuration overview.</p>
</section>

<section class="settings-card">
    <div class="profile-grid">
        <div><span class="meta-label">Application</span><strong><?= e(APP_NAME) ?></strong></div>
        <div><span class="meta-label">Base URL</span><strong><?= e(SYSTEM_BASE_URL) ?></strong></div>
        <div><span class="meta-label">Timezone</span><strong><?= e(date_default_timezone_get()) ?></strong></div>
        <div><span class="meta-label">Database</span><strong><?= e(DB_NAME) ?></strong></div>
    </div>
    <div class="notice-box compact">
        <p>Super admin credentials are managed through <code>.env</code> and <code>seed-superadmin.php</code>. Do not store plain passwords in the repository.</p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
