<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_client();

$clientId = (int) $user['id'];
$query = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$widgetFilterId = (int) ($_GET['widget_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = search_client_leads([
    'client_id' => $clientId,
    'widget_id' => $widgetFilterId,
    'q' => $query,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'page' => $page,
    'per_page' => 25,
]);

$exportUrl = 'export-leads.php?scope=client&widget_id=' . (int) $widgetFilterId
    . '&q=' . urlencode($query)
    . '&date_from=' . urlencode($dateFrom)
    . '&date_to=' . urlencode($dateTo);

$pageTitle = t('page.my_leads');
$pageScripts = ['assets/js/leads.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.lead_capture')) ?></p>
    <h1><?= e(t('heading.my_leads')) ?></h1>
    <p><?= e(t('desc.my_leads')) ?></p>
</section>

<section class="summary-grid lead-summary-grid">
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.today_title')) ?></span>
        <strong><?= (int) count_active_leads($clientId, true) ?></strong>
        <small><?= e(t('lead.today_scope_client')) ?></small>
    </article>
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.total_active_title')) ?></span>
        <strong><?= (int) count_active_leads($clientId, false) ?></strong>
        <small><?= e(t('lead.total_scope_client')) ?></small>
    </article>
</section>

<p class="lead-timezone-note dashboard-timezone-note"><?= e(t('lead.times_timezone_note')) ?></p>

<?php
$leadPageMode = 'client';
$clientFilterId = $clientId;
$widgetOptions = widgets_for_client_filter($clientId);
$clientOptions = [];
$formAction = 'client-leads.php';
$showClientOwnerColumn = false;
$showClientColumn = false;
$showRecycleMeta = false;
$deleteSingleLabel = t('button.delete');
$deleteBulkLabel = t('button.delete_selected');
$emptyMessage = t('empty.no_active_leads');
$csrfToken = csrf_token();
require __DIR__ . '/includes/leads-page.php';
?>

<?php if ($result['pages'] > 1): ?>
    <div class="pagination-bar">
        <?php if ($page > 1): ?>
            <a class="btn btn-light" href="?<?= e(http_build_query(['widget_id' => $widgetFilterId, 'q' => $query, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $page - 1])) ?>"><?= e(t('pagination.previous')) ?></a>
        <?php endif; ?>
        <span><?= e(t('pagination.page_of', ['page' => (string) $page, 'pages' => (string) $result['pages']])) ?></span>
        <?php if ($page < $result['pages']): ?>
            <a class="btn btn-light" href="?<?= e(http_build_query(['widget_id' => $widgetFilterId, 'q' => $query, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $page + 1])) ?>"><?= e(t('pagination.next')) ?></a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
