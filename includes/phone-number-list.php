<?php
declare(strict_types=1);

$phoneNumbers = $phoneNumbers ?? [];
$fieldPrefix = (string) ($fieldPrefix ?? 'widget_numbers');
$emptyMessage = (string) ($emptyMessage ?? 'No numbers added yet. Click Add number to get started.');
$listId = (string) ($listId ?? 'phone-number-list');
?>

<div class="phone-numbers-card" data-phone-numbers-card>
    <div class="panel-heading">
        <div>
            <h3>Phone numbers</h3>
            <p>Add one or more WhatsApp numbers. If more than one number is added, the widget will rotate numbers randomly on each click.</p>
        </div>
        <button type="button" class="btn btn-small btn-light" data-add-phone-number>Add number</button>
    </div>

    <div
        class="phone-number-list"
        data-phone-number-list
        data-field-prefix="<?= e($fieldPrefix) ?>"
        id="<?= e($listId) ?>"
    >
        <?php if ($phoneNumbers === []): ?>
            <div class="empty-state compact-empty" data-phone-empty-state>
                <p><?= e($emptyMessage) ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($phoneNumbers as $index => $row): ?>
                <?php
                $countryCode = (string) ($row['country_code'] ?? '+60');
                $phoneNumber = (string) ($row['number'] ?? '');
                $inputId = $listId . '-country-' . (int) $index;
                ?>
                <div class="phone-number-row" data-phone-number-row>
                    <?= render_country_code_search_input(
                        $inputId,
                        $countryCode,
                        $fieldPrefix . '[' . (int) $index . '][country_code]'
                    ) ?>
                    <label class="phone-number-input">
                        <span class="sr-only">Phone number</span>
                        <input
                            type="tel"
                            name="<?= e($fieldPrefix) ?>[<?= (int) $index ?>][number]"
                            value="<?= e($phoneNumber) ?>"
                            placeholder="123456789"
                            autocomplete="tel"
                            data-row-phone
                        >
                    </label>
                    <button type="button" class="btn btn-small btn-danger-soft" data-remove-phone-number>Delete</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <template id="<?= e($listId) ?>-template">
        <div class="phone-number-row" data-phone-number-row>
            <div class="country-code-field" data-country-code-field>
                <input type="text" class="country-code-search" list="<?= e($listId) ?>-country-options" value="MY +60 Malaysia" placeholder="Search country or code" autocomplete="off" data-country-search>
                <input type="hidden" value="+60" data-country-value>
                <datalist id="<?= e($listId) ?>-country-options"></datalist>
            </div>
            <label class="phone-number-input">
                <span class="sr-only">Phone number</span>
                <input type="tel" value="" placeholder="123456789" autocomplete="tel" data-row-phone>
            </label>
            <button type="button" class="btn btn-small btn-danger-soft" data-remove-phone-number>Delete</button>
        </div>
    </template>
</div>
