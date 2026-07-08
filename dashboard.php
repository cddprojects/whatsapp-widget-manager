<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_superadmin();
$stats = dashboard_summary_stats();

$pageTitle = t('page.dashboard');
require __DIR__ . '/includes/header.php';
?>

<section class="dashboard-hero page-heading-row">
    <div>
        <p class="eyebrow"><?= e(t('eyebrow.super_admin')) ?></p>
        <h1><?= e(t('heading.welcome_back', ['name' => $user['name']])) ?></h1>
        <p><?= e(t('desc.dashboard')) ?></p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-light" href="admin-client-create.php"><?= e(t('button.add_client')) ?></a>
        <a class="btn btn-primary" href="create-widget.php"><?= e(t('button.create_widget')) ?></a>
    </div>
</section>

<section class="summary-grid superadmin-lead-metrics">
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.today_title')) ?></span>
        <strong><?= (int) ($stats['today_leads'] ?? 0) ?></strong>
        <small><?= e(t('lead.today_scope_all')) ?></small>
    </article>
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.yesterday_title')) ?></span>
        <strong><?= (int) ($stats['yesterday_leads'] ?? 0) ?></strong>
        <small><?= e(t('lead.yesterday_scope_all')) ?></small>
    </article>
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.total_active_title')) ?></span>
        <strong><?= number_format((int) ($stats['total_active_leads'] ?? 0)) ?></strong>
        <small><?= e(t('lead.total_scope_all')) ?></small>
    </article>
</section>

<p class="lead-timezone-note dashboard-timezone-note"><?= e(t('lead.times_timezone_note')) ?></p>

<?php require __DIR__ . '/includes/footer.php'; ?>
