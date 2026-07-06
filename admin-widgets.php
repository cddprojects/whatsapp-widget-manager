<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$query = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

if (is_post() && ($_POST['action'] ?? '') === 'delete_widget') {
    verify_csrf();
    $widgetId = (int) ($_POST['widget_id'] ?? 0);
    $widget = find_widget_by_id($widgetId);
    if ($widget) {
        $stmt = db()->prepare('DELETE FROM widgets WHERE id = :id');
        $stmt->execute(['id' => $widgetId]);
        flash('success', t('flash.widget_deleted'));
    }
    $returnQuery = trim((string) ($_POST['return_q'] ?? ''));
    redirect('admin-widgets.php' . ($returnQuery !== '' ? '?q=' . urlencode($returnQuery) : ''));
}

$result = search_all_widgets(['q' => $query, 'page' => $page, 'per_page' => 20]);

$pageTitle = t('page.widgets');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading page-heading-row">
    <div>
        <p class="eyebrow"><?= e(t('eyebrow.super_admin')) ?></p>
        <h1><?= e(t('heading.all_widgets')) ?></h1>
        <p><?= e(t('desc.all_widgets')) ?></p>
    </div>
    <a class="btn btn-primary" href="create-widget.php"><?= e(t('button.create_widget')) ?></a>
</section>

<section class="settings-card table-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('heading.widget_directory')) ?></h2>
            <p class="results-meta inline-meta"><?= e(t($result['total'] === 1 ? 'results.widgets_found_one' : 'results.widgets_found_other', ['count' => (string) $result['total']])) ?></p>
        </div>
    </div>

    <form class="admin-filter-bar" method="get">
        <label class="search-field span-2">
            <span><?= e(t('filter.search')) ?></span>
            <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(t('filter.placeholder_widgets')) ?>">
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(t('button.search')) ?></button>
            <a class="btn btn-light" href="admin-widgets.php"><?= e(t('button.reset')) ?></a>
        </div>
    </form>

    <?php if (!$result['rows']): ?>
        <div class="empty-state compact-empty"><p><?= e(t('empty.no_widgets_found')) ?></p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th><?= e(t('table.widget')) ?></th>
                        <th><?= e(t('table.client')) ?></th>
                        <th><?= e(t('table.domain')) ?></th>
                        <th><?= e(t('table.whatsapp_destinations')) ?></th>
                        <th><?= e(t('table.widget_status')) ?></th>
                        <th title="<?= e(t('table.display_tooltip')) ?>"><?= e(t('table.display')) ?></th>
                        <th><?= e(t('table.updated')) ?></th>
                        <th class="col-actions"><?= e(t('table.actions')) ?></th>
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
                            <td><?php render_widget_destination_summary($widget); ?></td>
                            <td><?php render_widget_activation_status($widget, true); ?></td>
                            <td><?= feature_status_pill($widget['show_global'] ?? 0) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $widget['updated_at']))) ?></td>
                            <td class="col-actions">
                                <?php render_widget_action_menu($widget, ['show_delete' => true]); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="pagination-bar">
                <?php if ($page > 1): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['q' => $query, 'page' => $page - 1])) ?>"><?= e(t('pagination.previous')) ?></a>
                <?php endif; ?>
                <span><?= e(t('pagination.page_of', ['page' => (string) $page, 'pages' => (string) $result['pages']])) ?></span>
                <?php if ($page < $result['pages']): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['q' => $query, 'page' => $page + 1])) ?>"><?= e(t('pagination.next')) ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
