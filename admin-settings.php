<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$autoPurgeEnabled = lead_recycle_auto_purge_enabled();
$retentionDays = lead_recycle_retention_days();

if (is_post()) {
    verify_csrf();

    $newAutoPurge = !empty($_POST['lead_recycle_bin_auto_purge_enabled']);
    $newRetentionDays = (int) ($_POST['lead_recycle_bin_retention_days'] ?? LEAD_RECYCLE_DEFAULT_DAYS);
    $newRetentionDays = max(LEAD_RECYCLE_RETENTION_MIN_DAYS, min(LEAD_RECYCLE_RETENTION_MAX_DAYS, $newRetentionDays));
    $confirmedReduction = !empty($_POST['confirm_retention_reduction']);

    if ($newRetentionDays < $retentionDays && !$confirmedReduction) {
        $eligibleCount = count_recycled_leads_beyond_retention($newRetentionDays);
        if ($eligibleCount > 0) {
            flash('error', t('lead.retention_reduction_warning', ['count' => (string) $eligibleCount]));
            redirect('admin-settings.php');
        }
    }

    save_app_setting('lead_recycle_bin_auto_purge_enabled', $newAutoPurge ? '1' : '0');
    save_app_setting('lead_recycle_bin_retention_days', (string) $newRetentionDays);
    flash('success', t('flash.settings_saved'));
    redirect('admin-settings.php');
}

$autoPurgeEnabled = lead_recycle_auto_purge_enabled();
$retentionDays = lead_recycle_retention_days();

$pageTitle = t('page.settings');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.super_admin')) ?></p>
    <h1><?= e(t('heading.settings')) ?></h1>
    <p><?= e(t('desc.settings')) ?></p>
</section>

<section class="settings-card">
    <div class="profile-grid">
        <div><span class="meta-label"><?= e(t('meta.application')) ?></span><strong><?= e(APP_NAME) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.base_url')) ?></span><strong><?= e(SYSTEM_BASE_URL) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.timezone')) ?></span><strong><?= e(date_default_timezone_get()) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.database')) ?></span><strong><?= e(DB_NAME) ?></strong></div>
    </div>
    <div class="notice-box compact">
        <p><?= e(t('alert.settings_credentials')) ?></p>
    </div>
</section>

<section class="settings-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('lead.recycle_retention_title')) ?></h2>
            <p><?= e(t('lead.recycle_retention_desc')) ?></p>
        </div>
    </div>

    <form class="settings-form" method="post" data-lead-recycle-settings data-current-retention="<?= (int) $retentionDays ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="confirm_retention_reduction" value="0" data-confirm-retention-reduction>

        <div class="settings-toggle-row">
            <div class="settings-toggle-copy">
                <strong><?= e(t('lead.recycle_auto_purge_label')) ?></strong>
                <span class="field-helper"><?= e(t('lead.recycle_retention_helper')) ?></span>
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="lead_recycle_bin_auto_purge_enabled" value="1"<?= $autoPurgeEnabled ? ' checked' : '' ?>>
                <span><?= e(t('lead.recycle_auto_purge_toggle')) ?></span>
            </label>
        </div>

        <label>
            <span><?= e(t('lead.recycle_retention_days_label')) ?></span>
            <input type="number" name="lead_recycle_bin_retention_days" min="<?= LEAD_RECYCLE_RETENTION_MIN_DAYS ?>" max="<?= LEAD_RECYCLE_RETENTION_MAX_DAYS ?>" step="1" value="<?= (int) $retentionDays ?>" required>
            <small class="field-helper"><?= e(format_retention_days_label($retentionDays)) ?></small>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(t('button.save_changes')) ?></button>
        </div>
    </form>
</section>

<script>
(function () {
    var form = document.querySelector('[data-lead-recycle-settings]');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        var currentRetention = parseInt(form.getAttribute('data-current-retention') || '0', 10);
        var input = form.querySelector('[name="lead_recycle_bin_retention_days"]');
        var confirmField = form.querySelector('[data-confirm-retention-reduction]');
        var nextRetention = parseInt(input && input.value ? input.value : '0', 10);

        if (!confirmField || nextRetention >= currentRetention) {
            return;
        }

        if (!window.confirm(<?= json_encode(t('lead.retention_reduction_confirm_generic')) ?>)) {
            event.preventDefault();
            return;
        }

        confirmField.value = '1';
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
