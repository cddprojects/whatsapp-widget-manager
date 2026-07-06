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
        exit(t('error.client_not_found'));
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
            $errors[] = t('validation.select_client');
        }
    }

    if (!$errors) {
        $errors = validate_widget_data($widget);
    }

    if (!$errors) {
        $widgetId = insert_widget($targetUserId, $widget);
        $createdWidget = find_widget_by_id($widgetId);
        if ($createdWidget && widget_has_valid_destinations($createdWidget)) {
            flash('success', t('flash.widget_created', ['name' => $clientContext['name']]));
        } else {
            flash('success', t('flash.widget_created_setup_required'));
        }
        redirect('edit-widget.php?id=' . $widgetId);
    }
}

$pageTitle = t('page.create_widget');
require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.new_widget')) ?></p>
    <h1><?= e(t('heading.create_widget')) ?></h1>
    <?php if ($clientContext): ?>
        <p><?= e(t('desc.create_widget_for_client', ['name' => $clientContext['name'], 'email' => $clientContext['email']])) ?></p>
    <?php else: ?>
        <p><?= e(t('desc.create_widget_assign')) ?></p>
    <?php endif; ?>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/widget-form.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
