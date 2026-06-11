<?php
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
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
    <a class="brand" href="dashboard.php">
        <span class="brand-mark">C</span>
        <span><?= e(APP_NAME) ?></span>
    </a>
    <?php if ($user): ?>
        <nav class="topnav" aria-label="Main navigation">
            <a href="dashboard.php">Dashboard</a>
            <a href="create-widget.php">Create Widget</a>
            <a href="logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
