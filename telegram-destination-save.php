<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!is_post()) {
    http_response_code(405);
    exit('Method not allowed');
}

verify_csrf();

$user = current_user();
if (!$user) {
    http_response_code(401);
    exit('Unauthorized');
}

$widgetId = (int) ($_POST['widget_id'] ?? 0);
$action = trim((string) ($_POST['destination_action'] ?? 'save'));
$destinationId = (int) ($_POST['destination_id'] ?? 0);
$isSuperadmin = is_superadmin();
$isJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
    || ($_POST['response'] ?? '') === 'json';

$widget = find_accessible_widget($widgetId);

if (!$widget) {
    if ($isJson) {
        json_response(['success' => false, 'message' => t('error.widget_not_found')], 404);
    }
    flash('error', t('error.widget_not_found'));
    redirect($isSuperadmin ? 'admin-widgets.php' : 'client-dashboard.php');
}

if (!$isSuperadmin && !can_edit_phone_only($widget)) {
    if ($isJson) {
        json_response(['success' => false, 'message' => t('error.forbidden')], 403);
    }
    flash('error', t('error.forbidden'));
    redirect('client-dashboard.php');
}

// Clients may manage Telegram destinations only when Superadmin enabled Telegram.
if (!$isSuperadmin && !widget_channel_is_enabled($widgetId, WIDGET_CHANNEL_TELEGRAM, $widget)) {
    if ($isJson) {
        json_response(['success' => false, 'message' => t('telegram.disabled_by_admin')], 403);
    }
    flash('error', t('telegram.disabled_by_admin'));
    redirect('client-dashboard.php?widget_id=' . $widgetId);
}

$redirectTarget = $isSuperadmin
    ? 'edit-widget.php?id=' . $widgetId . '#destinations'
    : 'client-dashboard.php?widget_id=' . $widgetId . '&tab=telegram';

if ($action === 'delete' && $destinationId > 0) {
    $ok = soft_delete_channel_destination($widgetId, $destinationId);
    if ($isJson) {
        json_response(['success' => $ok, 'message' => $ok ? t('telegram.flash.deleted') : t('telegram.error.not_found')]);
    }
    flash($ok ? 'success' : 'error', $ok ? t('telegram.flash.deleted') : t('telegram.error.not_found'));
    redirect($redirectTarget);
}

if ($action === 'toggle' && $destinationId > 0) {
    $active = !empty($_POST['is_active']);
    $ok = set_channel_destination_active($widgetId, $destinationId, $active);
    if ($isJson) {
        json_response(['success' => $ok, 'message' => $ok ? t('telegram.flash.updated') : t('telegram.error.not_found')]);
    }
    flash($ok ? 'success' : 'error', $ok ? t('telegram.flash.updated') : t('telegram.error.not_found'));
    redirect($redirectTarget);
}

$result = save_telegram_destination($widgetId, [
    'display_name' => (string) ($_POST['display_name'] ?? ''),
    'destination_type' => (string) ($_POST['destination_type'] ?? ''),
    'destination_value' => (string) ($_POST['destination_value'] ?? ''),
    'bot_start_parameter' => (string) ($_POST['bot_start_parameter'] ?? ''),
    'is_active' => isset($_POST['is_active']) ? 1 : 0,
    'sort_order' => $_POST['sort_order'] ?? null,
], $destinationId > 0 ? $destinationId : null);

if ($isJson) {
    json_response([
        'success' => $result['ok'],
        'message' => $result['ok'] ? t('telegram.flash.saved') : ($result['errors'][0] ?? t('telegram.error.invalid_destination')),
        'field_errors' => $result['field_errors'] ?? [],
        'id' => $result['id'] ?? null,
    ], $result['ok'] ? 200 : 422);
}

if ($result['ok']) {
    flash('success', t('telegram.flash.saved'));
} else {
    flash('error', $result['errors'][0] ?? t('telegram.error.invalid_destination'));
}

redirect($redirectTarget);
