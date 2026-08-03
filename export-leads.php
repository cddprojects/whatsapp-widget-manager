<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$scope = trim((string) ($_GET['scope'] ?? ''));
$query = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$widgetFilterId = (int) ($_GET['widget_id'] ?? 0);
$sort = trim((string) ($_GET['sort'] ?? 'newest'));
$channelFilter = trim((string) ($_GET['channel'] ?? ''));
if ($channelFilter !== '' && normalize_widget_channel($channelFilter) === null) {
    $channelFilter = '';
}

if ($scope === 'client') {
    $user = require_client();
    $clientId = (int) $user['id'];
    $rows = client_leads_for_export([
        'client_id' => $clientId,
        'widget_id' => $widgetFilterId,
        'q' => $query,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'channel' => $channelFilter,
    ]);
    $filename = 'my-leads-' . app_lead_today_date_local() . '.csv';
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
        'channel' => $channelFilter,
    ]);
    $filename = 'client-leads-' . $clientId . '-' . app_lead_today_date_local() . '.csv';
} elseif ($scope === 'all') {
    require_superadmin();
    $clientId = (int) ($_GET['client_id'] ?? 0);
    $allowedSorts = ['newest', 'oldest', 'phone_az', 'phone_za', 'client_az', 'client_za'];
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'newest';
    }
    $rows = client_leads_for_export([
        'client_id' => $clientId,
        'widget_id' => $widgetFilterId,
        'q' => $query,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'sort' => $sort,
        'channel' => $channelFilter,
    ]);
    $filename = 'all-leads-' . app_lead_today_date_local() . '.csv';
} else {
    http_response_code(403);
    exit(t('error.access_denied'));
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

fputcsv($out, [
    t('lead.export_visitor_phone'),
    t('lead.export_widget_name'),
    t('lead.export_source_url'),
    t('lead.export_captured_at'),
    t('lead.export_channel'),
    t('lead.export_destination_name'),
    t('lead.export_destination_type'),
    t('lead.export_destination_snapshot'),
    t('lead.export_redirect_attempted_at'),
    t('lead.export_fallback_type'),
]);

foreach ($rows as $lead) {
    $channel = normalize_lead_channel($lead['channel'] ?? null);
    fputcsv($out, [
        format_lead_export_phone($lead),
        $lead['widget_name'] ?? '',
        $lead['source_url'] ?? '',
        format_lead_datetime_for_export($lead['created_at'] ?? null),
        $channel,
        $lead['destination_name'] ?? '',
        $lead['destination_type'] ?? '',
        $lead['destination_snapshot'] ?? '',
        format_lead_datetime_for_export($lead['redirect_attempted_at'] ?? null),
        $lead['fallback_type'] ?? '',
    ]);
}

fclose($out);
exit;
