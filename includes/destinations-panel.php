<?php
declare(strict_types=1);

/**
 * Unified destinations panel with WhatsApp / Telegram tabs.
 *
 * Expects:
 * - $widget (array)
 * - $destinationsContext = 'admin'|'client'
 * - $destinationsTelegramOnly (bool) optional — client telegram tab
 */
$destinationsContext = ($destinationsContext ?? 'admin') === 'client' ? 'client' : 'admin';
$destinationsTelegramOnly = !empty($destinationsTelegramOnly);
$widgetId = (int) ($widget['id'] ?? 0);
$channelConfig = $widgetId > 0 ? get_widget_channel_config($widgetId, $widget) : widget_channel_mode_defaults() + ['rows' => []];
$channelMode = (string) ($widget['channel_mode'] ?? $channelConfig['modes'] ?? 'whatsapp_only');
$whatsappCount = count(widget_phone_list($widget));
$telegramDestinations = $widgetId > 0 ? list_channel_destinations($widgetId, WIDGET_CHANNEL_TELEGRAM, true, false) : [];
$telegramCount = count($telegramDestinations);
$whatsappEnabled = !empty($channelConfig['whatsapp']) || $channelMode === 'whatsapp_only' || $channelMode === 'both';
$telegramEnabled = !empty($channelConfig['telegram']) || $channelMode === 'telegram_only' || $channelMode === 'both';
if ($channelMode === 'whatsapp_only') {
    $telegramEnabled = false;
    $whatsappEnabled = true;
} elseif ($channelMode === 'telegram_only') {
    $whatsappEnabled = false;
    $telegramEnabled = true;
} elseif ($channelMode === 'both') {
    $whatsappEnabled = true;
    $telegramEnabled = true;
}
$activeDestTab = $destinationsTelegramOnly
    ? 'telegram'
    : (string) ($_GET['dest_tab'] ?? (!$whatsappEnabled && $telegramEnabled ? 'telegram' : 'whatsapp'));
if (!in_array($activeDestTab, ['whatsapp', 'telegram'], true)) {
    $activeDestTab = 'whatsapp';
}
if (!$whatsappEnabled && $telegramEnabled) {
    $activeDestTab = 'telegram';
}
if ($whatsappEnabled && !$telegramEnabled) {
    $activeDestTab = 'whatsapp';
}
$saveUrl = app_url('telegram-destination-save.php');
$panelTag = $destinationsTelegramOnly ? 'div' : 'section';
$panelClass = $destinationsTelegramOnly ? 'client-telegram-panel' : 'settings-card';
?>

