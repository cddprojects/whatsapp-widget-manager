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

$file = $_FILES['phone_file'] ?? null;
if (!is_array($file)) {
    flash('error', 'Please choose a file to upload.');
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
}

$errors = validate_uploaded_phone_file($file);
if ($errors !== []) {
    flash('error', $errors[0]);
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
}

$tmpPath = (string) $file['tmp_name'];
$result = parse_phone_upload($tmpPath);
@unlink($tmpPath);

$update = build_phone_widget_update($result['numbers']);
if ($update === null) {
    flash('error', 'No valid numbers were found in the uploaded file.');
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
}

update_widget_phone_fields($widgetId, $update);
$activeCount = !empty($update['use_random_numbers'])
    ? count(decode_random_numbers($update['random_numbers_json'] ?? '[]'))
    : 1;
flash('success', 'Numbers uploaded successfully. ' . $activeCount . ' number' . ($activeCount === 1 ? ' is' : 's are') . ' now active.');
redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
