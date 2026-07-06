<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = require_widget_access($widgetId);
$errors = [];

if (is_post()) {
    verify_csrf();
    $data = sanitize_phone_numbers_from_post($_POST, 'manual_numbers', $widget);
    if ($data === null) {
        $errors[] = t('validation.keep_one_number');
    } else {
        update_widget_phone_fields($widgetId, $data);
        flash('success', t('flash.phone_numbers_updated'));
        redirect('edit-widget-phone.php?id=' . $widgetId);
    }
}

$phoneNumbers = widget_phone_list($widget);

$pageTitle = t('page.edit_phone_number');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.phone_settings')) ?></p>
    <h1><?= e($widget['widget_name']) ?></h1>
    <p><?= e(t('desc.edit_phone_numbers')) ?></p>
</section>

<section class="settings-card">
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" class="settings-form" data-client-manual-form>
        <?= csrf_field() ?>
        <?php
        $fieldPrefix = 'manual_numbers';
        $listId = 'admin-phone-number-list';
        require __DIR__ . '/includes/phone-number-list.php';
        ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(t('button.save_numbers')) ?></button>
            <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $widget['user_id'] ?>"><?= e(t('button.back_to_client')) ?></a>
        </div>
    </form>
</section>

<script type="application/json" id="country-code-data"><?= json_for_html(calling_code_options()) ?></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
