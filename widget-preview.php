<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_login();

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = find_user_widget($widgetId, (int) $user['id']);
if (!$widget) {
    http_response_code(404);
    exit('Widget not found.');
}

$src = SYSTEM_BASE_URL . '/widget.php?id=' . rawurlencode((string) $widget['id']) . '&key=' . rawurlencode((string) $widget['public_key']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview - <?= e($widget['widget_name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="preview-body">
    <div class="preview-site">
        <div class="preview-banner">
            <div>
                <strong>Live preview</strong>
                <span><?= e($widget['widget_name']) ?> on <?= e($widget['website_domain']) ?></span>
            </div>
            <a href="edit-widget.php?id=<?= (int) $widget['id'] ?>" class="btn btn-small btn-light">Back to editor</a>
        </div>
        <section class="mock-hero">
            <p class="eyebrow">Example website</p>
            <h1>Sample landing page</h1>
            <p>This page shows how your WhatsApp floating widget appears on a real website.</p>
        </section>
        <section class="mock-content">
            <div></div>
            <div></div>
            <div></div>
        </section>
        <iframe
            src="<?= e($src) ?>"
            title="Click to chat widget"
            style="border:0; position:fixed; bottom:0; right:0; width:100%; height:100%; pointer-events:auto; z-index:999999;"
            allowtransparency="true"></iframe>
    </div>
</body>
</html>