<<?= $panelTag ?> class="<?= e($panelClass) ?>" <?php if (!$destinationsTelegramOnly): ?>data-settings-panel="destinations" id="destinations"<?php endif; ?> data-destinations-panel>
    <?php if (!$destinationsTelegramOnly): ?>
        <div class="section-title">
            <span><?= $destinationsContext === 'admin' ? '2' : '•' ?></span>
            <div>
                <h2><?= e(t('section.destinations.title')) ?></h2>
                <p><?= e(t('section.destinations.description')) ?></p>
            </div>
        </div>

        <div class="tab-bar channel-dest-tabs" role="tablist" data-channel-dest-tabs data-channel-mode="<?= e($channelMode) ?>">
            <button
                type="button"
                class="tab-link<?= $activeDestTab === 'whatsapp' ? ' is-active' : '' ?><?= !$whatsappEnabled ? ' is-channel-disabled' : '' ?>"
                data-dest-tab-target="whatsapp"
                role="tab"
                <?= !$whatsappEnabled ? 'hidden' : '' ?>
            >
                <?= e(t('channel.whatsapp')) ?> (<?= (int) $whatsappCount ?>)
            </button>
            <?php if ($telegramEnabled || $destinationsContext === 'admin'): ?>
                <button
                    type="button"
                    class="tab-link<?= $activeDestTab === 'telegram' ? ' is-active' : '' ?><?= !$telegramEnabled ? ' is-channel-disabled' : '' ?>"
                    data-dest-tab-target="telegram"
                    role="tab"
                    <?= !$telegramEnabled ? 'hidden' : '' ?>
                >
                    <?= e(t('channel.telegram')) ?> (<?= (int) $telegramCount ?>)
                </button>
            <?php endif; ?>
        </div>

        <div class="dest-tab-panel<?= $activeDestTab === 'whatsapp' ? ' is-active' : '' ?><?= !$whatsappEnabled ? ' is-channel-disabled' : '' ?>" data-dest-tab-panel="whatsapp">
            <?php if ($destinationsContext === 'admin'): ?>
                <?php
                $allowEmptyPhones = true;
                require __DIR__ . '/phone-number-list.php';
                $destinationNumbers = widget_phone_list($widget);
                $destinationCount = count($destinationNumbers);
                $destinationMethod = effective_destination_selection_method($widget, $destinationCount);
                ?>
                <div class="ctcw-destination-distribution" data-destination-distribution-panel<?= $destinationCount < 2 ? ' hidden' : '' ?>>
                    <label>
                        <span><?= e(t('distribution.label')) ?></span>
                        <select name="destination_selection_method" data-destination-selection-method>
                            <option value="round_robin"<?= selected($destinationMethod, 'round_robin') ?>><?= e(t('distribution.option_round_robin')) ?></option>
                            <option value="random"<?= selected($destinationMethod, 'random') ?>><?= e(t('distribution.option_random')) ?></option>
                        </select>
                    </label>
                    <p class="helper-text" data-destination-method-help="round_robin"<?= $destinationMethod === 'round_robin' ? '' : ' hidden' ?>><?= e(t('distribution.help_round_robin')) ?></p>
                    <p class="helper-text" data-destination-method-help="random"<?= $destinationMethod === 'random' ? '' : ' hidden' ?>><?= e(t('distribution.help_random')) ?></p>
                    <p class="ctcw-destination-summary" data-destination-summary-text><?= e(destination_distribution_label($widget, $destinationCount)) ?></p>
                </div>
                <p class="ctcw-destination-single-summary" data-destination-single-summary<?= $destinationCount === 1 ? '' : ' hidden' ?>><?= e(t('distribution.one_number')) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="dest-tab-panel<?= ($activeDestTab === 'telegram' || $destinationsTelegramOnly) ? ' is-active' : '' ?><?= (!$telegramEnabled && !$destinationsTelegramOnly) ? ' is-channel-disabled' : '' ?>" data-dest-tab-panel="telegram">
        <?php if (!$telegramEnabled && !$destinationsTelegramOnly): ?>
            <div class="empty-state">
                <h3><?= e(t('telegram.disabled_title')) ?></h3>
                <p><?= e(t('telegram.disabled_by_admin')) ?></p>
            </div>
        <?php else: ?>
            <div class="card-header-row">
                <div>
                    <h3><?= e(t('telegram.destinations_title')) ?></h3>
                    <p class="helper-text"><?= e(t('telegram.destinations_help')) ?></p>
                </div>
                <?php if ($widgetId > 0): ?>
                    <button type="button" class="btn btn-primary" data-open-telegram-modal>
                        <?= e(t('telegram.add_destination')) ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($widgetId <= 0): ?>
                <div class="empty-state" data-telegram-empty-state>
                    <h3><?= e(t('telegram.save_widget_first_title')) ?></h3>
                    <p><?= e(t('telegram.save_widget_first_description')) ?></p>
                </div>
            <?php elseif ($telegramDestinations === []): ?>
                <div class="empty-state" data-telegram-empty-state>
                    <h3><?= e(t('telegram.empty_title')) ?></h3>
                    <p><?= e(t('telegram.empty_description')) ?></p>
                    <button type="button" class="btn btn-primary" data-open-telegram-modal>
                        <?= e(t('telegram.add_destination')) ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="telegram-destination-list">
                    <?php foreach ($telegramDestinations as $destination): ?>
                        <?php
                        $built = build_telegram_redirect_url($destination);
                        $testUrl = $built['ok'] ? (string) $built['url'] : '';
                        $displayValue = format_telegram_destination_display($destination);
                        ?>
                        <article class="telegram-destination-card">
                            <div class="telegram-destination-main">
                                <strong><?= e((string) ($destination['display_name'] ?: $displayValue)) ?></strong>
                                <div class="telegram-destination-meta">
                                    <span class="status-pill"><?= e(t('telegram.type.' . $destination['destination_type'])) ?></span>
                                    <code><?= e($displayValue) ?></code>
                                    <span class="status-pill <?= !empty($destination['is_active']) ? 'status-active' : 'status-disabled' ?>">
                                        <?= e(!empty($destination['is_active']) ? t('status.active') : t('status.disabled')) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="telegram-destination-actions">
                                <?php if ($testUrl !== ''): ?>
                                    <a class="btn btn-light btn-small" href="<?= e($testUrl) ?>" target="_blank" rel="noopener noreferrer">
                                        <?= e(t('telegram.test_link')) ?>
                                    </a>
                                <?php endif; ?>
                                <button
                                    type="button"
                                    class="btn btn-light btn-small"
                                    data-edit-telegram-destination="<?= e((string) json_encode([
                                        'id' => (int) $destination['id'],
                                        'display_name' => (string) $destination['display_name'],
                                        'destination_type' => (string) $destination['destination_type'],
                                        'destination_value' => $displayValue,
                                        'bot_start_parameter' => (string) ($destination['bot_start_parameter'] ?? ''),
                                        'is_active' => !empty($destination['is_active']),
                                    ], JSON_UNESCAPED_UNICODE)) ?>"
                                >
                                    <?= e(t('button.edit')) ?>
                                </button>
                                <div class="action-menu" data-action-menu>
                                    <button type="button" class="action-menu-toggle" aria-label="<?= e(t('action.more')) ?>">⋯</button>
                                    <div class="action-menu-panel">
                                        <button
                                            type="button"
                                            class="action-menu-item"
                                            data-telegram-dest-action="toggle"
                                            data-destination-id="<?= (int) $destination['id'] ?>"
                                            data-is-active="<?= empty($destination['is_active']) ? '1' : '0' ?>"
                                            data-widget-id="<?= $widgetId ?>"
                                        >
                                            <?= e(empty($destination['is_active']) ? t('telegram.enable') : t('telegram.disable')) ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="action-menu-item action-danger"
                                            data-telegram-dest-action="delete"
                                            data-destination-id="<?= (int) $destination['id'] ?>"
                                            data-widget-id="<?= $widgetId ?>"
                                        >
                                            <?= e(t('button.delete')) ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</<?= $panelTag ?>>

<?php
// Never nest the Telegram modal form inside the widget edit form (HTML forbids nested forms).
// Admin widget form defers rendering until after </form>; client pages render inline.
$deferTelegramModal = $destinationsContext === 'admin' && empty($destinationsTelegramOnly);
if (!$deferTelegramModal) {
    require __DIR__ . '/telegram-destination-modal.php';
}
?>
