<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$widgetId = (int) ($_GET['widget_id'] ?? 0);
$widget = find_widget_by_id($widgetId);
if (!$widget) {
    http_response_code(404);
    exit(t('error.widget_not_found'));
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

$exportUrl = 'export-widget-leads.php?widget_id=' . (int) $widgetId
    . '&q=' . urlencode($query)
    . '&date_from=' . urlencode($dateFrom)
    . '&date_to=' . urlencode($dateTo);

$pageTitle = t('page.widget_leads');
$pageScripts = ['assets/js/leads.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.lead_capture')) ?></p>
    <h1><?= e(t('heading.leads_for', ['name' => $widget['widget_name']])) ?></h1>
    <p><?= e(t('desc.widget_leads')) ?></p>
</section>

<section
    class="settings-card"
    data-leads-page
    data-widget-id="<?= (int) $widgetId ?>"
    data-csrf-token="<?= e(csrf_token()) ?>"
    data-total-leads="<?= (int) $result['total'] ?>"
>
    <form class="admin-filter-bar" method="get">
        <input type="hidden" name="widget_id" value="<?= (int) $widgetId ?>">
        <label class="search-field span-2">
            <span><?= e(t('filter.search')) ?></span>
            <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(t('filter.placeholder_leads')) ?>">
        </label>
        <label>
            <span><?= e(t('filter.from_date')) ?></span>
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
        </label>
        <label>
            <span><?= e(t('filter.to_date')) ?></span>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>">
        </label>
        <div class="form-actions lead-filter-actions">
            <button type="submit" class="btn btn-primary"><?= e(t('button.filter')) ?></button>
            <?php if ($result['total'] > 0): ?>
                <a class="btn btn-light" href="<?= e($exportUrl) ?>"><?= e(t('button.export_csv')) ?></a>
            <?php else: ?>
                <span class="btn btn-light is-disabled" aria-disabled="true"><?= e(t('button.export_csv')) ?></span>
            <?php endif; ?>
            <button type="button" class="btn btn-danger-soft" data-delete-selected-leads disabled><?= e(t('button.delete_selected')) ?></button>
            <span class="lead-selected-count" data-selected-lead-count hidden><?= e(t('lead.selected_count', ['count' => '0'])) ?></span>
        </div>
    </form>

    <p class="results-meta" data-leads-results-meta><?= e(t($result['total'] === 1 ? 'results.leads_found_one' : 'results.leads_found_other', ['count' => (string) $result['total']])) ?></p>

    <?php if (!$result['rows']): ?>
        <div class="empty-state compact-empty" data-leads-empty-state>
            <p><strong><?= e(t('empty.no_leads_found')) ?></strong></p>
            <p><?= e(t('empty.no_leads_subtitle')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table lead-table">
                <thead>
                    <tr>
                        <th class="col-select">
                            <label class="lead-select-all-label">
                                <input type="checkbox" data-select-all-leads aria-label="<?= e(t('table.select_all_aria')) ?>">
                                <span><?= e(t('table.select_all')) ?></span>
                            </label>
                        </th>
                        <th><?= e(t('table.visitor_phone')) ?></th>
                        <th><?= e(t('table.source_domain')) ?></th>
                        <th><?= e(t('table.source_url')) ?></th>
                        <th><?= e(t('table.page_title')) ?></th>
                        <th><?= e(t('table.created')) ?></th>
                        <th><?= e(t('table.widget')) ?></th>
                        <th><?= e(t('table.client_owner')) ?></th>
                        <th class="col-actions"><?= e(t('table.actions')) ?></th>
                    </tr>
                </thead>
                <tbody data-leads-table-body>
                    <?php foreach ($result['rows'] as $lead): ?>
                        <?php
                        $maskedPhone = mask_lead_phone((string) ($lead['visitor_phone'] ?: $lead['visitor_full_phone']));
                        ?>
                        <tr data-lead-row data-lead-id="<?= (int) $lead['id'] ?>">
                            <td class="col-select">
                                <input type="checkbox" class="ctcw-lead-select" value="<?= (int) $lead['id'] ?>" aria-label="<?= e(t('lead.select_row_aria', ['phone' => $maskedPhone])) ?>">
                            </td>
                            <td><strong><?= e($lead['visitor_phone']) ?></strong><small><?= e($lead['visitor_full_phone']) ?></small></td>
                            <td><?= e((string) ($lead['source_domain'] ?? '')) ?></td>
                            <td><small><?= e((string) ($lead['source_url'] ?? '')) ?></small></td>
                            <td><?= e((string) ($lead['page_title'] ?? '')) ?></td>
                            <td><?= e(format_datetime($lead['created_at'] ?? null, '')) ?></td>
                            <td><?= e($lead['widget_name']) ?></td>
                            <td><strong><?= e($lead['owner_name']) ?></strong><small><?= e($lead['owner_email']) ?></small></td>
                            <td class="col-actions">
                                <button
                                    type="button"
                                    class="btn btn-danger-soft btn-compact"
                                    data-delete-lead
                                    data-lead-id="<?= (int) $lead['id'] ?>"
                                    data-lead-phone="<?= e($maskedPhone) ?>"
                                ><?= e(t('button.delete')) ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="pagination-bar">
                <?php if ($page > 1): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['widget_id' => $widgetId, 'q' => $query, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $page - 1])) ?>"><?= e(t('pagination.previous')) ?></a>
                <?php endif; ?>
                <span><?= e(t('pagination.page_of', ['page' => (string) $page, 'pages' => (string) $result['pages']])) ?></span>
                <?php if ($page < $result['pages']): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['widget_id' => $widgetId, 'q' => $query, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $page + 1])) ?>"><?= e(t('pagination.next')) ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="ctcw-lead-modal" data-lead-single-modal hidden>
        <div class="ctcw-lead-modal-backdrop" data-lead-modal-close></div>
        <div class="ctcw-lead-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ctcwLeadSingleModalTitle">
            <h3 id="ctcwLeadSingleModalTitle"><?= e(t('lead.delete_title')) ?></h3>
            <p data-lead-single-modal-message><?= e(t('lead.delete_body')) ?></p>
            <div class="form-actions">
                <button type="button" class="btn btn-light" data-lead-modal-cancel><?= e(t('button.cancel')) ?></button>
                <button type="button" class="btn btn-danger-soft" data-lead-single-modal-confirm><?= e(t('lead.delete_button')) ?></button>
            </div>
        </div>
    </div>

    <div class="ctcw-lead-modal" data-lead-bulk-modal hidden>
        <div class="ctcw-lead-modal-backdrop" data-lead-modal-close></div>
        <div class="ctcw-lead-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ctcwLeadBulkModalTitle">
            <h3 id="ctcwLeadBulkModalTitle"><?= e(t('lead.bulk_delete_title')) ?></h3>
            <p data-lead-bulk-modal-message></p>
            <div class="form-actions">
                <button type="button" class="btn btn-light" data-lead-modal-cancel><?= e(t('button.cancel')) ?></button>
                <button type="button" class="btn btn-danger-soft" data-lead-bulk-modal-confirm><?= e(t('button.delete_selected')) ?></button>
            </div>
        </div>
    </div>

    <div class="ctcw-lead-toast" data-lead-toast hidden role="status" aria-live="polite"></div>
</section>

<div class="form-actions">
    <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $widget['user_id'] ?>"><?= e(t('button.back_to_client')) ?></a>
    <a class="btn btn-light" href="edit-widget.php?id=<?= (int) $widgetId ?>"><?= e(t('button.edit_widget')) ?></a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
