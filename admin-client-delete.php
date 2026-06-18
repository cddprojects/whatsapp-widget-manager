<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$currentUser = require_superadmin();

$clientId = (int) (is_post() ? ($_POST['client_id'] ?? 0) : ($_GET['id'] ?? 0));
$client = find_client_user($clientId);
if (!$client) {
    http_response_code(404);
    exit('Client not found.');
}

$widgetCount = client_widget_count($clientId);
$leadCount = client_lead_count($clientId);
$errors = [];
$widgetMode = 'reassign';

if (is_post()) {
    verify_csrf();

    $confirmation = trim((string) ($_POST['confirmation'] ?? ''));
    $widgetMode = (string) ($_POST['widget_mode'] ?? 'reassign');
    $postedClientId = (int) ($_POST['client_id'] ?? 0);

    if ($postedClientId !== $clientId) {
        $errors[] = 'Invalid client reference.';
    }
    if ($confirmation !== 'DELETE') {
        $errors[] = 'Confirmation text must exactly match DELETE.';
    }
    if (!in_array($widgetMode, ['reassign', 'delete_all'], true)) {
        $errors[] = 'Please choose what to do with this client’s widgets.';
    }

    if (!$errors) {
        $result = delete_client_account($clientId, $widgetMode, (int) $currentUser['id']);
        if (!empty($result['success'])) {
            flash('success', (string) $result['message']);
            redirect('admin-clients.php');
        }

        $errors[] = (string) ($result['message'] ?? 'Unable to delete client account. Please try again.');
    }
}

$pageTitle = 'Delete Client';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Client account</p>
    <h1>Delete client</h1>
    <p>Review this action carefully before permanently deleting a client account.</p>
</section>

<section class="settings-card danger-zone-card">
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning">
        You are about to delete this client account. This action cannot be undone.
    </div>

    <div class="profile-grid danger-zone-summary">
        <div><span class="meta-label">Client name</span><strong><?= e($client['name']) ?></strong></div>
        <div><span class="meta-label">Client email</span><strong><?= e($client['email']) ?></strong></div>
        <div><span class="meta-label">Status</span><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(ucfirst((string) $client['status'])) ?></span></div>
        <div><span class="meta-label">Total widgets</span><strong><?= (int) $widgetCount ?></strong></div>
        <?php if ($leadCount > 0 || database_table_exists('widget_leads')): ?>
            <div><span class="meta-label">Total leads</span><strong><?= (int) $leadCount ?></strong></div>
        <?php endif; ?>
    </div>

    <form method="post" class="danger-zone-form">
        <?= csrf_field() ?>
        <input type="hidden" name="client_id" value="<?= (int) $clientId ?>">

        <fieldset class="danger-zone-options">
            <legend>What should happen to this client’s widgets?</legend>

            <label class="radio-card">
                <input type="radio" name="widget_mode" value="reassign"<?= $widgetMode === 'reassign' ? ' checked' : '' ?>>
                <span>
                    <strong>Delete client only and keep widgets under superadmin</strong>
                    <small>Reassign all widgets to your superadmin account and keep them active.</small>
                </span>
            </label>

            <label class="radio-card">
                <input type="radio" name="widget_mode" value="delete_all"<?= $widgetMode === 'delete_all' ? ' checked' : '' ?>>
                <span>
                    <strong>Delete client and all widgets</strong>
                    <small>Delete the client account, all owned widgets, and related widget leads.</small>
                </span>
            </label>
        </fieldset>

        <label class="danger-zone-confirm">
            <span>Type DELETE to confirm</span>
            <input type="text" name="confirmation" autocomplete="off" spellcheck="false" placeholder="DELETE">
        </label>

        <div class="form-actions">
            <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $clientId ?>">Cancel</a>
            <button type="submit" class="btn btn-danger-soft">Delete Client Permanently</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
