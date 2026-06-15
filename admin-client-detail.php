<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$clientId = (int) ($_GET['id'] ?? 0);
$client = find_client_user($clientId);
if (!$client) {
    http_response_code(404);
    exit('Client not found.');
}

if (is_post() && ($_POST['action'] ?? '') === 'delete_widget') {
    verify_csrf();
    $widgetId = (int) ($_POST['widget_id'] ?? 0);
    $widget = find_widget_by_id($widgetId);
    if ($widget && (int) $widget['user_id'] === $clientId) {
        $stmt = db()->prepare('DELETE FROM widgets WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $widgetId, 'user_id' => $clientId]);
        flash('success', 'Widget deleted.');
    }
    redirect('admin-client-detail.php?id=' . $clientId);
}

if (is_post() && ($_POST['action'] ?? '') === 'toggle_status') {
    verify_csrf();
    if ((string) $client['status'] === USER_STATUS_ACTIVE) {
        $stmt = db()->prepare('UPDATE users SET status = :status WHERE id = :id AND role = :role');
        $stmt->execute(['status' => USER_STATUS_DISABLED, 'id' => $clientId, 'role' => ROLE_CLIENT]);
        flash('success', 'Client disabled.');
    } else {
        $stmt = db()->prepare('UPDATE users SET status = :status WHERE id = :id AND role = :role');
        $stmt->execute(['status' => USER_STATUS_ACTIVE, 'id' => $clientId, 'role' => ROLE_CLIENT]);
        flash('success', 'Client enabled.');
    }
    redirect('admin-client-detail.php?id=' . $clientId);
}

$widgets = widgets_for_user($clientId);
$widgetCount = count($widgets);

$pageTitle = 'Client Profile';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Client profile</p>
    <h1><?= e($client['name']) ?></h1>
    <p>Manage this client account and their widgets.</p>
</section>

<section class="settings-card profile-card">
    <div class="card-header-row">
        <div>
            <h2>Client profile</h2>
            <p>Account overview and admin actions.</p>
        </div>
        <div class="action-list">
            <a class="btn btn-light" href="admin-client-edit.php?id=<?= (int) $client['id'] ?>">Edit Client</a>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_status">
                <button type="submit" class="btn btn-light">
                    <?= (string) $client['status'] === USER_STATUS_ACTIVE ? 'Disable Client' : 'Enable Client' ?>
                </button>
            </form>
            <a class="btn btn-primary-soft" href="admin-client-reset-password.php?id=<?= (int) $client['id'] ?>">Reset Password</a>
        </div>
    </div>
    <div class="profile-grid">
        <div><span class="meta-label">Email</span><strong><?= e($client['email']) ?></strong></div>
        <div><span class="meta-label">Status</span><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(ucfirst((string) $client['status'])) ?></span></div>
        <div><span class="meta-label">Created</span><strong><?= e(date('M j, Y', strtotime((string) $client['created_at']))) ?></strong></div>
        <div><span class="meta-label">Last login</span><strong><?= e(format_datetime($client['last_login_at'] ?? null)) ?></strong></div>
        <div><span class="meta-label">Total widgets</span><strong><?= (int) $widgetCount ?></strong></div>
    </div>
</section>

<section class="settings-card">
    <div class="card-header-row">
        <div>
            <h2>Client widgets</h2>
            <p>Widgets owned by this client only.</p>
        </div>
        <a class="btn btn-primary" href="create-widget.php?user_id=<?= (int) $client['id'] ?>">Create Widget for This Client</a>
    </div>

    <?php if (!$widgets): ?>
        <div class="empty-state compact-empty">
            <p>This client has no widgets yet.</p>
            <a class="btn btn-primary" href="create-widget.php?user_id=<?= (int) $client['id'] ?>">Create Widget for This Client</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th>Widget name</th>
                        <th>Domain</th>
                        <th>WhatsApp number</th>
                        <th>Random numbers</th>
                        <th>Global display</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($widgets as $widget): ?>
                        <tr>
                            <td><strong><?= e($widget['widget_name']) ?></strong></td>
                            <td><?= e($widget['website_domain']) ?></td>
                            <td><?= format_whatsapp_display($widget) ?></td>
                            <td><?= e(enabled_label($widget['use_random_numbers'] ?? 0)) ?></td>
                            <td><?= e(enabled_label($widget['show_global'] ?? 0)) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $widget['updated_at']))) ?></td>
                            <td>
                                <div class="action-list">
                                    <a class="btn btn-small btn-light" href="widget-preview.php?id=<?= (int) $widget['id'] ?>">Preview</a>
                                    <a class="btn btn-small btn-primary-soft" href="edit-widget.php?id=<?= (int) $widget['id'] ?>">Full Edit</a>
                                    <a class="btn btn-small btn-light" href="edit-widget-phone.php?id=<?= (int) $widget['id'] ?>">Phone Number</a>
                                    <a class="btn btn-small btn-light" href="admin-widget-leads.php?widget_id=<?= (int) $widget['id'] ?>">Leads</a>
                                    <a class="btn btn-small btn-light" href="embed-code.php?id=<?= (int) $widget['id'] ?>">Embed Code</a>
                                    <form method="post" data-confirm="Delete this widget?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_widget">
                                        <input type="hidden" name="widget_id" value="<?= (int) $widget['id'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger-soft">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="form-actions">
    <a class="btn btn-light" href="admin-clients.php">Back to Clients</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
