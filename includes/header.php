<?php
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$isSuperAdmin = is_superadmin();
$isClient = is_client();
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <?php foreach ($pageStylesheets ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= e($stylesheet) ?>?v=<?= (int) filemtime(__DIR__ . '/../' . ltrim($stylesheet, '/')) ?>">
    <?php endforeach; ?>
</head>
<body class="app-shell">
<header class="topbar">
    <div class="topbar-left">
        <a class="brand" href="<?= $isSuperAdmin ? 'dashboard.php' : 'client-dashboard.php' ?>">
            <span class="brand-mark">C</span>
            <span><?= e(APP_NAME) ?></span>
        </a>
    </div>

    <?php if ($user): ?>
        <?php if ($isSuperAdmin): ?>
            <nav class="topnav-main" aria-label="Main navigation">
                <a class="<?= nav_link_class('dashboard.php') ?>" href="dashboard.php">Dashboard</a>
                <a class="<?= nav_link_class('admin-clients.php', ['admin-client-create.php', 'admin-client-detail.php', 'admin-client-edit.php', 'admin-client-reset-password.php']) ?>" href="admin-clients.php">Clients</a>
                <a class="<?= nav_link_class('admin-widgets.php', ['create-widget.php', 'edit-widget.php', 'edit-widget-phone.php', 'embed-code.php', 'admin-widget-leads.php']) ?>" href="admin-widgets.php">Widgets</a>
            </nav>
        <?php elseif ($isClient): ?>
            <nav class="topnav-main" aria-label="Main navigation">
                <a class="<?= nav_link_class('client-dashboard.php') ?>" href="client-dashboard.php">My WhatsApp Number</a>
            </nav>
        <?php endif; ?>

        <div class="topbar-right">
            <?php if ($isSuperAdmin): ?>
                <a class="icon-btn<?= nav_is_active('admin-settings.php') ? ' is-active' : '' ?>" href="admin-settings.php" title="Settings" aria-label="Settings">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8m8.94 5.06a1 1 0 0 0 .26-1.09l-1.2-3.46a1 1 0 0 0-.76-.65l-1.51-.34a7.06 7.06 0 0 0-.61-1.47l.86-1.38a1 1 0 0 0-.12-1.18l-2.45-2.45a1 1 0 0 0-1.18-.12l-1.38.86c-.47-.22-.96-.4-1.47-.61l-.34-1.51a1 1 0 0 0-.65-.76L13.03 2.8a1 1 0 0 0-1.09.26L9.48 5.26a1 1 0 0 0-.26 1.09l.34 1.51c-.51.21-1 .39-1.47.61l-1.38-.86a1 1 0 0 0-1.18.12L2.8 10.97a1 1 0 0 0-.12 1.18l.86 1.38c-.22.47-.4.96-.61 1.47l-1.51.34a1 1 0 0 0-.76.65l-1.2 3.46a1 1 0 0 0 .26 1.09l2.46 1.42a1 1 0 0 0 1.09-.26l1.51-.34c.47.22.96.4 1.47.61l.34 1.51a1 1 0 0 0 .65.76l3.46 1.2a1 1 0 0 0 1.09-.26l1.42-2.46a1 1 0 0 0-.26-1.09l-.34-1.51c.22-.47.4-.96.61-1.47l1.38.86a1 1 0 0 0 1.18-.12l2.45-2.45a1 1 0 0 0 .12-1.18l-.86-1.38c.22-.47.4-.96.61-1.47l1.51-.34a1 1 0 0 0 .76-.65l1.2-3.46Z"/></svg>
                </a>
            <?php endif; ?>

            <div class="user-menu" data-user-menu>
                <button type="button" class="user-menu-toggle" aria-haspopup="true" aria-expanded="false">
                    <span class="user-menu-name"><?= e($user['name']) ?></span>
                    <span class="user-menu-caret" aria-hidden="true">▾</span>
                </button>
                <div class="dropdown-menu" role="menu">
                    <?php if ($isSuperAdmin): ?>
                        <a role="menuitem" href="admin-settings.php">Account</a>
                    <?php else: ?>
                        <a role="menuitem" href="client-dashboard.php">Account</a>
                    <?php endif; ?>
                    <a role="menuitem" class="dropdown-danger" href="logout.php">Logout</a>
                </div>
            </div>

            <button type="button" class="mobile-nav-toggle" data-mobile-nav-toggle aria-label="Open menu">Menu</button>
        </div>
    <?php endif; ?>
</header>
<main class="container">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
