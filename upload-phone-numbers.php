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

$replaceExisting = isset($_POST['replace_existing']) && (string) $_POST['replace_existing'] === '1';

$tmpPath = (string) $file['tmp_name'];
$result = parse_phone_upload($tmpPath);
@unlink($tmpPath);

$uploadedNumbers = $result['numbers'];
if ($uploadedNumbers === []) {
    flash('error', 'No valid phone numbers found. Your current active numbers were not changed.');
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
}

if ($replaceExisting) {
    $finalNumbers = remove_duplicate_phone_numbers($uploadedNumbers);
    $addedCount = 0;
    $duplicatesSkipped = (int) ($result['stats']['duplicates'] ?? 0);
} else {
    $currentNumbers = get_widget_active_numbers($widget);
    $mergeResult = merge_phone_numbers_with_stats($currentNumbers, $uploadedNumbers);
    $finalNumbers = $mergeResult['numbers'];
    $addedCount = (int) $mergeResult['added'];
    $duplicatesSkipped = (int) $mergeResult['duplicates_skipped'] + (int) ($result['stats']['duplicates'] ?? 0);

    if ($addedCount === 0 && $duplicatesSkipped > 0) {
        flash(
            'success',
            'Numbers uploaded successfully. 0 new numbers added, '
            . $duplicatesSkipped
            . ' duplicate'
            . ($duplicatesSkipped === 1 ? '' : 's')
            . ' skipped.'
        );
        redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
    }
}

if ($finalNumbers === [] || !save_widget_phone_numbers($widgetId, $finalNumbers)) {
    flash('error', 'No valid phone numbers found. Your current active numbers were not changed.');
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
}

$activeCount = count($finalNumbers);

if ($replaceExisting) {
    flash(
        'success',
        'Numbers replaced successfully. '
        . $activeCount
        . ' number'
        . ($activeCount === 1 ? ' is' : 's are')
        . ' now active.'
    );
} elseif ($duplicatesSkipped > 0) {
    flash(
        'success',
        'Numbers uploaded successfully. '
        . $addedCount
        . ' new number'
        . ($addedCount === 1 ? '' : 's')
        . ' added, '
        . $duplicatesSkipped
        . ' duplicate'
        . ($duplicatesSkipped === 1 ? '' : 's')
        . ' skipped.'
    );
} else {
    flash(
        'success',
        'Numbers uploaded successfully. '
        . $addedCount
        . ' new number'
        . ($addedCount === 1 ? '' : 's')
        . ' added. '
        . $activeCount
        . ' number'
        . ($activeCount === 1 ? ' is' : 's are')
        . ' now active.'
    );
}

redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
