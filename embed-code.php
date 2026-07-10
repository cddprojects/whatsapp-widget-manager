<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = require_widget_access($widgetId);

$code = embed_code($widget);
$pageTitle = t('page.embed_code');
require __DIR__ . '/includes/header.php';
?>

<section class="settings-card embed-page">
    <div class="section-title">
        <span>&lt;/&gt;</span>
        <div>
            <h1><?= e(t('heading.embed_code_for', ['name' => $widget['widget_name']])) ?></h1>
            <p><?= e(t('desc.embed_code', ['domain' => $widget['website_domain']])) ?></p>
        </div>
    </div>

    <div class="embed-box">
        <div class="panel-heading">
            <h3><?= e(t('subheading.copy_iframe_code')) ?></h3>
            <button type="button" class="btn btn-primary" data-copy-target="#embed-code"><?= e(t('button.copy_code')) ?></button>
        </div>
        <div class="alert alert-warning"><?= e(t('embed.domain_locked_warning')) ?></div>
        <textarea id="embed-code" readonly rows="6"><?= e($code) ?></textarea>
    </div>

    <div class="preview-frame-wrap large">
        <iframe src="<?= e(app_url('widget-preview.php', ['id' => (int) $widget['id']])) ?>" title="<?= e(t('embed.preview_title')) ?>" class="preview-frame"></iframe>
    </div>

    <div class="form-actions">
        <a class="btn btn-light" href="<?= e(app_url('admin-client-detail.php', ['id' => (int) $widget['user_id']])) ?>"><?= e(t('button.back_to_client')) ?></a>
        <a class="btn btn-primary-soft" href="<?= e(app_url('edit-widget.php', ['id' => (int) $widget['id']])) ?>"><?= e(t('button.edit_settings')) ?></a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
