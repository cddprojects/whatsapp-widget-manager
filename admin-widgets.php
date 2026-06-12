<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$query = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = search_all_widgets(['q' => $query, 'page' => $page, 'per_page' => 20]);

$pageTitle = 'Widgets';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Super admin</p>
    <h1>All widgets</h1>
    <p>Browse widgets across every client account.</p>
</section>

<section class="settings-card">
    <form class="admin-filter-bar" method="get">
        <label class="search-field span-2">
            <span>Search</span>
            <input type="search" name="q" value="<?= e($query) ?>" placeholder="Search widget, domain, client name, or email…">
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Search</button>
            <a class="btn btn-light" href="admin-widgets.php">Reset</a>
        </div>
    </form>

    <p class="results-meta"><?= (int) $result['total'] ?> widget<?= $result['total'] === 1 ? '' : 's' ?> found</p>

    <?php if (!$result['rows']): ?>
        <div class="empty-state compact-empty"><p>No widgets found.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th>Widget</th>
                        <th>Client</th>
                        <th>Domain</th>
                        <th>WhatsApp</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['rows'] as $widget): ?>
                        <tr>
                            <td><strong><?= e($widget['widget_name']) ?></strong></td>
                            <td>
                                <strong><?= e($widget['owner_name']) ?></strong>
                                <small><?= e($widget['owner_email']) ?></small>
                            </td>
                            <td><?= e($widget['website_domain']) ?></td>
                            <td><?= format_whatsapp_display($widget) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $widget['updated_at']))) ?></td>
                            <td>
                                <div class="action-list">
                                    <a class="btn btn-small btn-light" href="admin-client-detail.php?id=<?= (int) $widget['user_id'] ?>">View Client</a>
                                    <a class="btn btn-small btn-primary-soft" href="edit-widget.php?id=<?= (int) $widget['id'] ?>">Full Edit</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="pagination-bar">
                <?php if ($page > 1): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['q' => $query, 'page' => $page - 1])) ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?= (int) $page ?> of <?= (int) $result['pages'] ?></span>
                <?php if ($page < $result['pages']): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['q' => $query, 'page' => $page + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
