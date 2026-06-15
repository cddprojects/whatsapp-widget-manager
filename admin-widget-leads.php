<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$widgetId = (int) ($_GET['widget_id'] ?? 0);
$widget = find_widget_by_id($widgetId);
if (!$widget) {
    http_response_code(404);
    exit('Widget not found.');
}

$query = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = search_widget_leads($widgetId, [
    'q' => $query,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'page' => $page,
    'per_page' => 25,
]);

$pageTitle = 'Widget Leads';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Lead capture</p>
    <h1>Leads for <?= e($widget['widget_name']) ?></h1>
    <p>Visitor phone numbers captured from the greeting dialog.</p>
</section>

<section class="settings-card">
    <form class="admin-filter-bar" method="get">
        <input type="hidden" name="widget_id" value="<?= (int) $widgetId ?>">
        <label class="search-field span-2">
            <span>Search</span>
            <input type="search" name="q" value="<?= e($query) ?>" placeholder="Search phone, domain, URL, or page title…">
        </label>
        <label>
            <span>From date</span>
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
        </label>
        <label>
            <span>To date</span>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>">
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a class="btn btn-light" href="export-widget-leads.php?widget_id=<?= (int) $widgetId ?>&amp;q=<?= e(urlencode($query)) ?>&amp;date_from=<?= e(urlencode($dateFrom)) ?>&amp;date_to=<?= e(urlencode($dateTo)) ?>">Export CSV</a>
        </div>
    </form>

    <p class="results-meta"><?= (int) $result['total'] ?> lead<?= $result['total'] === 1 ? '' : 's' ?> found</p>

    <?php if (!$result['rows']): ?>
        <div class="empty-state compact-empty"><p>No leads captured yet.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th>Visitor phone</th>
                        <th>Source domain</th>
                        <th>Source URL</th>
                        <th>Page title</th>
                        <th>Created</th>
                        <th>Widget</th>
                        <th>Client owner</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['rows'] as $lead): ?>
                        <tr>
                            <td><strong><?= e($lead['visitor_phone']) ?></strong><small><?= e($lead['visitor_full_phone']) ?></small></td>
                            <td><?= e((string) ($lead['source_domain'] ?? '')) ?></td>
                            <td><small><?= e((string) ($lead['source_url'] ?? '')) ?></small></td>
                            <td><?= e((string) ($lead['page_title'] ?? '')) ?></td>
                            <td><?= e(format_datetime($lead['created_at'] ?? null, '')) ?></td>
                            <td><?= e($lead['widget_name']) ?></td>
                            <td><strong><?= e($lead['owner_name']) ?></strong><small><?= e($lead['owner_email']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="pagination-bar">
                <?php if ($page > 1): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['widget_id' => $widgetId, 'q' => $query, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $page - 1])) ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?= (int) $page ?> of <?= (int) $result['pages'] ?></span>
                <?php if ($page < $result['pages']): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['widget_id' => $widgetId, 'q' => $query, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $page + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<div class="form-actions">
    <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $widget['user_id'] ?>">Back to client</a>
    <a class="btn btn-light" href="edit-widget.php?id=<?= (int) $widgetId ?>">Edit widget</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
