<?php
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$isSuperAdmin = is_superadmin();
$isClient = is_client();
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$currentLocale = current_locale();
?>
<!doctype html>
<html lang="<?= e(html_lang()) ?>">
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
            <nav class="topnav-main" aria-label="<?= e(t('nav.main')) ?>">
                <a class="<?= nav_link_class('dashboard.php') ?>" href="dashboard.php"><?= e(t('nav.dashboard')) ?></a>
                <a class="<?= nav_link_class('admin-clients.php', ['admin-client-create.php', 'admin-client-detail.php', 'admin-client-edit.php', 'admin-client-reset-password.php', 'admin-client-delete.php', 'admin-client-leads.php']) ?>" href="admin-clients.php"><?= e(t('nav.clients')) ?></a>
                <a class="<?= nav_link_class('admin-widgets.php', ['create-widget.php', 'edit-widget.php', 'edit-widget-phone.php', 'embed-code.php', 'admin-widget-leads.php']) ?>" href="admin-widgets.php"><?= e(t('nav.widgets')) ?></a>
                <a class="<?= nav_link_class('lead-recycle-bin.php') ?>" href="lead-recycle-bin.php"><?= e(t('nav.lead_recycle_bin')) ?></a>
            </nav>
        <?php elseif ($isClient): ?>
            <nav class="topnav-main" aria-label="<?= e(t('nav.main')) ?>">
                <a class="<?= nav_link_class('client-dashboard.php') ?>" href="client-dashboard.php"><?= e(t('nav.my_whatsapp_number')) ?></a>
                <a class="<?= nav_link_class('client-leads.php') ?>" href="client-leads.php"><?= e(t('nav.my_leads')) ?></a>
            </nav>
        <?php endif; ?>

        <div class="topbar-right">
            <form method="post" action="set-language.php" class="language-switcher">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e(current_request_path()) ?>">
                <label class="sr-only" for="language-select"><?= e(t('nav.language')) ?></label>
                <select id="language-select" name="language" class="language-switcher-select">
                    <option value="en"<?= $currentLocale === 'en' ? ' selected' : '' ?>>English</option>
                    <option value="zh-CN"<?= $currentLocale === 'zh-CN' ? ' selected' : '' ?>>中文</option>
                </select>
            </form>

            <div class="user-menu" data-user-menu>
                <button type="button" class="user-menu-toggle" aria-haspopup="true" aria-expanded="false">
                    <span class="user-menu-name"><?= e($user['name']) ?></span>
                    <span class="user-menu-caret" aria-hidden="true">▾</span>
                </button>
                <div class="dropdown-menu" role="menu">
                    <?php if ($isSuperAdmin): ?>
                        <a role="menuitem" href="admin-settings.php"><?= e(t('nav.account_settings')) ?></a>
                    <?php else: ?>
                        <a role="menuitem" href="client-dashboard.php"><?= e(t('nav.account_settings')) ?></a>
                    <?php endif; ?>
                    <a role="menuitem" class="dropdown-danger" href="logout.php"><?= e(t('nav.logout')) ?></a>
                </div>
            </div>

            <button type="button" class="mobile-nav-toggle" data-mobile-nav-toggle aria-label="<?= e(t('nav.open_menu')) ?>"><?= e(t('nav.menu')) ?></button>
        </div>
    <?php else: ?>
        <div class="topbar-right">
            <form method="post" action="set-language.php" class="language-switcher">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e(current_request_path()) ?>">
                <label class="sr-only" for="language-select-guest"><?= e(t('nav.language')) ?></label>
                <select id="language-select-guest" name="language" class="language-switcher-select">
                    <option value="en"<?= $currentLocale === 'en' ? ' selected' : '' ?>>English</option>
                    <option value="zh-CN"<?= $currentLocale === 'zh-CN' ? ' selected' : '' ?>>中文</option>
                </select>
            </form>
        </div>
    <?php endif; ?>
</header>
<main class="container">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
