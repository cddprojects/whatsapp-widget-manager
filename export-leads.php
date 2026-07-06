<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$scope = trim((string) ($_GET['scope'] ?? ''));
$query = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$widgetFilterId = (int) ($_GET['widget_id'] ?? 0);

if ($scope === 'client') {
    $user = require_client();
    $clientId = (int) $user['id'];
    $rows = client_leads_for_export([
        'client_id' => $clientId,
        'widget_id' => $widgetFilterId,
        'q' => $query,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);
    $filename = 'my-leads-' . date('Y-m-d') . '.csv';
} elseif ($scope === 'admin') {
    require_superadmin();
    $clientId = (int) ($_GET['client_id'] ?? 0);
    $client = find_client_user($clientId);
    if (!$client) {
        http_response_code(404);
        exit(t('error.client_not_found'));
    }
    $rows = client_leads_for_export([
        'client_id' => $clientId,
        'widget_id' => $widgetFilterId,
        'q' => $query,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);
    $filename = 'client-leads-' . $clientId . '-' . date('Y-m-d') . '.csv';
} else {
    http_response_code(403);
    exit(t('error.access_denied'));
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['visitor_phone', 'visitor_full_phone', 'widget_name', 'source_domain', 'source_url', 'page_title', 'created_at']);

foreach ($rows as $lead) {
    fputcsv($out, [
        $lead['visitor_phone'],
        $lead['visitor_full_phone'],
        $lead['widget_name'],
        $lead['source_domain'],
        $lead['source_url'],
        $lead['page_title'],
        $lead['created_at'],
    ]);
}

fclose($out);
exit;
