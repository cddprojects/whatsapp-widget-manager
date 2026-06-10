<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_login();

$widget = default_widget_data();
$errors = [];

if (is_post()) {
    verify_csrf();
    $widget = sanitize_widget_input($_POST);
    $errors = validate_widget_data($widget);

    if (!$errors) {
        $widgetId = insert_widget((int) $user['id'], $widget);
        flash('success', 'Widget created. Your iframe embed code is ready.');
        redirect('edit-widget.php?id=' . $widgetId);
    }
}

$pageTitle = 'Create Widget';
require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <p class="eyebrow">New widget</p>
    <h1>Create WhatsApp click-to-chat widget</h1>
    <p>Configure the number, style, domain, display rules, greeting, business hours, and custom iframe code.</p>
</div>

<?php require __DIR__ . '/includes/widget-form.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
