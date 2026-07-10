<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$clientId = (int) ($_GET['client_id'] ?? 0);
$client = find_client_user($clientId);
if (!$client) {
    http_response_code(404);
    exit(t('error.client_not_found'));
}

$query = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$widgetFilterId = (int) ($_GET['widget_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = normalize_lead_list_per_page($_GET['per_page'] ?? null);

$result = search_client_leads([
    'client_id' => $clientId,
    'widget_id' => $widgetFilterId,
    'q' => $query,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'page' => $page,
    'per_page' => $perPage,
]);
$page = (int) $result['page'];
$perPage = (int) $result['per_page'];

$exportUrl = app_url('export-leads.php', [
    'scope' => 'admin',
    'client_id' => (int) $clientId,
    'widget_id' => (int) $widgetFilterId,
    'q' => $query,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
]);

$pageTitle = t('page.client_leads');
$pageScripts = ['assets/js/leads.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.lead_capture')) ?></p>
    <h1><?= e(t('heading.leads_for_client', ['name' => $client['name']])) ?></h1>
    <p><?= e(t('desc.client_leads')) ?></p>
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
$leadPageMode = 'superadmin';
$clientFilterId = $clientId;
$widgetOptions = widgets_for_client_filter($clientId);
$clientOptions = [];
$formAction = app_url('admin-client-leads.php');
$showClientOwnerColumn = false;
$showClientColumn = false;
$showRecycleMeta = false;
$deleteSingleLabel = t('lead.move_to_recycle_bin');
$deleteBulkLabel = t('lead.move_selected_to_recycle_bin');
$emptyMessage = t('empty.no_active_leads');
$csrfToken = csrf_token();
require __DIR__ . '/includes/leads-page.php';
?>

<div class="form-actions">
    <a class="btn btn-light" href="<?= e(app_url('admin-client-detail.php', ['id' => (int) $clientId])) ?>"><?= e(t('button.back_to_client')) ?></a>
    <a class="btn btn-light" href="<?= e(app_url('lead-recycle-bin.php')) ?>"><?= e(t('nav.lead_recycle_bin')) ?></a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
