<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = require_widget_access($widgetId);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview - <?= e($widget['widget_name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="preview-body">
    <div class="preview-site">
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
        <?= embed_code($widget) ?>
    </div>
</body>
</html>
