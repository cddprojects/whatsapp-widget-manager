<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$targetUserId = 0;
$clientContext = null;

if (isset($_GET['user_id'])) {
    $clientContext = find_client_user((int) $_GET['user_id']);
    if (!$clientContext) {
        http_response_code(404);
        exit('Client not found.');
    }
    $targetUserId = (int) $clientContext['id'];
}

$showOwnerPicker = $clientContext === null;
$widget = default_widget_data();
$errors = [];

if (is_post()) {
    verify_csrf();
    $widget = sanitize_widget_input($_POST);

    if ($targetUserId === 0) {
        $targetUserId = (int) ($_POST['owner_user_id'] ?? 0);
        $clientContext = find_client_user($targetUserId);
        if (!$clientContext) {
            $errors[] = 'Please select a client to assign this widget to.';
        }
    }

    if (!$errors) {
        $errors = validate_widget_data($widget);
    }

    if (!$errors) {
        $widgetId = insert_widget($targetUserId, $widget);
        flash('success', 'Widget created for ' . $clientContext['name'] . '.');
        redirect('edit-widget.php?id=' . $widgetId);
    }
}

$pageTitle = 'Create Widget';
require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <p class="eyebrow">New widget</p>
    <h1>Create WhatsApp click-to-chat widget</h1>
    <?php if ($clientContext): ?>
        <p>Create a widget for <strong><?= e($clientContext['name']) ?></strong> (<?= e($clientContext['email']) ?>).</p>
    <?php else: ?>
        <p>Configure the widget and assign it to a client account.</p>
    <?php endif; ?>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/widget-form.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
