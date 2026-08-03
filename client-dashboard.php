<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_client();

$widgets = widgets_for_user((int) $user['id']);
$selectedWidgetId = (int) ($_GET['widget_id'] ?? ($widgets[0]['id'] ?? 0));
$widget = null;

foreach ($widgets as $item) {
    if ((int) $item['id'] === $selectedWidgetId) {
        $widget = $item;
        break;
    }
}

if (!$widget && $widgets !== []) {
    $widget = $widgets[0];
    $selectedWidgetId = (int) $widget['id'];
}

$activeNumbers = $widget ? widget_phone_list($widget) : [];
$activeTab = (string) ($_GET['tab'] ?? 'manual');
if (!in_array($activeTab, ['manual', 'upload', 'telegram'], true)) {
    $activeTab = 'manual';
}

$pageTitle = t('page.my_whatsapp_number');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow"><?= e(t('eyebrow.client_account')) ?></p>
    <h1><?= e(t('heading.my_whatsapp_number')) ?></h1>
    <p><?= e(t('desc.my_whatsapp_number')) ?></p>
</section>

<?php if ($widgets): ?>
<section class="summary-grid lead-summary-grid">
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.yesterday_title')) ?></span>
        <strong><?= (int) count_yesterday_active_leads((int) $user['id']) ?></strong>
        <small><?= e(t('lead.today_scope_client')) ?></small>
    </article>
    <article class="summary-card">
        <span class="summary-label"><?= e(t('lead.today_title')) ?></span>
        <strong><?= (int) count_active_leads((int) $user['id'], true) ?></strong>
        <small><?= e(t('lead.today_scope_client')) ?></small>
    </article>
</section>

<p class="lead-timezone-note dashboard-timezone-note"><?= e(t('lead.times_timezone_note')) ?></p>

<div class="form-actions page-inline-actions">
    <a class="btn btn-light" href="<?= e(app_url('client-leads.php')) ?>"><?= e(t('nav.my_leads')) ?></a>
</div>
<?php endif; ?>

<?php if (!$widgets): ?>
    <section class="settings-card">
        <div class="empty-state">
            <h3><?= e(t('heading.no_widget_assigned')) ?></h3>
            <p><?= e(t('desc.no_widget_assigned')) ?></p>
        </div>
    </section>
