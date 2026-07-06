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

$query = http_build_query([
    'scope' => 'admin',
    'client_id' => (int) $widget['user_id'],
    'widget_id' => $widgetId,
    'q' => trim((string) ($_GET['q'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
]);

redirect('export-leads.php?' . $query);
