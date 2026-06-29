<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$pageTitle = t('page.settings');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.super_admin')) ?></p>
    <h1><?= e(t('heading.settings')) ?></h1>
    <p><?= e(t('desc.settings')) ?></p>
</section>

<section class="settings-card">
    <div class="profile-grid">
        <div><span class="meta-label"><?= e(t('meta.application')) ?></span><strong><?= e(APP_NAME) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.base_url')) ?></span><strong><?= e(SYSTEM_BASE_URL) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.timezone')) ?></span><strong><?= e(date_default_timezone_get()) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.database')) ?></span><strong><?= e(DB_NAME) ?></strong></div>
    </div>
    <div class="notice-box compact">
        <p><?= e(t('alert.settings_credentials')) ?></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
