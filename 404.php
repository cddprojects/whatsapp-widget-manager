<?php
declare(strict_types=1);

$pageTitle = 'Page Not Found';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="app-shell">
<main class="container">
    <section class="settings-card">
        <h1>Page Not Found</h1>
        <p>The page you requested does not exist.</p>
        <a class="btn btn-primary" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Go to Login</a>
    </section>
</main>
</body>
</html>
