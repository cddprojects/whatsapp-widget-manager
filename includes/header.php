<?php
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$isSuperAdmin = is_superadmin();
$isClient = is_client();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body class="app-shell">
<header class="topbar">
    <a class="brand" href="<?= $isSuperAdmin ? 'dashboard.php' : 'client-dashboard.php' ?>">
        <span class="brand-mark">C</span>
        <span><?= e(APP_NAME) ?></span>
    </a>
    <?php if ($user): ?>
        <nav class="topnav" aria-label="Main navigation">
            <?php if ($isSuperAdmin): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="admin-clients.php">Clients</a>
                <a href="admin-widgets.php">Widgets</a>
                <a href="create-widget.php">Create Widget</a>
                <a href="admin-settings.php">Settings</a>
            <?php elseif ($isClient): ?>
                <a href="client-dashboard.php">My WhatsApp Number</a>
                <?php
                $previewStmt = db()->prepare('SELECT id FROM widgets WHERE user_id = :user_id ORDER BY id ASC LIMIT 1');
                $previewStmt->execute(['user_id' => (int) $user['id']]);
                $previewWidgetId = (int) ($previewStmt->fetchColumn() ?: 0);
                if ($previewWidgetId > 0):
                ?>
                    <a href="widget-preview.php?id=<?= $previewWidgetId ?>">Preview</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
