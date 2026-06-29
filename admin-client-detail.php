<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$clientId = (int) ($_GET['id'] ?? 0);
$client = find_client_user($clientId);
if (!$client) {
    http_response_code(404);
    exit(t('error.client_not_found'));
}

if (is_post() && ($_POST['action'] ?? '') === 'delete_widget') {
    verify_csrf();
    $widgetId = (int) ($_POST['widget_id'] ?? 0);
    $widget = find_widget_by_id($widgetId);
    if ($widget && (int) $widget['user_id'] === $clientId) {
        $stmt = db()->prepare('DELETE FROM widgets WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $widgetId, 'user_id' => $clientId]);
        flash('success', t('flash.widget_deleted'));
    }
    redirect('admin-client-detail.php?id=' . $clientId);
}

if (is_post() && ($_POST['action'] ?? '') === 'toggle_status') {
    verify_csrf();
    if ((string) $client['status'] === USER_STATUS_ACTIVE) {
        $stmt = db()->prepare('UPDATE users SET status = :status WHERE id = :id AND role = :role');
        $stmt->execute(['status' => USER_STATUS_DISABLED, 'id' => $clientId, 'role' => ROLE_CLIENT]);
        flash('success', t('flash.client_disabled'));
    } else {
        $stmt = db()->prepare('UPDATE users SET status = :status WHERE id = :id AND role = :role');
        $stmt->execute(['status' => USER_STATUS_ACTIVE, 'id' => $clientId, 'role' => ROLE_CLIENT]);
        flash('success', t('flash.client_enabled'));
    }
    redirect('admin-client-detail.php?id=' . $clientId);
}

$widgets = widgets_for_user($clientId);
$widgetCount = count($widgets);
$createdPassword = $_SESSION['created_client_password'] ?? null;
$createdClientEmail = $_SESSION['created_client_email'] ?? null;
unset($_SESSION['created_client_password'], $_SESSION['created_client_email']);

$pageTitle = t('page.client_profile');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading page-heading-row">
    <div>
        <p class="eyebrow"><?= e(t('eyebrow.client_profile')) ?></p>
        <h1><?= e($client['name']) ?></h1>
        <p><?= e(t('desc.client_profile')) ?></p>
    </div>
    <a class="btn btn-primary" href="create-widget.php?user_id=<?= (int) $client['id'] ?>"><?= e(t('button.create_widget_for_client')) ?></a>
</section>

<?php if ($createdPassword !== null): ?>
    <div class="alert alert-success">
        <?= e($createdClientEmail
            ? t('alert.client_created_for', ['email' => (string) $createdClientEmail])
            : t('alert.client_created')) ?>
    </div>
    <div class="alert alert-warning">
        <?= e(t('alert.copy_password_now')) ?>
    </div>
    <section class="settings-card">
        <div class="temp-password-box">
            <span class="meta-label"><?= e(t('meta.password')) ?></span>
            <code><?= e((string) $createdPassword) ?></code>
        </div>
    </section>
<?php endif; ?>

<section class="settings-card profile-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('heading.client_profile')) ?></h2>
            <p><?= e(t('desc.client_profile_overview')) ?></p>
        </div>
        <div class="action-list">
            <a class="btn btn-light" href="admin-client-edit.php?id=<?= (int) $client['id'] ?>"><?= e(t('button.edit_client')) ?></a>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_status">
                <button type="submit" class="btn btn-light">
                    <?= e((string) $client['status'] === USER_STATUS_ACTIVE ? t('button.disable_client') : t('button.enable_client')) ?>
                </button>
            </form>
            <a class="btn btn-primary-soft" href="admin-client-reset-password.php?id=<?= (int) $client['id'] ?>"><?= e(t('button.reset_password')) ?></a>
        </div>
    </div>
    <div class="profile-grid">
        <div><span class="meta-label"><?= e(t('meta.email')) ?></span><strong><?= e($client['email']) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.status')) ?></span><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(translate_user_status((string) $client['status'])) ?></span></div>
        <div><span class="meta-label"><?= e(t('meta.created')) ?></span><strong><?= e(date('M j, Y', strtotime((string) $client['created_at']))) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.last_login')) ?></span><strong><?= e(format_datetime($client['last_login_at'] ?? null)) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.total_widgets')) ?></span><strong><?= (int) $widgetCount ?></strong></div>
    </div>
</section>

<section class="settings-card table-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('heading.client_widgets')) ?></h2>
            <p><?= e(t('desc.client_widgets')) ?></p>
        </div>
    </div>

    <?php if (!$widgets): ?>
        <div class="empty-state compact-empty">
            <p><?= e(t('empty.client_no_widgets')) ?></p>
            <a class="btn btn-primary" href="create-widget.php?user_id=<?= (int) $client['id'] ?>"><?= e(t('button.create_widget_for_client')) ?></a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th><?= e(t('table.widget_name')) ?></th>
                        <th><?= e(t('table.domain')) ?></th>
                        <th><?= e(t('table.whatsapp_number')) ?></th>
                        <th><?= e(t('table.random_numbers')) ?></th>
                        <th><?= e(t('table.global_display')) ?></th>
                        <th><?= e(t('table.updated')) ?></th>
                        <th class="col-actions"><?= e(t('table.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($widgets as $widget): ?>
                        <tr>
                            <td><strong><?= e($widget['widget_name']) ?></strong></td>
                            <td><?= e($widget['website_domain']) ?></td>
                            <td><?= format_whatsapp_display($widget) ?></td>
                            <td><?= feature_status_pill($widget['use_random_numbers'] ?? 0) ?></td>
                            <td><?= feature_status_pill($widget['show_global'] ?? 0) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $widget['updated_at']))) ?></td>
                            <td class="col-actions">
                                <?php render_widget_action_menu($widget, [
                                    'show_delete' => true,
                                    'delete_client_id' => $clientId,
                                ]); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="settings-card danger-zone-card">
    <h2><?= e(t('heading.danger_zone')) ?></h2>
    <p class="danger-zone-copy"><?= e(t('desc.danger_zone')) ?></p>
    <a class="btn btn-danger-soft" href="admin-client-delete.php?id=<?= (int) $client['id'] ?>"><?= e(t('button.delete_client')) ?></a>
</section>

<div class="form-actions">
    <a class="btn btn-light" href="admin-clients.php"><?= e(t('button.back_to_clients')) ?></a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
