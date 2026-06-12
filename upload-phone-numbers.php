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

$defaultCountry = (string) ($_POST['default_country_code'] ?? '+60');
if (!array_key_exists($defaultCountry, country_codes())) {
    $defaultCountry = '+60';
}

$tmpPath = (string) $file['tmp_name'];
$result = parse_phone_upload($tmpPath, $defaultCountry);
@unlink($tmpPath);

$update = build_phone_widget_update($result['numbers']);
if ($update === null) {
    flash('error', 'No valid numbers were found in the uploaded file.');
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
}

update_widget_phone_fields($widgetId, $update);

$stats = $result['stats'];
flash(
    'success',
    'Import complete. Total rows: ' . (int) $stats['total_rows']
    . ', imported: ' . (int) $stats['imported']
    . ', skipped invalid: ' . (int) $stats['skipped_invalid']
    . ', duplicates: ' . (int) $stats['duplicates'] . '.'
);
redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
