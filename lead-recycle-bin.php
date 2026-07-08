<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$query = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$widgetFilterId = (int) ($_GET['widget_id'] ?? 0);
$clientFilterId = (int) ($_GET['client_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = normalize_lead_list_per_page($_GET['per_page'] ?? null);

$result = search_client_leads([
    'client_id' => $clientFilterId,
    'widget_id' => $widgetFilterId,
    'q' => $query,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'recycle_bin' => true,
    'page' => $page,
    'per_page' => $perPage,
]);
$page = (int) $result['page'];
$perPage = (int) $result['per_page'];

$clientOptions = db()->query("SELECT id, name, email FROM users WHERE role = '" . ROLE_CLIENT . "' ORDER BY name ASC")->fetchAll() ?: [];

$pageTitle = t('page.lead_recycle_bin');
$pageScripts = ['assets/js/leads.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading lead-recycle-bin-heading">
    <p class="eyebrow"><?= e(t('eyebrow.lead_capture')) ?></p>
    <h1><?= e(t('heading.lead_recycle_bin')) ?></h1>
    <p><?= e(t('desc.lead_recycle_bin')) ?></p>
</section>

<?php
$leadPageMode = 'recycle_bin';
$formAction = 'lead-recycle-bin.php';
$showClientOwnerColumn = false;
$showClientColumn = true;
$showRecycleMeta = true;
$deleteSingleLabel = t('button.delete');
$deleteBulkLabel = t('button.delete_selected');
$emptyMessage = t('empty.no_recycled_leads');
$csrfToken = csrf_token();
$exportUrl = '';
$widgetOptions = widgets_for_admin_filter($clientFilterId > 0 ? $clientFilterId : null);
require __DIR__ . '/includes/leads-page.php';
?>

<?php require __DIR__ . '/includes/footer.php'; ?>
