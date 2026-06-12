<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_superadmin();
$stats = dashboard_summary_stats();
$recentClients = recent_clients(5);
$recentWidgets = recent_widgets(5);

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="dashboard-hero">
    <div>
        <p class="eyebrow">Super admin</p>
        <h1>Welcome back, <?= e($user['name']) ?></h1>
        <p>Manage client accounts, widgets, and WhatsApp configurations from one place.</p>
    </div>
    <a class="btn btn-primary" href="admin-clients.php">View All Clients</a>
</section>

<section class="summary-grid">
    <article class="summary-card">
        <span class="summary-label">Total clients</span>
        <strong><?= (int) $stats['total_clients'] ?></strong>
    </article>
    <article class="summary-card">
        <span class="summary-label">Active clients</span>
        <strong><?= (int) $stats['active_clients'] ?></strong>
    </article>
    <article class="summary-card">
        <span class="summary-label">Disabled clients</span>
        <strong><?= (int) $stats['disabled_clients'] ?></strong>
    </article>
    <article class="summary-card">
        <span class="summary-label">Total widgets</span>
        <strong><?= (int) $stats['total_widgets'] ?></strong>
    </article>
</section>

<section class="settings-card">
    <div class="card-header-row">
        <div>
            <h2>Recent clients</h2>
            <p>Latest client accounts registered in the system.</p>
        </div>
        <a class="btn btn-light" href="admin-clients.php">View all</a>
    </div>
    <?php if (!$recentClients): ?>
        <div class="empty-state compact-empty">
            <p>No client accounts yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Widgets</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentClients as $client): ?>
                        <tr>
                            <td>
                                <strong><?= e($client['name']) ?></strong>
                                <small><?= e($client['email']) ?></small>
                            </td>
                            <td><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(ucfirst((string) $client['status'])) ?></span></td>
                            <td><?= (int) $client['widget_count'] ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $client['created_at']))) ?></td>
                            <td><a class="btn btn-small btn-light" href="admin-client-detail.php?id=<?= (int) $client['id'] ?>">View Client</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="settings-card">
    <div class="card-header-row">
        <div>
            <h2>Recent widgets</h2>
            <p>Recently updated widgets across all clients.</p>
        </div>
        <a class="btn btn-light" href="admin-widgets.php">View all</a>
    </div>
    <?php if (!$recentWidgets): ?>
        <div class="empty-state compact-empty">
            <p>No widgets yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th>Widget</th>
                        <th>Client</th>
                        <th>Domain</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentWidgets as $widget): ?>
                        <tr>
                            <td><strong><?= e($widget['widget_name']) ?></strong></td>
                            <td><?= e($widget['owner_name']) ?></td>
                            <td><?= e($widget['website_domain']) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $widget['updated_at']))) ?></td>
                            <td><a class="btn btn-small btn-light" href="edit-widget.php?id=<?= (int) $widget['id'] ?>">Full Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
