<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$currentUser = require_superadmin();

$clientId = (int) (is_post() ? ($_POST['client_id'] ?? 0) : ($_GET['id'] ?? 0));
$client = find_client_user($clientId);
if (!$client) {
    http_response_code(404);
    exit(t('error.client_not_found'));
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
        $errors[] = t('validation.invalid_client_reference');
    }
    if ($confirmation !== 'DELETE') {
        $errors[] = t('validation.delete_confirmation');
    }
    if (!in_array($widgetMode, ['reassign', 'delete_all'], true)) {
        $errors[] = t('validation.widget_mode_required');
    }

    if (!$errors) {
        $result = delete_client_account($clientId, $widgetMode, (int) $currentUser['id']);
        if (!empty($result['success'])) {
            flash('success', (string) $result['message']);
            redirect('admin-clients.php');
        }

        $errors[] = (string) ($result['message'] ?? t('validation.client_delete_retry'));
    }
}

$pageTitle = t('page.delete_client');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.client_account')) ?></p>
    <h1><?= e(t('heading.delete_client')) ?></h1>
    <p><?= e(t('desc.delete_client')) ?></p>
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
        <?= e(t('alert.delete_client_warning')) ?>
    </div>

    <div class="profile-grid danger-zone-summary">
        <div><span class="meta-label"><?= e(t('meta.client_name')) ?></span><strong><?= e($client['name']) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.client_email')) ?></span><strong><?= e($client['email']) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.status')) ?></span><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(translate_user_status((string) $client['status'])) ?></span></div>
        <div><span class="meta-label"><?= e(t('meta.total_widgets')) ?></span><strong><?= (int) $widgetCount ?></strong></div>
        <?php if ($leadCount > 0 || database_table_exists('widget_leads')): ?>
            <div><span class="meta-label"><?= e(t('meta.total_leads')) ?></span><strong><?= (int) $leadCount ?></strong></div>
        <?php endif; ?>
    </div>

    <form method="post" class="danger-zone-form">
        <?= csrf_field() ?>
        <input type="hidden" name="client_id" value="<?= (int) $clientId ?>">

        <fieldset class="danger-zone-options">
            <legend><?= e(t('danger.widgets_legend')) ?></legend>

            <label class="radio-card">
                <input type="radio" name="widget_mode" value="reassign"<?= $widgetMode === 'reassign' ? ' checked' : '' ?>>
                <span>
                    <strong><?= e(t('danger.reassign_title')) ?></strong>
                    <small><?= e(t('danger.reassign_description')) ?></small>
                </span>
            </label>

            <label class="radio-card">
                <input type="radio" name="widget_mode" value="delete_all"<?= $widgetMode === 'delete_all' ? ' checked' : '' ?>>
                <span>
                    <strong><?= e(t('danger.delete_all_title')) ?></strong>
                    <small><?= e(t('danger.delete_all_description')) ?></small>
                </span>
            </label>
        </fieldset>

        <label class="danger-zone-confirm">
            <span><?= e(t('label.type_delete_to_confirm')) ?></span>
            <input type="text" name="confirmation" autocomplete="off" spellcheck="false" placeholder="<?= e(t('placeholder.delete_confirm')) ?>">
        </label>

        <div class="form-actions">
            <a class="btn btn-light" href="admin-client-detail.php?id=<?= (int) $clientId ?>"><?= e(t('button.cancel')) ?></a>
            <button type="submit" class="btn btn-danger-soft"><?= e(t('button.delete_client_permanently')) ?></button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
