<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

if (is_client()) {
    redirect('client-dashboard.php');
}

$widgetId = (int) ($_GET['id'] ?? 0);
$widget = require_widget_access($widgetId);
$errors = [];

if (is_post()) {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'reassign_owner') {
        $newOwnerId = (int) ($_POST['owner_user_id'] ?? 0);
        $newOwner = find_client_user($newOwnerId);
        if ($newOwner) {
            reassign_widget_owner($widgetId, (int) $newOwner['id']);
            flash('success', 'Widget owner updated.');
            redirect('edit-widget.php?id=' . $widgetId);
        }
        flash('error', 'Please select a valid client.');
        redirect('edit-widget.php?id=' . $widgetId);
    }

    $updated = sanitize_widget_input($_POST);
    $errors = validate_widget_data($updated);

    if (!$errors) {
        update_widget_admin($widgetId, $updated);
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
    <p>Full widget configuration. Owner: client #<?= (int) $widget['user_id'] ?>.</p>
</div>

<section class="settings-card">
    <form method="post" class="settings-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reassign_owner">
        <label>
            <span>Widget owner</span>
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
            <button type="submit" class="btn btn-light">Reassign owner</button>
            <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $widget['user_id'] ?>">View client</a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/widget-form.php'; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
