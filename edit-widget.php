<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = find_widget_with_owner($widgetId);
if (!$widget) {
    http_response_code(404);
    exit(t('error.widget_not_found'));
}
$errors = [];

if (is_post()) {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'reassign_owner') {
        $newOwnerId = (int) ($_POST['owner_user_id'] ?? 0);
        $newOwner = find_client_user($newOwnerId);
        if ($newOwner) {
            reassign_widget_owner($widgetId, (int) $newOwner['id']);
            flash('success', t('flash.widget_owner_updated'));
            redirect('edit-widget.php?id=' . $widgetId);
        }
        flash('error', t('flash.select_valid_client'));
        redirect('edit-widget.php?id=' . $widgetId);
    }

    $updated = sanitize_widget_input($_POST);
    $errors = validate_widget_data($updated);

    if (!$errors) {
        update_widget_admin($widgetId, $updated);
        flash('success', t('flash.widget_updated'));
        redirect('edit-widget.php?id=' . $widgetId);
    }

    $widget = array_merge($widget, $updated);
    $refreshedWidget = find_widget_with_owner($widgetId);
    if ($refreshedWidget) {
        $widget['owner_name'] = $refreshedWidget['owner_name'];
        $widget['owner_email'] = $refreshedWidget['owner_email'];
        $widget['user_id'] = $refreshedWidget['user_id'];
    }
}

$pageTitle = t('page.edit_widget');
$showLivePreview = true;
$pageStylesheets = ['assets/css/widget-live-preview.css'];
require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.widget_settings')) ?></p>
    <h1><?= e(t('heading.edit_widget', ['name' => $widget['widget_name']])) ?></h1>
    <p><?= e(t('desc.edit_widget', ['owner' => format_widget_owner_display($widget)])) ?></p>
</div>

<section class="settings-card">
    <form method="post" class="settings-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reassign_owner">
        <label>
            <span><?= e(t('label.widget_owner')) ?></span>
            <select name="owner_user_id" required>
                <?php
                $clientOptions = db()->query("SELECT id, name, email FROM users WHERE role = '" . ROLE_CLIENT . "' ORDER BY name ASC")->fetchAll();
                foreach ($clientOptions as $clientOption):
                ?>
                    <option value="<?= (int) $clientOption['id'] ?>"<?= (int) $clientOption['id'] === (int) $widget['user_id'] ? ' selected' : '' ?>>
                        <?= e($clientOption['name']) ?> — <?= e($clientOption['email']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-light"><?= e(t('button.reassign_owner')) ?></button>
            <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $widget['user_id'] ?>"><?= e(t('button.view_client')) ?></a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/widget-form.php'; ?>

<?php if (!empty($showLivePreview)): ?>
    <div id="ctcwAdminLivePreview" class="ctcw-admin-live-preview" aria-hidden="true"></div>
    <style id="ctcwAdminLivePreviewCustomCss"></style>
    <script type="application/json" id="ctcw-preview-icon"><?= json_for_html(whatsapp_icon_svg()) ?></script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
