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

$rows = widget_leads_for_export($widgetId, [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
]);

$filename = 'widget-leads-' . $widgetId . '-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['visitor_phone', 'visitor_full_phone', 'source_domain', 'source_url', 'page_title', 'created_at']);

foreach ($rows as $lead) {
    fputcsv($out, [
        $lead['visitor_phone'],
        $lead['visitor_full_phone'],
        $lead['source_domain'],
        $lead['source_url'],
        $lead['page_title'],
        $lead['created_at'],
    ]);
}

fclose($out);
exit;
