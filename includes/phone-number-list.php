<?php
declare(strict_types=1);

$phoneNumbers = $phoneNumbers ?? [];
$fieldPrefix = (string) ($fieldPrefix ?? 'widget_numbers');
$emptyMessage = (string) ($emptyMessage ?? t('empty.no_phone_numbers'));
$listId = (string) ($listId ?? 'phone-number-list');
$hasPhoneNumbers = $phoneNumbers !== [];
?>

<div class="phone-numbers-card ctcw-phone-numbers-card" data-phone-numbers-card>
    <div class="panel-heading">
        <div>
            <h3><?= e(t('phone.numbers_title')) ?></h3>
            <p><?= e(t('phone.numbers_description')) ?></p>
        </div>
        <button type="button" class="btn btn-small btn-light" data-add-phone-number><?= e(t('button.add_number')) ?></button>
    </div>

    <div class="ctcw-phone-bulk-toolbar" data-phone-bulk-toolbar<?= $hasPhoneNumbers ? '' : ' hidden' ?>>
        <div class="ctcw-phone-bulk-actions">
            <label class="ctcw-phone-select-all" for="ctcwSelectAllPhones">
                <input type="checkbox" id="ctcwSelectAllPhones" data-select-all-phones aria-label="<?= e(t('phone.select_all_aria')) ?>">
                <span><?= e(t('phone.select_all')) ?></span>
            </label>
            <span id="ctcwSelectedPhoneCount" class="ctcw-selected-phone-count" data-selected-phone-count><?= e(t('phone.selected_count', ['count' => '0'])) ?></span>
        </div>
        <button
            type="button"
            id="ctcwDeleteSelectedPhones"
            class="btn btn-small btn-danger-soft ctcw-delete-selected-btn"
            data-delete-selected-phones
            disabled
            aria-label="<?= e(t('phone.delete_selected_aria')) ?>"
        >
            <?= e(t('button.delete_selected')) ?>
        </button>
    </div>

    <p class="ctcw-phone-bulk-error" data-phone-bulk-error hidden><?= e(t('phone.min_one_required')) ?></p>

    <div
        class="phone-number-list ctcw-phone-list"
        data-phone-number-list
        data-field-prefix="<?= e($fieldPrefix) ?>"
        id="<?= e($listId) ?>"
    >
        <?php if (!$hasPhoneNumbers): ?>
            <div class="empty-state compact-empty" data-phone-empty-state>
                <p><?= e($emptyMessage) ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($phoneNumbers as $index => $row): ?>
                <?php
                $countryCode = normalize_dial_code((string) ($row['country_code'] ?? '+60')) ?: '+60';
                $phoneNumber = (string) ($row['number'] ?? '');
                $selectLabel = t('phone.select_number_with_value', ['number' => $countryCode . $phoneNumber]);
                ?>
                <div class="phone-number-row ctcw-phone-row" data-phone-number-row>
                    <label class="ctcw-phone-select-wrap">
                        <span class="sr-only"><?= e($selectLabel) ?></span>
                        <input type="checkbox" class="ctcw-phone-select" aria-label="<?= e($selectLabel) ?>">
                    </label>
                    <?= render_calling_code_picker(
                        $countryCode,
                        $fieldPrefix . '[' . (int) $index . '][country_code]'
                    ) ?>
                    <label class="phone-number-input">
                        <span class="sr-only"><?= e(t('label.phone_number')) ?></span>
                        <input
                            type="tel"
                            name="<?= e($fieldPrefix) ?>[<?= (int) $index ?>][number]"
                            value="<?= e($phoneNumber) ?>"
                            placeholder="<?= e(t('placeholder.phone_without_code')) ?>"
                            autocomplete="tel"
                            data-row-phone
                            class="ctcw-phone-number-input"
                        >
                    </label>
                    <button type="button" class="btn btn-small btn-danger-soft" data-remove-phone-number><?= e(t('button.delete')) ?></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <template id="<?= e($listId) ?>-template">
        <div class="phone-number-row ctcw-phone-row" data-phone-number-row>
            <label class="ctcw-phone-select-wrap">
                <span class="sr-only"><?= e(t('phone.select_number')) ?></span>
                <input type="checkbox" class="ctcw-phone-select" aria-label="<?= e(t('phone.select_number')) ?>">
            </label>
            <div class="ctcw-calling-code-picker">
                <input type="hidden" class="ctcw-calling-code-value" value="+60">
                <button type="button" class="ctcw-calling-code-trigger" aria-haspopup="listbox" aria-expanded="false" aria-label="<?= e(t('phone.calling_code')) ?>">
                    <span class="ctcw-calling-code-label">+60</span>
                    <span class="ctcw-calling-code-caret" aria-hidden="true">▼</span>
                </button>
                <div class="ctcw-calling-code-menu" hidden>
                    <input type="search" class="ctcw-calling-code-search" placeholder="<?= e(t('placeholder.search_calling_code')) ?>" autocomplete="off" aria-label="<?= e(t('phone.search_calling_code')) ?>">
                    <div class="ctcw-calling-code-options" role="listbox"></div>
                </div>
            </div>
            <label class="phone-number-input">
                <span class="sr-only"><?= e(t('label.phone_number')) ?></span>
                <input type="tel" value="" placeholder="<?= e(t('placeholder.phone_without_code')) ?>" autocomplete="tel" data-row-phone class="ctcw-phone-number-input">
            </label>
            <button type="button" class="btn btn-small btn-danger-soft" data-remove-phone-number><?= e(t('button.delete')) ?></button>
        </div>
    </template>

    <div class="ctcw-phone-bulk-modal" data-phone-bulk-modal hidden>
        <div class="ctcw-phone-bulk-modal-backdrop" data-phone-bulk-modal-close></div>
        <div class="ctcw-phone-bulk-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ctcwPhoneBulkModalTitle">
            <h3 id="ctcwPhoneBulkModalTitle"><?= e(t('phone.bulk_delete_title')) ?></h3>
            <p data-phone-bulk-modal-message></p>
            <div class="form-actions">
                <button type="button" class="btn btn-light" data-phone-bulk-modal-cancel><?= e(t('button.cancel')) ?></button>
                <button type="button" class="btn btn-danger-soft" data-phone-bulk-modal-confirm><?= e(t('button.delete_selected')) ?></button>
            </div>
        </div>
    </div>
</div>