<?php else: ?>
    <?php
    $widgetActivationStatus = normalize_widget_activation_status((string) ($widget['widget_status'] ?? WIDGET_STATUS_SETUP_REQUIRED));
    if (widget_is_admin_disabled($widget)) {
        $widgetActivationStatus = WIDGET_STATUS_DISABLED;
    }
    $needsSetup = in_array($widgetActivationStatus, [WIDGET_STATUS_SETUP_REQUIRED, WIDGET_STATUS_PAUSED], true)
        && !widget_has_valid_destinations($widget);
    ?>
    <?php if ($needsSetup): ?>
        <section class="settings-card widget-setup-card">
            <h2><?= e(t('client_setup.title')) ?></h2>
            <p><?= e(t('client_setup.description')) ?></p>
            <div class="form-actions">
                <a class="btn btn-primary" href="<?= e(app_url('client-dashboard.php', ['widget_id' => (int) $selectedWidgetId, 'tab' => 'manual'])) ?>"><?= e(t('client_setup.add_number')) ?></a>
            </div>
        </section>
    <?php endif; ?>

    <section class="settings-card client-summary-card">
        <?php if (count($widgets) > 1): ?>
            <form method="get" class="widget-selector-form">
                <label>
                    <span><?= e(t('label.select_widget')) ?></span>
                    <select name="widget_id" onchange="this.form.submit()">
                        <?php foreach ($widgets as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $selectedWidgetId ? ' selected' : '' ?>>
                                <?= e($item['widget_name']) ?> — <?= e($item['website_domain']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
            </form>
        <?php endif; ?>

        <div class="profile-grid">
            <div><span class="meta-label"><?= e(t('meta.widget_name')) ?></span><strong><?= e($widget['widget_name']) ?></strong></div>
            <div><span class="meta-label"><?= e(t('meta.domain')) ?></span><strong><?= e($widget['website_domain']) ?></strong></div>
            <div><span class="meta-label"><?= e(t('meta.active_numbers')) ?></span><strong><?= e(client_active_numbers_label($widget)) ?></strong></div>
            <div><span class="meta-label"><?= e(t('meta.widget_status')) ?></span><?php render_widget_activation_status($widget, true); ?></div>
        </div>
    </section>

    <section class="settings-card client-phone-card">
        <?php
        $telegramEnabledForClient = widget_channel_is_enabled((int) $selectedWidgetId, WIDGET_CHANNEL_TELEGRAM, $widget);
        ?>
        <div class="tab-bar">
            <a class="tab-link<?= $activeTab === 'manual' ? ' is-active' : '' ?>" href="<?= e(app_url('client-dashboard.php', ['widget_id' => (int) $selectedWidgetId, 'tab' => 'manual'])) ?>"><?= e(t('tab.phone_numbers')) ?></a>
            <a class="tab-link<?= $activeTab === 'upload' ? ' is-active' : '' ?>" href="<?= e(app_url('client-dashboard.php', ['widget_id' => (int) $selectedWidgetId, 'tab' => 'upload'])) ?>"><?= e(t('tab.upload_numbers')) ?></a>
            <a class="tab-link<?= $activeTab === 'telegram' ? ' is-active' : '' ?>" href="<?= e(app_url('client-dashboard.php', ['widget_id' => (int) $selectedWidgetId, 'tab' => 'telegram'])) ?>"><?= e(t('tab.telegram_destinations')) ?></a>
        </div>

        <?php if ($activeTab === 'telegram'): ?>
            <?php if (!$telegramEnabledForClient): ?>
                <div class="empty-state">
                    <h3><?= e(t('telegram.disabled_title')) ?></h3>
                    <p><?= e(t('telegram.disabled_by_admin')) ?></p>
                </div>
            <?php else: ?>
                <?php
                $destinationsContext = 'client';
                $destinationsTelegramOnly = true;
                require __DIR__ . '/includes/destinations-panel.php';
                ?>
            <?php endif; ?>
        <?php elseif ($activeTab === 'upload'): ?>
            <form class="settings-form client-upload-form" method="post" action="<?= e(app_url('upload-phone-numbers.php')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="widget_id" value="<?= (int) $selectedWidgetId ?>">
                <label>
                    <span><?= e(t('label.upload_csv_or_txt')) ?></span>
                    <input type="file" name="phone_file" accept=".csv,.txt,text/csv,text/plain" required>
                </label>
                <div class="format-helper">
                    <strong><?= e(t('helper.csv_format')) ?></strong>
                    <code>country_code,phone_number</code>
                    <pre>+60,123456789
+65,81234567</pre>
                    <strong><?= e(t('helper.txt_format')) ?></strong>
                    <p><?= e(t('helper.txt_one_per_line')) ?></p>
                    <pre>+60123456789
+6581234567</pre>
                </div>
                <label class="checkbox-row">
                    <input type="checkbox" name="replace_existing" value="1">
                    <span><?= e(t('toggle.replace_existing_numbers')) ?></span>
                </label>
                <p class="upload-helper-text"><?= e(t('helper.upload_replace_existing')) ?></p>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= e(t('button.upload_numbers')) ?></button>
                </div>
            </form>
        <?php else: ?>
            <form class="settings-form client-manual-form" method="post" action="<?= e(app_url('update-phone-numbers.php')) ?>" data-client-manual-form data-allow-empty-phones="1">
                <?= csrf_field() ?>
                <input type="hidden" name="widget_id" value="<?= (int) $selectedWidgetId ?>">

                <?php
                $phoneNumbers = $activeNumbers;
                $fieldPrefix = 'manual_numbers';
                $listId = 'client-phone-number-list';
                $allowEmptyPhones = true;
                require __DIR__ . '/includes/phone-number-list.php';
                ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= e(t('button.save_numbers')) ?></button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <script type="application/json" id="country-code-data"><?= json_for_html(calling_code_options()) ?></script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
