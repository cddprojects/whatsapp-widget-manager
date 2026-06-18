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

$activeNumbers = $widget ? client_active_phone_numbers($widget) : [];
$activeTab = (string) ($_GET['tab'] ?? 'manual');

$pageTitle = 'My WhatsApp Number';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Client account</p>
    <h1>My WhatsApp Number</h1>
    <p>Update the WhatsApp number used by your widget.</p>
</section>

<?php if (!$widgets): ?>
    <section class="settings-card">
        <div class="empty-state">
            <h3>No widget assigned yet</h3>
            <p>Your administrator has not assigned a widget to your account.</p>
        </div>
    </section>
<?php else: ?>
    <section class="settings-card client-summary-card">
        <?php if (count($widgets) > 1): ?>
            <form method="get" class="widget-selector-form">
                <label>
                    <span>Select widget</span>
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
            <div><span class="meta-label">Widget name</span><strong><?= e($widget['widget_name']) ?></strong></div>
            <div><span class="meta-label">Domain</span><strong><?= e($widget['website_domain']) ?></strong></div>
            <div><span class="meta-label">Current WhatsApp number</span><strong><?= format_whatsapp_display($widget) ?></strong></div>
            <div><span class="meta-label">Random numbers</span><strong><?= feature_status_pill($widget['use_random_numbers'] ?? 0) ?></strong></div>
        </div>
    </section>

    <section class="settings-card client-phone-card">
        <div class="tab-bar">
            <a class="tab-link<?= $activeTab === 'manual' ? ' is-active' : '' ?>" href="client-dashboard.php?widget_id=<?= (int) $selectedWidgetId ?>&tab=manual">Manual Entry</a>
            <a class="tab-link<?= $activeTab === 'upload' ? ' is-active' : '' ?>" href="client-dashboard.php?widget_id=<?= (int) $selectedWidgetId ?>&tab=upload">Upload Numbers</a>
        </div>

        <?php if ($activeTab === 'upload'): ?>
            <form class="settings-form client-upload-form" method="post" action="upload-phone-numbers.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="widget_id" value="<?= (int) $selectedWidgetId ?>">
                <label>
                    <span>Upload CSV or TXT</span>
                    <input type="file" name="phone_file" accept=".csv,.txt,text/csv,text/plain" required>
                </label>
                <div class="format-helper">
                    <strong>CSV format</strong>
                    <code>country_code,phone_number</code>
                    <pre>+60,123456789
+65,81234567</pre>
                    <strong>TXT format</strong>
                    <p>One full international number per line:</p>
                    <pre>+60123456789
+6581234567</pre>
                </div>
                <label class="checkbox-row">
                    <input type="checkbox" name="replace_existing" value="1">
                    <span>Replace existing active numbers</span>
                </label>
                <p class="upload-helper-text">If unchecked, uploaded numbers will be added to your current active numbers.</p>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Upload Numbers</button>
                </div>
            </form>
        <?php else: ?>
            <form class="settings-form client-manual-form" method="post" action="update-phone-numbers.php" data-client-manual-form>
                <?= csrf_field() ?>
                <input type="hidden" name="widget_id" value="<?= (int) $selectedWidgetId ?>">

                <div class="client-number-panel">
                    <div class="panel-heading">
                        <div>
                            <h3>Active numbers</h3>
                            <p>Add numbers one at a time, then save your list.</p>
                        </div>
                        <button type="button" class="btn btn-small btn-light" data-client-add-number>Add Number</button>
                    </div>

                    <div class="client-number-list" data-client-number-list>
                        <?php if ($activeNumbers === []): ?>
                            <div class="empty-state compact-empty" data-client-empty-state>
                                <p>No numbers added yet. Click Add Number to get started.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activeNumbers as $index => $row): ?>
                                <div class="client-number-item" data-client-number-item>
                                    <div class="client-number-display">
                                        <strong><?= e((string) $row['country_code']) ?></strong>
                                        <span><?= e((string) $row['number']) ?></span>
                                    </div>
                                    <input type="hidden" name="manual_numbers[<?= (int) $index ?>][country_code]" value="<?= e((string) $row['country_code']) ?>" data-number-country>
                                    <input type="hidden" name="manual_numbers[<?= (int) $index ?>][number]" value="<?= e((string) $row['number']) ?>" data-number-phone>
                                    <button type="button" class="btn btn-small btn-light" data-client-remove-number>Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <template id="client-number-item-template">
                        <div class="client-number-item" data-client-number-item>
                            <div class="client-number-display">
                                <strong data-display-country></strong>
                                <span data-display-phone></span>
                            </div>
                            <input type="hidden" name="" value="" data-number-country>
                            <input type="hidden" name="" value="" data-number-phone>
                            <button type="button" class="btn btn-small btn-light" data-client-remove-number>Remove</button>
                        </div>
                    </template>

                    <div class="client-number-draft" data-client-number-draft hidden>
                        <div class="client-number-draft-row">
                            <?= render_country_code_search_input('client-draft-country', '+60') ?>
                            <label class="client-phone-input">
                                <span class="sr-only">Phone number</span>
                                <input type="tel" data-client-draft-phone placeholder="123456789" autocomplete="tel">
                            </label>
                            <div class="client-number-draft-actions">
                                <button type="button" class="btn btn-small btn-primary" data-client-confirm-number>Confirm</button>
                                <button type="button" class="btn btn-small btn-light" data-client-cancel-number>Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Numbers</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <script type="application/json" id="country-code-data"><?= json_for_html(country_code_options()) ?></script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
