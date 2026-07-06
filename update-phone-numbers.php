<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_client();

if (!is_post()) {
    redirect('client-dashboard.php');
}

verify_csrf();

$widgetId = (int) ($_POST['widget_id'] ?? 0);
$widget = find_user_widget($widgetId, (int) $user['id']);
if (!$widget) {
    http_response_code(403);
    exit(t('error.access_denied'));
}

$previousStatus = normalize_widget_activation_status((string) ($widget['widget_status'] ?? WIDGET_STATUS_SETUP_REQUIRED));
$data = sanitize_client_phone_manual_input($_POST, $widget, true);
if ($data === null) {
    flash('error', t('validation.invalid_phone_numbers'));
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
}

update_widget_phone_fields($widgetId, $data);
$updatedWidget = find_user_widget($widgetId, (int) $user['id']) ?? find_widget_by_id($widgetId);
$newStatus = $updatedWidget
    ? refresh_widget_destination_status($widgetId, $updatedWidget)
    : refresh_widget_destination_status($widgetId);

$activeCount = widget_has_valid_destinations($updatedWidget ?? [])
    ? count(widget_phone_list($updatedWidget ?? []))
    : 0;

if ($activeCount === 0) {
    flash('success', t('flash.widget_paused_no_numbers'));
} elseif (
    in_array($previousStatus, [WIDGET_STATUS_SETUP_REQUIRED, WIDGET_STATUS_PAUSED], true)
    && $newStatus === WIDGET_STATUS_ACTIVE
) {
    flash('success', t('flash.widget_active_after_number_save'));
} elseif ($activeCount > 1) {
    flash('success', t('flash.numbers_saved_other', ['count' => (string) $activeCount]));
} else {
    flash('success', t('flash.number_saved_one'));
}

redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
