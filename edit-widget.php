<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_login();

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = find_user_widget($widgetId, (int) $user['id']);
if (!$widget) {
    http_response_code(404);
    exit('Widget not found.');
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $updated = sanitize_widget_input($_POST);
    $errors = validate_widget_data($updated);

    if (!$errors) {
        update_widget($widgetId, (int) $user['id'], $updated);
        flash('success', 'Widget updated.');
        redirect('edit-widget.php?id=' . $widgetId);
    }

    $widget = array_merge($widget, $updated);
}

$pageTitle = 'Edit Widget';
require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <p class="eyebrow">Widget settings</p>
    <h1>Edit <?= e($widget['widget_name']) ?></h1>
    <p>Changes affect only this widget and are rendered inside its isolated iframe.</p>
</div>

<?php require __DIR__ . '/includes/widget-form.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
