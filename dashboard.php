<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_superadmin();
$stats = dashboard_summary_stats();
$recentClients = recent_clients(5);
$recentWidgets = recent_widgets(5);

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

<section class="summary-grid">
    <article class="summary-card">
        <span class="summary-label"><?= e(t('summary.total_clients')) ?></span>
        <strong><?= (int) $stats['total_clients'] ?></strong>
    </article>
    <article class="summary-card">
        <span class="summary-label"><?= e(t('summary.active_clients')) ?></span>
        <strong><?= (int) $stats['active_clients'] ?></strong>
    </article>
    <article class="summary-card">
        <span class="summary-label"><?= e(t('summary.disabled_clients')) ?></span>
        <strong><?= (int) $stats['disabled_clients'] ?></strong>
    </article>
    <article class="summary-card">
        <span class="summary-label"><?= e(t('summary.total_widgets')) ?></span>
        <strong><?= (int) $stats['total_widgets'] ?></strong>
    </article>
</section>

<section class="summary-grid lead-summary-grid">
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.today_title')) ?></span>
        <strong><?= (int) ($stats['today_leads'] ?? 0) ?></strong>
        <small><?= e(t('lead.today_scope_all')) ?></small>
    </article>
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.total_active_title')) ?></span>
        <strong><?= number_format((int) ($stats['total_active_leads'] ?? 0)) ?></strong>
        <small><?= e(t('lead.total_scope_all')) ?></small>
    </article>
</section>

<section class="settings-card table-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('heading.recent_clients')) ?></h2>
            <p><?= e(t('desc.all_clients')) ?></p>
        </div>
        <a class="btn btn-light" href="admin-clients.php"><?= e(t('button.view_all')) ?></a>
    </div>
    <?php if (!$recentClients): ?>
        <div class="empty-state compact-empty">
            <p><?= e(t('empty.no_clients')) ?></p>
            <a class="btn btn-primary" href="admin-client-create.php"><?= e(t('button.add_client')) ?></a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th><?= e(t('table.client')) ?></th>
                        <th><?= e(t('table.status')) ?></th>
                        <th><?= e(t('table.widgets')) ?></th>
                        <th><?= e(t('table.created')) ?></th>
                        <th class="col-actions"><?= e(t('table.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentClients as $client): ?>
                        <tr>
                            <td>
                                <strong><?= e($client['name']) ?></strong>
                                <small><?= e($client['email']) ?></small>
                            </td>
                            <td><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(translate_user_status((string) $client['status'])) ?></span></td>
                            <td><?= (int) $client['widget_count'] ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $client['created_at']))) ?></td>
                            <td class="col-actions">
                                <a class="btn btn-small btn-primary" href="admin-client-detail.php?id=<?= (int) $client['id'] ?>"><?= e(t('button.manage')) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="settings-card table-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('heading.recent_widgets')) ?></h2>
            <p><?= e(t('desc.recent_widgets')) ?></p>
        </div>
        <a class="btn btn-light" href="admin-widgets.php"><?= e(t('button.view_all')) ?></a>
    </div>
    <?php if (!$recentWidgets): ?>
        <div class="empty-state compact-empty">
            <p><?= e(t('empty.no_widgets')) ?></p>
            <a class="btn btn-primary" href="create-widget.php"><?= e(t('button.create_widget')) ?></a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th><?= e(t('table.widget')) ?></th>
                        <th><?= e(t('table.client')) ?></th>
                        <th><?= e(t('table.domain')) ?></th>
                        <th><?= e(t('table.updated')) ?></th>
                        <th class="col-actions"><?= e(t('table.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentWidgets as $widget): ?>
                        <tr>
                            <td><strong><?= e($widget['widget_name']) ?></strong></td>
                            <td><?= e($widget['owner_name']) ?></td>
                            <td><?= e($widget['website_domain']) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $widget['updated_at']))) ?></td>
                            <td class="col-actions">
                                <?php render_widget_action_menu($widget, ['show_delete' => false]); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
