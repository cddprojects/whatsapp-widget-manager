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

$file = $_FILES['phone_file'] ?? null;
if (!is_array($file)) {
    flash('error', t('flash.upload_choose_file'));
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
    flash('error', t('flash.upload_no_valid_numbers'));
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
            t(
                $duplicatesSkipped === 1 ? 'flash.upload_zero_added_dupes_one' : 'flash.upload_zero_added_dupes_other',
                ['count' => (string) $duplicatesSkipped]
            )
        );
        redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
    }
}

if ($finalNumbers === [] || !save_widget_phone_numbers($widgetId, $finalNumbers)) {
    flash('error', t('flash.upload_no_valid_numbers'));
    redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
}

$activeCount = count($finalNumbers);

if ($replaceExisting) {
    flash(
        'success',
        t(
            $activeCount === 1 ? 'flash.upload_replaced_one' : 'flash.upload_replaced_other',
            ['count' => (string) $activeCount]
        )
    );
} elseif ($duplicatesSkipped > 0) {
    $addedKey = $addedCount === 1 ? 'one' : 'other';
    $dupeKey = $duplicatesSkipped === 1 ? 'one' : 'other';
    flash(
        'success',
        t(
            'flash.upload_added_with_dupes_' . $addedKey . '_' . $dupeKey,
            ['added' => (string) $addedCount, 'count' => (string) $duplicatesSkipped]
        )
    );
} else {
    $addedKey = $addedCount === 1 ? 'one' : 'other';
    $activeKey = $activeCount === 1 ? 'one' : 'other';
    flash(
        'success',
        t(
            'flash.upload_added_' . $addedKey . '_' . $activeKey,
            ['added' => (string) $addedCount, 'active' => (string) $activeCount]
        )
    );
}

redirect('client-dashboard.php?widget_id=' . $widgetId . '&tab=upload');
