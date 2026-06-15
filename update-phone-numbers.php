<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_client();

if (!is_post()) {
    redirect(is_client() ? 'client-dashboard.php' : 'dashboard.php');
}

verify_csrf();

$widgetId = (int) ($_POST['widget_id'] ?? 0);
$widget = find_user_widget($widgetId, (int) $user['id']);
if (!$widget) {
    http_response_code(403);
    exit('Access denied.');
}

if (($_POST['action'] ?? '') === 'remove_number') {
    $numbers = decode_random_numbers($widget['random_numbers_json'] ?? '[]');
    $index = (int) ($_POST['number_index'] ?? -1);
    if (!isset($numbers[$index])) {
        flash('error', 'Number not found.');
        redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
    }

    array_splice($numbers, $index, 1);
    $update = build_phone_widget_update($numbers);
    if ($update === null) {
        flash('error', 'At least one valid number is required.');
        redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
    }

    update_widget_phone_fields($widgetId, $update);
    flash('success', 'Number removed.');
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
}

$data = sanitize_client_phone_manual_input($_POST);
if ($data === null) {
    flash('error', 'Please enter at least one valid phone number.');
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
}

update_widget_phone_fields($widgetId, $data);
flash('success', count(decode_random_numbers($data['random_numbers_json'] ?? '[]')) > 1 || !empty($data['use_random_numbers'])
    ? 'WhatsApp numbers updated. Random rotation is enabled.'
    : 'WhatsApp number updated.');
redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=manual');
