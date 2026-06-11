<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_login();

if (is_post() && ($_POST['action'] ?? '') === 'delete_widget') {
    verify_csrf();
    $widgetId = (int) ($_POST['widget_id'] ?? 0);
    $stmt = db()->prepare('DELETE FROM widgets WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $widgetId, 'user_id' => (int) $user['id']]);
    flash('success', 'Widget deleted.');
    redirect('dashboard.php');
}

$stmt = db()->prepare('SELECT * FROM widgets WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute(['user_id' => (int) $user['id']]);
$widgets = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="dashboard-hero">
    <div>
        <p class="eyebrow">Welcome back</p>
        <h1><?= e($user['name']) ?></h1>
        <p>Manage WhatsApp click-to-chat widgets for multiple client websites and domains.</p>
    </div>
    <a class="btn btn-primary" href="create-widget.php">Create New Widget</a>
</section>

<section class="settings-card">
    <div class="card-header-row">
        <div>
            <h2>Your widgets</h2>
            <p>Each widget has its own domain settings, public key, embed code, and isolated iframe renderer.</p>
        </div>
    </div>

    <?php if (!$widgets): ?>
        <div class="empty-state">
            <h3>No widgets yet</h3>
            <p>Create your first widget to generate an iframe embed code.</p>
            <a class="btn btn-primary" href="create-widget.php">Create Widget</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th>Widget name</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($widgets as $widget): ?>
                        <tr>
                            <td>
                                <strong><?= e($widget['widget_name']) ?></strong>
                                <small>ID #<?= (int) $widget['id'] ?></small>
                            </td>
                            <td>
                                <strong><?= e($widget['website_domain']) ?></strong>
                                <small>Domain Lock: <?= e(enabled_label($widget['domain_lock_enabled'] ?? 1)) ?></small>
                                <small>WWW: <?= e(enabled_label($widget['allow_www'] ?? 1, 'Allowed', 'Not Allowed')) ?></small>
                                <small>Subdomains: <?= e(enabled_label($widget['allow_subdomains'] ?? 0, 'Allowed', 'Not Allowed')) ?></small>
                            </td>
                            <td><span class="status-pill"><?= e(widget_status_label($widget)) ?></span></td>
                            <td><?= e(date('M j, Y', strtotime((string) $widget['created_at']))) ?></td>
                            <td>
                                <div class="action-list">
                                    <a class="btn btn-small btn-light" href="edit-widget.php?id=<?= (int) $widget['id'] ?>">Edit</a>
                                    <a class="btn btn-small btn-light" href="widget-preview.php?id=<?= (int) $widget['id'] ?>">Preview</a>
                                    <a class="btn btn-small btn-primary-soft" href="embed-code.php?id=<?= (int) $widget['id'] ?>">Get Embed Code</a>
                                    <form method="post" data-confirm="Delete this widget? This cannot be undone.">
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

<?php require __DIR__ . '/includes/footer.php'; ?>
