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

redirect('admin-client-leads.php?client_id=' . (int) $widget['user_id'] . '&widget_id=' . $widgetId);
