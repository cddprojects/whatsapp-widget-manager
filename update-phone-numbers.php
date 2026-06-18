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
    exit('Access denied.');
}

$data = sanitize_client_phone_manual_input($_POST);
if ($data === null) {
    flash('error', 'Please add at least one WhatsApp number.');
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
}

update_widget_phone_fields($widgetId, $data);
$activeCount = !empty($data['use_random_numbers'])
    ? count(decode_random_numbers($data['random_numbers_json'] ?? '[]'))
    : 1;
flash(
    'success',
    $activeCount > 1
        ? 'Numbers saved successfully. ' . $activeCount . ' numbers are now active.'
        : 'Number saved successfully. 1 number is now active.'
);
redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
