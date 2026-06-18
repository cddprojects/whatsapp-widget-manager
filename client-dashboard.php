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

$pageTitle = 'My WhatsApp Number';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Client account</p>
    <h1>My WhatsApp Number</h1>
    <p>Update the WhatsApp numbers used by your widget.</p>
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
            <div><span class="meta-label">Active numbers</span><strong><?= format_whatsapp_display($widget) ?></strong></div>
        </div>
    </section>

    <section class="settings-card client-phone-card">
        <div class="tab-bar">
            <a class="tab-link<?= $activeTab === 'manual' ? ' is-active' : '' ?>" href="client-dashboard.php?widget_id=<?= (int) $selectedWidgetId ?>&tab=manual">Phone Numbers</a>
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

                <?php
                $phoneNumbers = $activeNumbers;
                $fieldPrefix = 'manual_numbers';
                $listId = 'client-phone-number-list';
                require __DIR__ . '/includes/phone-number-list.php';
                ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Numbers</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <script type="application/json" id="country-code-data"><?= json_for_html(country_code_options()) ?></script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
