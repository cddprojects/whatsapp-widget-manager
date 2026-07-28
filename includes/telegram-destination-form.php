<?php
declare(strict_types=1);

/**
 * Shared Telegram destination form fields.
 *
 * Expects:
 * - $telegramDestination (array|null)
 * - $telegramFormId (string)
 */
$telegramDestination = is_array($telegramDestination ?? null) ? $telegramDestination : [];
$telegramFormId = (string) ($telegramFormId ?? 'telegram-destination-form');
$type = (string) ($telegramDestination['destination_type'] ?? TELEGRAM_DESTINATION_USERNAME);
$value = (string) ($telegramDestination['destination_value'] ?? '');
if (in_array($type, [TELEGRAM_DESTINATION_USERNAME, TELEGRAM_DESTINATION_BOT], true) && $value !== '') {
    $value = '@' . ltrim($value, '@');
}
$isActive = array_key_exists('is_active', $telegramDestination)
    ? !empty($telegramDestination['is_active'])
    : true;
?>
<div class="form-grid" data-telegram-destination-fields>
    <label>
        <span><?= e(t('telegram.label.display_name')) ?></span>
        <input
            type="text"
            name="display_name"
            value="<?= e((string) ($telegramDestination['display_name'] ?? '')) ?>"
            maxlength="120"
            placeholder="<?= e(t('telegram.placeholder.display_name')) ?>"
            data-telegram-field="display_name"
        >
        <small class="field-helper" data-telegram-error="display_name" hidden></small>
    </label>

    <label>
        <span><?= e(t('telegram.label.destination_type')) ?></span>
        <select name="destination_type" data-telegram-field="destination_type" data-telegram-type-select>
            <?php foreach (telegram_destination_types() as $option): ?>
                <option value="<?= e($option) ?>"<?= selected($type, $option) ?>>
                    <?= e(t('telegram.type.' . $option)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="field-helper" data-telegram-error="destination_type" hidden></small>
    </label>

    <label>
        <span data-telegram-value-label><?= e(t('telegram.label.username_or_link')) ?></span>
        <input
            type="text"
            name="destination_value"
            value="<?= e($value) ?>"
            required
            autocomplete="off"
            data-telegram-field="destination_value"
            placeholder="@example_support"
        >
        <small class="field-helper" data-telegram-value-help><?= e(t('telegram.help.username')) ?></small>
        <small class="field-error" data-telegram-error="destination_value" hidden></small>
    </label>

    <label data-telegram-bot-start-wrap<?= $type === TELEGRAM_DESTINATION_BOT ? '' : ' hidden' ?>>
        <span><?= e(t('telegram.label.bot_start_parameter')) ?></span>
        <input
            type="text"
            name="bot_start_parameter"
            value="<?= e((string) ($telegramDestination['bot_start_parameter'] ?? '')) ?>"
            maxlength="64"
            data-telegram-field="bot_start_parameter"
            placeholder="offer123"
        >
        <small class="field-helper"><?= e(t('telegram.help.bot_start')) ?></small>
        <small class="field-error" data-telegram-error="bot_start_parameter" hidden></small>
    </label>

    <label class="checkbox-row">
        <input type="checkbox" name="is_active" value="1"<?= $isActive ? ' checked' : '' ?>>
        <span><?= e(t('telegram.label.active')) ?></span>
    </label>
</div>
