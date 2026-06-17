<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = require_widget_access($widgetId);
$errors = [];

if (is_post()) {
    verify_csrf();
    $data = sanitize_client_phone_manual_input($_POST);
    if ($data === null) {
        $errors[] = 'Please enter a valid phone number.';
    } else {
        update_widget_phone_fields($widgetId, $data);
        flash('success', 'Phone number updated.');
        redirect('edit-widget-phone.php?id=' . $widgetId);
    }
}

$pageTitle = 'Edit Phone Number';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Phone settings</p>
    <h1><?= e($widget['widget_name']) ?></h1>
    <p>Update WhatsApp phone number settings only.</p>
</section>

<section class="settings-card">
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" class="settings-form">
        <?= csrf_field() ?>
        <div class="form-grid two-columns">
            <label>
                <span>Country code</span>
                <select name="whatsapp_country_code">
                    <?php foreach (country_codes() as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= selected((string) ($widget['whatsapp_country_code'] ?? '+60'), $code) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Phone number</span>
                <input type="text" name="whatsapp_number" value="<?= e((string) ($widget['whatsapp_number'] ?? '')) ?>" required>
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $widget['user_id'] ?>">Back to client</a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
