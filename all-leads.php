<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$query = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$widgetFilterId = (int) ($_GET['widget_id'] ?? 0);
$clientFilterId = (int) ($_GET['client_id'] ?? 0);
$sort = trim((string) ($_GET['sort'] ?? 'newest'));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = normalize_lead_list_per_page($_GET['per_page'] ?? null);

$allowedSorts = ['newest', 'oldest', 'phone_az', 'phone_za', 'client_az', 'client_za'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}

$result = search_client_leads([
    'client_id' => $clientFilterId,
    'widget_id' => $widgetFilterId,
    'q' => $query,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'sort' => $sort,
    'page' => $page,
    'per_page' => $perPage,
]);
$page = (int) $result['page'];
$perPage = (int) $result['per_page'];

$clientOptions = db()->query("SELECT id, name, email FROM users WHERE role = '" . ROLE_CLIENT . "' ORDER BY name ASC")->fetchAll() ?: [];

$exportUrl = 'export-leads.php?scope=all'
    . '&client_id=' . (int) $clientFilterId
    . '&widget_id=' . (int) $widgetFilterId
    . '&sort=' . urlencode($sort)
    . '&q=' . urlencode($query)
    . '&date_from=' . urlencode($dateFrom)
    . '&date_to=' . urlencode($dateTo);

$pageTitle = t('page.all_leads');
$pageScripts = ['assets/js/leads.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.lead_capture')) ?></p>
    <h1><?= e(t('heading.all_leads')) ?></h1>
    <p><?= e(t('desc.all_leads')) ?></p>
</section>

<?php
$leadPageMode = 'all_leads';
$formAction = 'all-leads.php';
$showClientOwnerColumn = false;
$showClientColumn = true;
$showRecycleMeta = false;
$deleteSingleLabel = t('lead.move_to_recycle_bin');
$deleteBulkLabel = t('lead.move_selected_to_recycle_bin');
$emptyMessage = t('empty.no_active_leads');
$csrfToken = csrf_token();
$widgetOptions = widgets_for_admin_filter($clientFilterId > 0 ? $clientFilterId : null);
require __DIR__ . '/includes/leads-page.php';
?>

<?php require __DIR__ . '/includes/footer.php'; ?>
