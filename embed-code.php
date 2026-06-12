<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

if (is_client()) {
    redirect('client-dashboard.php');
}

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = require_widget_access($widgetId);

$code = embed_code($widget);
$pageTitle = 'Embed Code';
require __DIR__ . '/includes/header.php';
?>

<section class="settings-card embed-page">
    <div class="section-title">
        <span>&lt;/&gt;</span>
        <div>
            <h1>Embed code for <?= e($widget['widget_name']) ?></h1>
            <p>Paste this iframe before the closing <code>&lt;/body&gt;</code> tag of <?= e($widget['website_domain']) ?>.</p>
        </div>
    </div>

    <div class="embed-box">
        <div class="panel-heading">
            <h3>Copy iframe code</h3>
            <button type="button" class="btn btn-primary" data-copy-target="#embed-code">Copy code</button>
        </div>
        <div class="alert alert-warning">This iframe widget is locked to your registered domain. It will not work if copied to another website.</div>
        <textarea id="embed-code" readonly rows="6"><?= e($code) ?></textarea>
    </div>

    <div class="preview-frame-wrap large">
        <iframe src="widget-preview.php?id=<?= (int) $widget['id'] ?>" title="Widget preview" class="preview-frame"></iframe>
    </div>

    <div class="form-actions">
        <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $widget['user_id'] ?>">Back to client</a>
        <a class="btn btn-primary-soft" href="edit-widget.php?id=<?= (int) $widget['id'] ?>">Edit settings</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
