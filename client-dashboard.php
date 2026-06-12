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

$importedNumbers = $widget ? decode_random_numbers($widget['random_numbers_json'] ?? '[]') : [];
$activeTab = (string) ($_GET['tab'] ?? 'manual');

$pageTitle = 'My WhatsApp Number';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Client account</p>
    <h1>My WhatsApp Number</h1>
    <p>Update the WhatsApp number used by your widget. Advanced settings are managed by your administrator.</p>
</section>

<?php if (!$widgets): ?>
    <section class="settings-card">
        <div class="empty-state">
            <h3>No widget assigned yet</h3>
            <p>Your administrator has not assigned a widget to your account.</p>
        </div>
    </section>
<?php else: ?>
    <section class="settings-card">
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
            <div><span class="meta-label">Random numbers</span><strong><?= e(enabled_label($widget['use_random_numbers'] ?? 0)) ?></strong></div>
        </div>

        <div class="tab-bar">
            <a class="tab-link<?= $activeTab === 'manual' ? ' is-active' : '' ?>" href="client-dashboard.php?widget_id=<?= (int) $selectedWidgetId ?>&tab=manual">Manual Entry</a>
            <a class="tab-link<?= $activeTab === 'upload' ? ' is-active' : '' ?>" href="client-dashboard.php?widget_id=<?= (int) $selectedWidgetId ?>&tab=upload">Upload Numbers</a>
        </div>

        <?php if ($activeTab === 'upload'): ?>
            <form class="settings-form" method="post" action="upload-phone-numbers.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="widget_id" value="<?= (int) $selectedWidgetId ?>">
                <label>
                    <span>Upload CSV or TXT</span>
                    <small>Max 1MB. CSV columns: country_code, phone_number, label. TXT: one number per line.</small>
                    <input type="file" name="phone_file" accept=".csv,.txt,text/csv,text/plain" required>
                </label>
                <label>
                    <span>Default country code</span>
                    <select name="default_country_code">
                        <?php foreach (country_codes() as $code => $label): ?>
                            <option value="<?= e($code) ?>"<?= selected((string) ($widget['whatsapp_country_code'] ?? '+60'), $code) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Upload numbers</button>
                    <a class="btn btn-light" href="widget-preview.php?id=<?= (int) $selectedWidgetId ?>">Preview</a>
                </div>
            </form>
        <?php else: ?>
            <form class="settings-form" method="post" action="update-phone-numbers.php">
                <?= csrf_field() ?>
                <input type="hidden" name="widget_id" value="<?= (int) $selectedWidgetId ?>">
                <div class="form-grid two-columns">
                    <label>
                        <span>Country code</span>
                        <select name="whatsapp_country_code">
                            <?php foreach (country_codes() as $code => $label): ?>
                                <option value="<?= e($code) ?>"<?= selected((string) ($widget['whatsapp_country_code'] ?? '+60'), $code) ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Phone number</span>
                        <input type="text" name="whatsapp_number" value="<?= e((string) ($widget['whatsapp_number'] ?? '')) ?>" required>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save number</button>
                    <a class="btn btn-light" href="widget-preview.php?id=<?= (int) $selectedWidgetId ?>">Preview</a>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($importedNumbers !== []): ?>
            <div class="embed-box">
                <div class="panel-heading">
                    <h3>Imported numbers</h3>
                </div>
                <div class="table-wrap">
                    <table class="widget-table">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th>Number</th>
                                <th>Label</th>
                                <th>Full number</th>
                                <?php if (!empty($widget['use_random_numbers'])): ?>
                                    <th></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($importedNumbers as $index => $number): ?>
                                <tr>
                                    <td><?= e((string) ($number['country_code'] ?? '')) ?></td>
                                    <td><?= e((string) ($number['number'] ?? '')) ?></td>
                                    <td><?= e((string) ($number['label'] ?? '')) ?></td>
                                    <td><?= e((string) ($number['full_number'] ?? '')) ?></td>
                                    <?php if (!empty($widget['use_random_numbers'])): ?>
                                        <td>
                                            <form method="post" action="update-phone-numbers.php">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="widget_id" value="<?= (int) $selectedWidgetId ?>">
                                                <input type="hidden" name="action" value="remove_number">
                                                <input type="hidden" name="number_index" value="<?= (int) $index ?>">
                                                <button type="submit" class="btn btn-small btn-danger-soft">Remove</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
