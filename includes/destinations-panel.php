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
$whatsappCount = count(widget_phone_list($widget));
$telegramDestinations = $widgetId > 0 ? list_channel_destinations($widgetId, WIDGET_CHANNEL_TELEGRAM, true, false) : [];
$telegramCount = count($telegramDestinations);
$telegramEnabled = !empty($channelConfig['telegram']);
$activeDestTab = $destinationsTelegramOnly
    ? 'telegram'
    : (string) ($_GET['dest_tab'] ?? ($telegramEnabled && $whatsappCount === 0 ? 'telegram' : 'whatsapp'));
if (!in_array($activeDestTab, ['whatsapp', 'telegram'], true)) {
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

        <div class="tab-bar channel-dest-tabs" role="tablist">
            <button type="button" class="tab-link<?= $activeDestTab === 'whatsapp' ? ' is-active' : '' ?>" data-dest-tab-target="whatsapp" role="tab">
                <?= e(t('channel.whatsapp')) ?> (<?= (int) $whatsappCount ?>)
            </button>
            <?php if ($telegramEnabled || $destinationsContext === 'admin'): ?>
                <button type="button" class="tab-link<?= $activeDestTab === 'telegram' ? ' is-active' : '' ?>" data-dest-tab-target="telegram" role="tab">
                    <?= e(t('channel.telegram')) ?> (<?= (int) $telegramCount ?>)
                </button>
            <?php endif; ?>
        </div>

        <div class="dest-tab-panel<?= $activeDestTab === 'whatsapp' ? ' is-active' : '' ?>" data-dest-tab-panel="whatsapp">
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

    <div class="dest-tab-panel<?= ($activeDestTab === 'telegram' || $destinationsTelegramOnly) ? ' is-active' : '' ?>" data-dest-tab-panel="telegram">
        <?php if (!$telegramEnabled): ?>
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
                <button type="button" class="btn btn-primary" data-open-telegram-modal>
                    <?= e(t('telegram.add_destination')) ?>
                </button>
            </div>

            <?php if ($telegramDestinations === []): ?>
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
                                        <form method="post" action="<?= e($saveUrl) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="widget_id" value="<?= $widgetId ?>">
                                            <input type="hidden" name="destination_id" value="<?= (int) $destination['id'] ?>">
                                            <input type="hidden" name="destination_action" value="toggle">
                                            <?php if (empty($destination['is_active'])): ?>
                                                <input type="hidden" name="is_active" value="1">
                                                <button type="submit" class="action-menu-item"><?= e(t('telegram.enable')) ?></button>
                                            <?php else: ?>
                                                <button type="submit" class="action-menu-item"><?= e(t('telegram.disable')) ?></button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="post" action="<?= e($saveUrl) ?>" onsubmit="return confirm(<?= json_encode(t('telegram.confirm_delete')) ?>);">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="widget_id" value="<?= $widgetId ?>">
                                            <input type="hidden" name="destination_id" value="<?= (int) $destination['id'] ?>">
                                            <input type="hidden" name="destination_action" value="delete">
                                            <button type="submit" class="action-menu-item action-danger"><?= e(t('button.delete')) ?></button>
                                        </form>
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

<dialog class="ctcw-modal" data-telegram-modal>
    <form method="post" action="<?= e($saveUrl) ?>" class="settings-form" data-telegram-destination-form>
        <?= csrf_field() ?>
        <input type="hidden" name="widget_id" value="<?= $widgetId ?>">
        <input type="hidden" name="destination_id" value="" data-telegram-destination-id>
        <input type="hidden" name="destination_action" value="save">
        <div class="card-header-row">
            <div>
                <h3 data-telegram-modal-title><?= e(t('telegram.add_destination')) ?></h3>
                <p class="helper-text"><?= e(t('telegram.modal_help')) ?></p>
            </div>
            <button type="button" class="btn btn-light" data-close-telegram-modal><?= e(t('button.close')) ?></button>
        </div>
        <?php
        $telegramDestination = [];
        $telegramFormId = 'telegram-destination-form';
        require __DIR__ . '/telegram-destination-form.php';
        ?>
        <div class="form-actions">
            <button type="button" class="btn btn-light" data-close-telegram-modal><?= e(t('button.cancel')) ?></button>
            <button type="submit" class="btn btn-primary"><?= e(t('button.save')) ?></button>
        </div>
    </form>
</dialog>
