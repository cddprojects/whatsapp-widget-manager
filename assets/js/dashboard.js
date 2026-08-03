(function () {
    'use strict';

    function ctcwI18n(key, replace) {
        var bag = window.CTCW_I18N || {};
        var text = bag[key] || key;

        if (replace) {
            Object.keys(replace).forEach(function (name) {
                text = text.split('{' + name + '}').join(String(replace[name]));
            });
        }

        return text;
    }

    window.ctcwI18n = ctcwI18n;

    var i18nNode = document.getElementById('ctcw-i18n');
    if (i18nNode) {
        try {
            window.CTCW_I18N = JSON.parse(i18nNode.textContent || '{}');
        } catch (error) {
            window.CTCW_I18N = {};
        }
    }

    function updateCodeLines(textarea) {
        var editor = textarea.closest('.code-editor');
        if (!editor) return;
        var lines = editor.querySelector('.code-lines');
        if (!lines) return;
        var count = (textarea.value.match(/\n/g) || []).length + 1;
        lines.textContent = Array.from({ length: count }, function (_, i) { return i + 1; }).join('\n');
    }

    function refreshMobileFields() {
        var checkbox = document.querySelector('[data-same-mobile]');
        var fields = document.querySelector('[data-mobile-position-fields]');
        if (!checkbox || !fields) return;
        fields.classList.toggle('is-disabled', checkbox.checked);
        fields.querySelectorAll('input, select').forEach(function (field) {
            field.disabled = checkbox.checked;
        });
    }

    function refreshBusinessHours() {
        var modeSelect = document.querySelector('[data-business-hours-mode]');
        var alwaysOpenState = document.querySelector('[data-always-open-state]');
        var offlineMessageGroup = document.querySelector('[data-offline-message-group]');
        var customHoursGroup = document.querySelector('[data-custom-business-hours-group]');
        var helperClosed = document.querySelector('[data-offline-helper-closed]');
        var helperCustom = document.querySelector('[data-offline-helper-custom]');

        if (!modeSelect) {
            return;
        }

        var mode = modeSelect.value;

        if (alwaysOpenState) {
            alwaysOpenState.hidden = mode !== 'always_open';
        }
        if (offlineMessageGroup) {
            offlineMessageGroup.hidden = mode === 'always_open';
        }
        if (customHoursGroup) {
            customHoursGroup.hidden = mode !== 'custom';
        }
        if (helperClosed) {
            helperClosed.hidden = mode !== 'always_closed';
        }
        if (helperCustom) {
            helperCustom.hidden = mode !== 'custom';
        }

        document.querySelectorAll('[data-business-day-row]').forEach(function (row) {
            var dayEnabled = row.querySelector('[data-day-enabled]');
            var timeInputs = row.querySelectorAll('input[type="time"]');
            var disableTimes = mode === 'custom' && dayEnabled && !dayEnabled.checked;

            row.classList.toggle('is-disabled', disableTimes);
            timeInputs.forEach(function (input) {
                input.disabled = disableTimes;
            });
        });
    }

    function initBusinessHoursRows() {
        document.querySelectorAll('[data-day-enabled]').forEach(function (checkbox) {
            checkbox.addEventListener('change', refreshBusinessHours);
        });
    }

    function setGreetingSectionFieldsDisabled(section, disabled) {
        if (!section) {
            return;
        }

        section.querySelectorAll('input, select, textarea, button').forEach(function (field) {
            if (field.type === 'hidden') {
                return;
            }
            field.disabled = disabled;
        });
    }

    function refreshGreetingDialogSettings() {
        var greetingToggle = document.querySelector('[data-role="greeting-dialog-toggle"]');
        var greetingSettings = document.querySelector('[data-role="greeting-dialog-settings"]');
        var captureToggle = document.querySelector('[data-role="phone-capture-toggle"]');
        var captureSettings = document.querySelector('[data-role="phone-capture-settings"]');
        var forceToggle = document.querySelector('[data-role="force-phone-toggle"]');
        var optionalRequiredSettings = document.querySelector('[data-greeting-optional-required-settings]');
        var forceRequiredSettings = document.querySelector('[data-greeting-force-required-settings]');
        var requiredToggle = document.querySelector('[data-greeting-phone-required]');
        var consentToggle = document.querySelector('[data-role="consent-notice-toggle"]');
        var consentSettings = document.querySelector('[data-role="consent-notice-settings"]');

        if (!greetingToggle || !greetingSettings) {
            return;
        }

        var greetingEnabled = greetingToggle.checked;
        greetingToggle.setAttribute('aria-expanded', greetingEnabled ? 'true' : 'false');
        greetingSettings.hidden = !greetingEnabled;

        if (!greetingEnabled) {
            setGreetingSectionFieldsDisabled(greetingSettings, true);
            if (captureSettings) {
                captureSettings.hidden = true;
            }
            if (consentSettings) {
                consentSettings.hidden = true;
            }
            return;
        }

        setGreetingSectionFieldsDisabled(greetingSettings, false);

        var captureEnabled = !!(captureToggle && captureToggle.checked);
        if (captureToggle) {
            captureToggle.setAttribute('aria-expanded', captureEnabled ? 'true' : 'false');
        }

        if (captureSettings) {
            captureSettings.hidden = !captureEnabled;
        }

        var consentEnabled = !!(consentToggle && consentToggle.checked);
        if (consentToggle) {
            consentToggle.setAttribute('aria-expanded', consentEnabled ? 'true' : 'false');
        }
        if (consentSettings) {
            consentSettings.hidden = !consentEnabled;
        }

        if (!captureEnabled) {
            setGreetingSectionFieldsDisabled(captureSettings, true);
            refreshGreetingOpenBehaviorFields();
            return;
        }

        setGreetingSectionFieldsDisabled(captureSettings, false);

        var forceEnabled = !!(forceToggle && forceToggle.checked);
        if (forceToggle) {
            forceToggle.setAttribute('aria-expanded', forceEnabled ? 'true' : 'false');
        }

        if (optionalRequiredSettings) {
            optionalRequiredSettings.hidden = forceEnabled;
        }

        if (forceRequiredSettings) {
            forceRequiredSettings.hidden = !forceEnabled;
        }

        if (requiredToggle) {
            if (forceEnabled) {
                requiredToggle.checked = true;
                requiredToggle.disabled = true;
            } else {
                requiredToggle.disabled = false;
            }
        }

        refreshGreetingOpenBehaviorFields();
    }

    function refreshGreetingOpenBehaviorFields() {
        var behaviorSelect = document.querySelector('[data-role="greeting-open-behavior"]');
        var delayField = document.querySelector('[data-role="greeting-delay-field"]');
        var autoHelper = document.querySelector('[data-role="greeting-open-behavior-helper-auto"]');
        var clickHelper = document.querySelector('[data-role="greeting-open-behavior-helper-click"]');

        if (!behaviorSelect) {
            return;
        }

        var clickOnly = behaviorSelect.value === 'click_only';

        if (delayField) {
            delayField.hidden = clickOnly;
        }

        if (autoHelper) {
            autoHelper.hidden = clickOnly;
        }

        if (clickHelper) {
            clickHelper.hidden = !clickOnly;
        }
    }

    function refreshGreetingCaptureOptions() {
        refreshGreetingDialogSettings();
    }

    function isValidPhoneSubmitButtonId(value) {
        return value === '' || /^[A-Za-z][A-Za-z0-9_-]{0,79}$/.test(value);
    }

    function validatePhoneSubmitButtonIdInput() {
        var input = document.querySelector('[data-phone-submit-button-id-input]');
        var error = document.querySelector('[data-phone-submit-button-id-error]');
        if (!input || !error) {
            return true;
        }

        var captureToggle = document.querySelector('[data-role="phone-capture-toggle"]');
        if (!captureToggle || !captureToggle.checked) {
            error.hidden = true;
            error.textContent = '';
            return true;
        }

        var value = String(input.value || '').trim();
        if (isValidPhoneSubmitButtonId(value)) {
            error.hidden = true;
            error.textContent = '';
            return true;
        }

        error.textContent = ctcwI18n('validation.phone_submit_button_id_invalid');
        error.hidden = false;
        input.focus();
        return false;
    }

    function initPrefilledMessageVariables() {
        var textarea = document.querySelector('[data-prefilled-message-textarea]');
        if (!textarea) {
            return;
        }

        document.querySelectorAll('[data-variable-insert]').forEach(function (button) {
            button.addEventListener('click', function () {
                var token = button.getAttribute('data-variable-insert') || '';
                if (token === '') {
                    return;
                }

                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                var value = textarea.value;

                if (typeof start === 'number' && typeof end === 'number') {
                    textarea.value = value.slice(0, start) + token + value.slice(end);
                    var nextPos = start + token.length;
                    textarea.setSelectionRange(nextPos, nextPos);
                } else {
                    textarea.value = value + token;
                }

                textarea.focus();
            });
        });
    }

    function initPhoneSubmitButtonIdValidation() {
        var input = document.querySelector('[data-phone-submit-button-id-input]');
        var form = document.querySelector('[data-widget-form]');
        if (!input) {
            return;
        }

        input.addEventListener('input', function () {
            var value = String(input.value || '').trim();
            if (isValidPhoneSubmitButtonId(value)) {
                error.hidden = true;
                error.textContent = '';
            }
        });

        input.addEventListener('blur', function () {
            validatePhoneSubmitButtonIdInput();
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                if (!validatePhoneSubmitButtonIdInput()) {
                    event.preventDefault();
                }
            });
        }
    }

    function countPhoneRows(list) {
        return list.querySelectorAll('[data-phone-number-row]').length;
    }

    function getPhoneCard(list) {
        return list ? list.closest('[data-phone-numbers-card]') : null;
    }

    function phoneAllowsEmpty(card) {
        if (!card) {
            return false;
        }

        if (card.hasAttribute('data-allow-empty-phones')) {
            return true;
        }

        var form = card.closest('form');
        return !!(form && form.hasAttribute('data-allow-empty-phones'));
    }

    function canRemovePhoneRows(list, removeCount) {
        if (phoneAllowsEmpty(getPhoneCard(list))) {
            return true;
        }

        return countPhoneRows(list) - removeCount >= 1;
    }

    function setPhoneBulkToolbarVisible(card, visible) {
        if (!card) {
            return;
        }
        var toolbar = card.querySelector('[data-phone-bulk-toolbar]');
        if (toolbar) {
            toolbar.hidden = !visible;
        }
    }

    function updateBulkDeleteUI(card) {
        if (!card) {
            return;
        }

        var list = card.querySelector('[data-phone-number-list]');
        var checkboxes = list ? Array.from(list.querySelectorAll('.ctcw-phone-select')) : [];
        var selected = checkboxes.filter(function (checkbox) {
            return checkbox.checked;
        });
        var selectAll = card.querySelector('[data-select-all-phones]');
        var countEl = card.querySelector('[data-selected-phone-count]');
        var deleteButton = card.querySelector('[data-delete-selected-phones]');
        var errorEl = card.querySelector('[data-phone-bulk-error]');
        var total = checkboxes.length;
        var allowEmpty = phoneAllowsEmpty(card);

        if (countEl) {
            countEl.textContent = ctcwI18n('phone.selected_count', { count: selected.length });
        }

        if (deleteButton) {
            deleteButton.disabled = selected.length === 0 || (!allowEmpty && selected.length === total);
        }

        if (errorEl) {
            errorEl.hidden = allowEmpty || !(total > 0 && selected.length === total);
        }

        if (selectAll) {
            if (selected.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (selected.length === total) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }

        checkboxes.forEach(function (checkbox) {
            var row = checkbox.closest('[data-phone-number-row]');
            if (row) {
                row.classList.toggle('is-selected', checkbox.checked);
            }
        });
    }

    function closePhoneBulkModal(card) {
        var modal = card.querySelector('[data-phone-bulk-modal]');
        if (modal) {
            modal.hidden = true;
        }
    }

    function openPhoneBulkModal(card) {
        var list = card.querySelector('[data-phone-number-list]');
        var modal = card.querySelector('[data-phone-bulk-modal]');
        if (!list || !modal) {
            return;
        }

        var selectedCheckboxes = Array.from(list.querySelectorAll('.ctcw-phone-select:checked'));
        var count = selectedCheckboxes.length;
        var total = countPhoneRows(list);
        var allowEmpty = phoneAllowsEmpty(card);

        if (count === 0 || (!allowEmpty && count === total)) {
            updateBulkDeleteUI(card);
            return;
        }

        var message = modal.querySelector('[data-phone-bulk-modal-message]');
        var confirmButton = modal.querySelector('[data-phone-bulk-modal-confirm]');

        if (message) {
            message.textContent = allowEmpty && count === total
                ? ctcwI18n('phone.delete_last_confirm')
                : ctcwI18n('phone.bulk_delete_confirm', { count: count });
        }
        if (confirmButton) {
            confirmButton.textContent = ctcwI18n('phone.bulk_delete_button', { count: count });
        }

        modal.hidden = false;
        if (confirmButton) {
            confirmButton.focus();
        }
    }

    function removeSelectedPhoneRows(card) {
        var list = card.querySelector('[data-phone-number-list]');
        if (!list) {
            return;
        }

        var selectedCheckboxes = Array.from(list.querySelectorAll('.ctcw-phone-select:checked'));
        if (!canRemovePhoneRows(list, selectedCheckboxes.length)) {
            updateBulkDeleteUI(card);
            return;
        }

        selectedCheckboxes.forEach(function (checkbox) {
            var row = checkbox.closest('[data-phone-number-row]');
            if (row) {
                row.remove();
            }
        });

        renumberPhoneRows(list);
        showPhoneEmptyState(list);

        if (countPhoneRows(list) === 0) {
            setPhoneBulkToolbarVisible(card, false);
        }

        var selectAll = card.querySelector('[data-select-all-phones]');
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }

        updateBulkDeleteUI(card);
        closePhoneBulkModal(card);
        updateDestinationDistributionUi(document);
    }

    function initPhoneBulkDelete(card) {
        var list = card.querySelector('[data-phone-number-list]');
        if (!list) {
            return;
        }

        var selectAll = card.querySelector('[data-select-all-phones]');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                var checked = selectAll.checked;
                list.querySelectorAll('.ctcw-phone-select').forEach(function (checkbox) {
                    checkbox.checked = checked;
                });
                updateBulkDeleteUI(card);
            });
        }

        list.addEventListener('change', function (event) {
            if (event.target.classList.contains('ctcw-phone-select')) {
                updateBulkDeleteUI(card);
            }
        });

        var deleteButton = card.querySelector('[data-delete-selected-phones]');
        if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                openPhoneBulkModal(card);
            });
        }

        var modal = card.querySelector('[data-phone-bulk-modal]');
        if (modal) {
            modal.querySelectorAll('[data-phone-bulk-modal-close], [data-phone-bulk-modal-cancel]').forEach(function (button) {
                button.addEventListener('click', function () {
                    closePhoneBulkModal(card);
                });
            });

            var confirmButton = modal.querySelector('[data-phone-bulk-modal-confirm]');
            if (confirmButton) {
                confirmButton.addEventListener('click', function () {
                    removeSelectedPhoneRows(card);
                });
            }
        }

        setPhoneBulkToolbarVisible(card, countPhoneRows(list) > 0);
        updateBulkDeleteUI(card);
    }

    function renumberPhoneRows(list) {
        var fieldPrefix = list.getAttribute('data-field-prefix') || 'widget_numbers';
        list.querySelectorAll('[data-phone-number-row]').forEach(function (row, index) {
            var hiddenInput = row.querySelector('.ctcw-calling-code-value');
            var phoneInput = row.querySelector('[data-row-phone]');

            if (hiddenInput) {
                hiddenInput.name = fieldPrefix + '[' + index + '][country_code]';
            }
            if (phoneInput) {
                phoneInput.name = fieldPrefix + '[' + index + '][number]';
            }
        });
    }

    function hidePhoneEmptyState(list) {
        var emptyState = list.querySelector('[data-phone-empty-state]');
        if (emptyState) {
            emptyState.remove();
        }
    }

    function showPhoneEmptyState(list) {
        if (list.querySelector('[data-phone-number-row]') || list.querySelector('[data-phone-empty-state]')) {
            return;
        }

        var empty = document.createElement('div');
        empty.className = 'empty-state compact-empty';
        empty.setAttribute('data-phone-empty-state', '');
        empty.innerHTML = '<p>No numbers added yet. Click Add number to get started.</p>';
        list.appendChild(empty);
    }

    function preparePhoneRow(row, list) {
        hydrateCallingCodePicker(row);
        renumberPhoneRows(list);
    }

    function syncPhoneListCallingCodes(list) {
        list.querySelectorAll('[data-phone-number-row]').forEach(function (row) {
            hydrateCallingCodePicker(row);
        });
    }

    function addPhoneRow(list) {
        var existingRow = list.querySelector('[data-phone-number-row]');
        var row;

        if (existingRow) {
            row = existingRow.cloneNode(true);
            row.querySelectorAll('input[type="tel"]').forEach(function (input) {
                input.value = '';
            });
            row.querySelectorAll('.ctcw-phone-select').forEach(function (checkbox) {
                checkbox.checked = false;
            });
            row.classList.remove('is-selected');
            selectCallingCode(row, '+60');
        } else {
            var template = document.getElementById(list.id + '-template');
            if (!template) {
                return;
            }
            row = template.content.firstElementChild.cloneNode(true);
            selectCallingCode(row, '+60');
        }

        hidePhoneEmptyState(list);
        list.appendChild(row);
        preparePhoneRow(row, list);

        var card = getPhoneCard(list);
        setPhoneBulkToolbarVisible(card, true);
        updateBulkDeleteUI(card);
        updateDestinationDistributionUi(document);
    }

    function showSettingsPanel(sectionId, updateHash) {
        var workspace = document.querySelector('[data-settings-workspace]');
        if (!workspace) return;

        var panels = workspace.querySelectorAll('[data-settings-panel]');
        var buttons = workspace.querySelectorAll('[data-section-target]');
        var activePanel = workspace.querySelector('[data-settings-panel="' + sectionId + '"]') || panels[0];
        if (!activePanel) return;

        panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel === activePanel);
        });
        buttons.forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-section-target') === activePanel.getAttribute('data-settings-panel'));
        });

        if (updateHash) {
            history.replaceState(null, '', '#' + activePanel.getAttribute('data-settings-panel'));
        }
    }

    function refreshSelectedStyleCards() {
        document.querySelectorAll('[data-style-preview-card]').forEach(function (card) {
            var channel = card.getAttribute('data-style-channel') || 'whatsapp';
            var styleKey = card.getAttribute('data-style-preview-card');
            var selected = Array.from(document.querySelectorAll('[data-style-select][data-style-channel="' + channel + '"]')).map(function (select) {
                return select.value;
            });
            var isSelected = selected.indexOf(styleKey) !== -1;
            card.classList.toggle('is-selected', isSelected);
            if (card.hasAttribute('aria-pressed')) {
                card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            }
        });
    }
    window.refreshSelectedStyleCards = refreshSelectedStyleCards;

    document.addEventListener('click', function (event) {
        var styleCard = event.target.closest('[data-style-preview-card]');
        if (!styleCard) {
            return;
        }
        var channel = styleCard.getAttribute('data-style-channel') || 'whatsapp';
        var styleKey = styleCard.getAttribute('data-style-preview-card');
        if (!styleKey) {
            return;
        }
        document.querySelectorAll('[data-style-select][data-style-channel="' + channel + '"]').forEach(function (select) {
            if (select.value !== styleKey) {
                select.value = styleKey;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        refreshSelectedStyleCards();
    });

    document.addEventListener('click', function (event) {
        var copyButton = event.target.closest('[data-copy-target]');
        if (copyButton) {
            var target = document.querySelector(copyButton.getAttribute('data-copy-target'));
            if (target) {
                target.select();
                navigator.clipboard.writeText(target.value).then(function () {
                    copyButton.textContent = ctcwI18n('embed.copied');
                    setTimeout(function () { copyButton.textContent = ctcwI18n('embed.copy_code'); }, 1600);
                }).catch(function () {
                    document.execCommand('copy');
                });
            }
        }

        var addPhoneButton = event.target.closest('[data-add-phone-number]');
        if (addPhoneButton) {
            var phoneCard = addPhoneButton.closest('[data-phone-numbers-card]');
            var phoneList = phoneCard && phoneCard.querySelector('[data-phone-number-list]');
            if (phoneList) {
                addPhoneRow(phoneList);
            }
            return;
        }

        var removePhoneButton = event.target.closest('[data-remove-phone-number]');
        if (removePhoneButton) {
            var phoneRow = removePhoneButton.closest('[data-phone-number-row]');
            var phoneList = removePhoneButton.closest('[data-phone-number-list]');
            if (!phoneRow || !phoneList) {
                return;
            }
            if (!canRemovePhoneRows(phoneList, 1)) {
                window.alert(ctcwI18n('phone.min_one_required'));
                updateBulkDeleteUI(getPhoneCard(phoneList));
                return;
            }

            var phoneCard = getPhoneCard(phoneList);
            var confirmMessage = countPhoneRows(phoneList) === 1 && phoneAllowsEmpty(phoneCard)
                ? ctcwI18n('phone.delete_last_confirm')
                : ctcwI18n('phone.delete_confirm');
            if (!window.confirm(confirmMessage)) {
                return;
            }
            phoneRow.remove();
            renumberPhoneRows(phoneList);
            showPhoneEmptyState(phoneList);

            if (countPhoneRows(phoneList) === 0) {
                setPhoneBulkToolbarVisible(phoneCard, false);
            }
            updateBulkDeleteUI(phoneCard);
            updateDestinationDistributionUi(document);
            return;
        }

        var resetButton = event.target.closest('[data-reset-custom-code]');
        if (resetButton && !confirm(ctcwI18n('custom_code.reset_confirm'))) {
            event.preventDefault();
        }

        var sectionButton = event.target.closest('[data-section-target]');
        if (sectionButton) {
            showSettingsPanel(sectionButton.getAttribute('data-section-target'), true);
        }
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.code-textarea').forEach(function (textarea) {
        updateCodeLines(textarea);
        textarea.addEventListener('input', function () { updateCodeLines(textarea); });
        textarea.addEventListener('scroll', function () {
            var lines = textarea.closest('.code-editor').querySelector('.code-lines');
            lines.scrollTop = textarea.scrollTop;
        });
    });

    var mobileSame = document.querySelector('[data-same-mobile]');
    if (mobileSame) {
        mobileSame.addEventListener('change', refreshMobileFields);
        refreshMobileFields();
    }

    var businessMode = document.querySelector('[data-business-hours-mode]');
    if (businessMode) {
        businessMode.addEventListener('change', refreshBusinessHours);
        initBusinessHoursRows();
        refreshBusinessHours();
    }

    var greetingDialogToggle = document.querySelector('[data-role="greeting-dialog-toggle"]');
    var greetingCaptureToggle = document.querySelector('[data-role="phone-capture-toggle"]');
    var greetingForceToggle = document.querySelector('[data-role="force-phone-toggle"]');
    var consentNoticeToggle = document.querySelector('[data-role="consent-notice-toggle"]');
    if (greetingDialogToggle) {
        greetingDialogToggle.addEventListener('change', refreshGreetingDialogSettings);
    }
    if (greetingCaptureToggle) {
        greetingCaptureToggle.addEventListener('change', refreshGreetingDialogSettings);
    }
    if (greetingForceToggle) {
        greetingForceToggle.addEventListener('change', refreshGreetingDialogSettings);
    }
    if (consentNoticeToggle) {
        consentNoticeToggle.addEventListener('change', refreshGreetingDialogSettings);
    }
    var greetingOpenBehaviorSelect = document.querySelector('[data-role="greeting-open-behavior"]');
    if (greetingOpenBehaviorSelect) {
        greetingOpenBehaviorSelect.addEventListener('change', function () {
            refreshGreetingOpenBehaviorFields();
            renderAdminLivePreview();
        });
    }
    refreshGreetingDialogSettings();

    document.querySelectorAll('[data-style-select]').forEach(function (select) {
        select.addEventListener('change', refreshSelectedStyleCards);
    });
    refreshSelectedStyleCards();

    var initialSection = window.location.hash ? window.location.hash.slice(1) : '';
    showSettingsPanel(initialSection || 'whatsapp-number', false);

    document.querySelectorAll('[data-user-menu], [data-action-menu]').forEach(function (menu) {
        var toggle = menu.querySelector('.user-menu-toggle, .action-menu-toggle');
        if (!toggle) {
            return;
        }
        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            document.querySelectorAll('.user-menu.is-open, .action-menu.is-open').forEach(function (openMenu) {
                if (openMenu !== menu) {
                    openMenu.classList.remove('is-open');
                }
            });
            menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', menu.classList.contains('is-open') ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.user-menu.is-open, .action-menu.is-open').forEach(function (menu) {
            menu.classList.remove('is-open');
            var toggle = menu.querySelector('.user-menu-toggle, .action-menu-toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.querySelectorAll('.dropdown-menu, .action-menu-panel').forEach(function (panel) {
        panel.addEventListener('click', function (event) {
            if (event.target.closest('button, a, [role="menuitem"], input, select, textarea, label')) {
                return;
            }

            event.stopPropagation();
        });
    });

    var mobileToggle = document.querySelector('[data-mobile-nav-toggle]');
    var topnavMain = document.querySelector('.topnav-main');
    if (mobileToggle && topnavMain) {
        mobileToggle.addEventListener('click', function () {
            topnavMain.classList.toggle('is-open');
        });
    }

    var callingCodeDataNode = document.getElementById('country-code-data');
    var callingCodeOptions = [];
    if (callingCodeDataNode) {
        try {
            callingCodeOptions = JSON.parse(callingCodeDataNode.textContent || '[]');
        } catch (error) {
            callingCodeOptions = [];
        }
    }

    function normalizeDialCode(value) {
        var digits = String(value || '').replace(/\D/g, '');

        if (!digits) {
            return '';
        }

        return '+' + digits;
    }

    function getCallingCodeDisplay(value) {
        return normalizeDialCode(value) || '+60';
    }

    function getCallingCodePickerElements(row) {
        return {
            hiddenInput: row.querySelector('.ctcw-calling-code-value'),
            trigger: row.querySelector('.ctcw-calling-code-trigger'),
            label: row.querySelector('.ctcw-calling-code-label'),
            menu: row.querySelector('.ctcw-calling-code-menu'),
            search: row.querySelector('.ctcw-calling-code-search'),
            options: row.querySelector('.ctcw-calling-code-options')
        };
    }

    function getCallingCodeSearchText(option) {
        var parts = [String(option.label || '').toLowerCase()];

        if (callingCodeSearchAliases[option.dialCode]) {
            parts = parts.concat(callingCodeSearchAliases[option.dialCode]);
        }

        return parts.join(' ');
    }

    var callingCodeSearchAliases = {
        '+1': ['north america', 'usa', 'us', 'canada', 'america'],
        '+44': ['uk', 'united kingdom', 'britain', 'great britain', 'england'],
        '+60': ['malaysia'],
        '+65': ['singapore'],
        '+852': ['hong kong', 'hk']
    };

    function getCallingCodeLabel(dialCode) {
        var match = callingCodeOptions.find(function (option) {
            return option.dialCode === dialCode;
        });

        return match && match.label ? match.label : 'International calling code';
    }

    function updateCallingCodeTrigger(row, dialCode) {
        var picker = getCallingCodePickerElements(row);

        if (!picker.hiddenInput || !picker.label) {
            return;
        }

        var normalizedCode = normalizeDialCode(dialCode);

        picker.hiddenInput.value = normalizedCode;
        picker.label.textContent = normalizedCode;

        if (picker.trigger) {
            picker.trigger.title = 'Calling code: ' + normalizedCode + ' — ' + getCallingCodeLabel(normalizedCode);
        }
    }

    function filterCallingCodeOptions(options, query) {
        var rawQuery = String(query || '').trim().toLowerCase();
        var normalizedCode = normalizeDialCode(query);

        if (!rawQuery) {
            return options;
        }

        var exactCodeMatch = options.filter(function (option) {
            return option.dialCode === normalizedCode;
        });

        if (exactCodeMatch.length) {
            return exactCodeMatch;
        }

        return options.filter(function (option) {
            return (normalizedCode !== '' && option.dialCode.indexOf(normalizedCode) === 0)
                || getCallingCodeSearchText(option).indexOf(rawQuery) !== -1;
        });
    }

    function renderCallingCodeOptions(row, query) {
        var picker = getCallingCodePickerElements(row);

        if (!picker.options) {
            return;
        }

        var filtered = filterCallingCodeOptions(callingCodeOptions, query);
        picker.options.innerHTML = '';

        filtered.forEach(function (option) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'ctcw-calling-code-option';
            button.setAttribute('role', 'option');
            button.dataset.dialCode = option.dialCode;

            var codeEl = document.createElement('strong');
            codeEl.className = 'ctcw-calling-code-option-code';
            codeEl.textContent = option.dialCode;

            var labelEl = document.createElement('span');
            labelEl.className = 'ctcw-calling-code-option-label';
            labelEl.textContent = option.label || 'International calling code';

            button.appendChild(codeEl);
            button.appendChild(labelEl);
            picker.options.appendChild(button);
        });
    }

    function setCallingCodeMenuOpenState(row, isOpen) {
        if (!row) {
            return;
        }

        row.classList.toggle('is-calling-code-menu-open', isOpen);
    }

    function closeAllCallingCodeMenus(exceptRow) {
        document.querySelectorAll('.ctcw-phone-row').forEach(function (row) {
            if (row === exceptRow) {
                return;
            }

            var picker = getCallingCodePickerElements(row);

            if (!picker.menu || !picker.trigger) {
                return;
            }

            picker.menu.hidden = true;
            picker.trigger.setAttribute('aria-expanded', 'false');
            setCallingCodeMenuOpenState(row, false);
        });
    }

    function closeCallingCodeMenu(row) {
        var picker = getCallingCodePickerElements(row);

        if (!picker.menu || !picker.trigger) {
            return;
        }

        picker.menu.hidden = true;
        picker.trigger.setAttribute('aria-expanded', 'false');
        setCallingCodeMenuOpenState(row, false);
    }

    function toggleCallingCodeMenu(row) {
        var picker = getCallingCodePickerElements(row);

        if (!picker.menu || !picker.trigger) {
            return;
        }

        var shouldOpen = picker.menu.hidden;

        closeAllCallingCodeMenus(row);

        picker.menu.hidden = !shouldOpen;
        picker.trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        setCallingCodeMenuOpenState(row, shouldOpen);

        if (shouldOpen) {
            renderCallingCodeOptions(row, picker.search ? picker.search.value : '');

            window.setTimeout(function () {
                if (picker.search) {
                    picker.search.focus();
                }
            }, 0);
        }
    }

    function selectCallingCode(row, dialCode) {
        updateCallingCodeTrigger(row, dialCode);

        var picker = getCallingCodePickerElements(row);

        if (picker.hiddenInput) {
            picker.hiddenInput.dispatchEvent(
                new Event('change', { bubbles: true })
            );
        }

        closeCallingCodeMenu(row);
    }

    function hydrateCallingCodePicker(row) {
        var picker = getCallingCodePickerElements(row);

        if (!picker.hiddenInput) {
            return;
        }

        updateCallingCodeTrigger(row, getCallingCodeDisplay(picker.hiddenInput.value));
    }

    function initCallingCodePickers(list) {
        if (list.dataset.callingCodePickerInit === 'true') {
            return;
        }

        list.dataset.callingCodePickerInit = 'true';

        list.addEventListener('click', function (event) {
            var trigger = event.target.closest('.ctcw-calling-code-trigger');

            if (trigger) {
                event.preventDefault();

                var row = trigger.closest('.ctcw-phone-row');

                if (row) {
                    toggleCallingCodeMenu(row);
                }

                return;
            }

            var option = event.target.closest('.ctcw-calling-code-option');

            if (option) {
                event.preventDefault();

                var optionRow = option.closest('.ctcw-phone-row');

                if (optionRow) {
                    selectCallingCode(optionRow, option.dataset.dialCode);
                }
            }
        });

        list.addEventListener('input', function (event) {
            var searchInput = event.target.closest('.ctcw-calling-code-search');

            if (!searchInput) {
                return;
            }

            var row = searchInput.closest('.ctcw-phone-row');

            if (row) {
                renderCallingCodeOptions(row, searchInput.value);
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('.ctcw-calling-code-picker')) {
            return;
        }

        closeAllCallingCodeMenus(null);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        var openMenu = document.querySelector('.ctcw-calling-code-menu:not([hidden])');

        if (!openMenu) {
            return;
        }

        var row = openMenu.closest('.ctcw-phone-row');

        closeCallingCodeMenu(row);

        var picker = getCallingCodePickerElements(row);

        if (picker.trigger) {
            picker.trigger.focus();
        }
    });

    document.querySelectorAll('[data-strict-domain-check]').forEach(function (checkbox) {
        var warning = document.querySelector('[data-strict-domain-warning]');

        if (!warning) {
            return;
        }

        function syncStrictDomainWarning() {
            warning.hidden = !checkbox.checked;
        }

        checkbox.addEventListener('change', syncStrictDomainWarning);
        syncStrictDomainWarning();
    });

    function bootFeature(label, initFn) {
        try {
            initFn();
        } catch (error) {
            console.error('[CTC] ' + label + ' initialization failed:', error);
        }
    }

    function initLanguageSwitcher() {
        document.querySelectorAll('.language-switcher').forEach(function (languageForm) {
            var languageSelect = languageForm.querySelector('select[name="language"]');

            if (!languageSelect || languageSelect.dataset.ctcwLanguageInit === 'true') {
                return;
            }

            languageSelect.dataset.ctcwLanguageInit = 'true';
            languageSelect.addEventListener('change', function () {
                languageForm.submit();
            });
        });
    }

    function countValidPhoneRows(list) {
        if (!list) {
            return 0;
        }

        var count = 0;
        list.querySelectorAll('[data-phone-number-row]').forEach(function (row) {
            var phoneInput = row.querySelector('[data-row-phone]');
            var digits = String(phoneInput && phoneInput.value ? phoneInput.value : '').replace(/\D/g, '');
            if (digits.length >= 7) {
                count += 1;
            }
        });

        return count;
    }

    function updateDestinationDistributionUi(scope) {
        var root = scope && scope.querySelector ? scope : document;
        var panel = root.querySelector('[data-destination-distribution-panel]');
        var singleSummary = root.querySelector('[data-destination-single-summary]');
        var summaryText = root.querySelector('[data-destination-summary-text]');
        var methodSelect = root.querySelector('[data-destination-selection-method]');
        var card = root.querySelector('[data-phone-numbers-card]');
        var list = card ? card.querySelector('[data-phone-number-list]') : root.querySelector('[data-phone-number-list]');
        var count = countValidPhoneRows(list);

        if (panel) {
            panel.hidden = count < 2;
        }

        if (singleSummary) {
            singleSummary.hidden = count !== 1;
        }

        if (methodSelect) {
            methodSelect.disabled = count < 2;
        }

        root.querySelectorAll('[data-destination-method-help]').forEach(function (help) {
            var method = help.getAttribute('data-destination-method-help');
            help.hidden = !methodSelect || methodSelect.value !== method || count < 2;
        });

        if (summaryText && methodSelect && count >= 2) {
            if (methodSelect.value === 'random') {
                summaryText.textContent = ctcwI18n('distribution.js_summary_random', { count: String(count) });
            } else {
                summaryText.textContent = ctcwI18n('distribution.js_summary_round_robin', { count: String(count) });
            }
        }
    }

    function initDestinationDistributionUi() {
        var form = document.querySelector('[data-widget-form]');
        if (!form) {
            return;
        }

        updateDestinationDistributionUi(form);

        form.addEventListener('input', function (event) {
            if (event.target.closest('[data-phone-number-list]') || event.target.matches('[data-destination-selection-method]')) {
                updateDestinationDistributionUi(form);
            }
        });

        form.addEventListener('change', function (event) {
            if (event.target.matches('[data-destination-selection-method]')) {
                updateDestinationDistributionUi(form);
            }
        });

        document.querySelectorAll('[data-phone-numbers-card]').forEach(function (card) {
            var list = card.querySelector('[data-phone-number-list]');
            if (!list) {
                return;
            }

            var observer = new MutationObserver(function () {
                updateDestinationDistributionUi(form);
            });
            observer.observe(list, { childList: true, subtree: true });
        });
    }

    function runFeatureInits() {
        bootFeature('Language switcher', initLanguageSwitcher);
        bootFeature('Phone number manager', function () {
            document.querySelectorAll('[data-phone-number-list]').forEach(function (list) {
                initCallingCodePickers(list);
                list.querySelectorAll('[data-phone-number-row]').forEach(function (row) {
                    preparePhoneRow(row, list);
                });
            });

            document.querySelectorAll('[data-widget-form], [data-client-manual-form]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    var phoneList = form.querySelector('[data-phone-number-list]');
                    if (!phoneList) {
                        return;
                    }

                    syncPhoneListCallingCodes(phoneList);

                    if (form.hasAttribute('data-allow-empty-phones')) {
                        return;
                    }

                    if (!phoneList.querySelector('[data-phone-number-row]')) {
                        event.preventDefault();
                        window.alert(ctcwI18n('phone.min_one_form'));
                    }
                });
            });

            document.querySelectorAll('[data-phone-numbers-card]').forEach(function (card) {
                initPhoneBulkDelete(card);
            });
        });
        bootFeature('Destination distribution UI', initDestinationDistributionUi);
        bootFeature('Prefilled message variables', initPrefilledMessageVariables);
        bootFeature('Phone submit button ID validation', initPhoneSubmitButtonIdValidation);
        bootFeature('Admin live preview', initAdminLivePreview);
        bootFeature('Client create form', initClientCreateForm);
        bootFeature('Channel destinations UI', initChannelDestinationsUi);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runFeatureInits);
    } else {
        runFeatureInits();
    }
})();

function initClientCreateForm() {
    var form = document.querySelector('[data-client-create-form]');
    if (!form) {
        return;
    }

    var passwordInput = form.querySelector('[data-client-password]');
    var confirmInput = form.querySelector('[data-client-confirm-password]');
    var generateButton = form.querySelector('[data-generate-client-password]');
    var toggleButton = form.querySelector('[data-toggle-client-password]');
    var strengthRoot = form.querySelector('[data-password-strength]');
    var strengthText = form.querySelector('[data-strength-text]');
    var matchError = form.querySelector('[data-password-match-error]');

    if (!passwordInput || !confirmInput) {
        return;
    }

    var upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    var lower = 'abcdefghijklmnopqrstuvwxyz';
    var numbers = '0123456789';
    var symbols = '!@#$%^&*()_+-=[]{};:,.?/~';
    var allChars = upper + lower + numbers + symbols;

    function randomChar(set) {
        var array = new Uint32Array(1);
        window.crypto.getRandomValues(array);
        return set.charAt(array[0] % set.length);
    }

    function shuffleChars(chars) {
        var list = chars.slice();
        var index = list.length - 1;

        while (index > 0) {
            var array = new Uint32Array(1);
            window.crypto.getRandomValues(array);
            var swapIndex = array[0] % (index + 1);
            var temp = list[index];
            list[index] = list[swapIndex];
            list[swapIndex] = temp;
            index -= 1;
        }

        return list;
    }

    function generateSecurePassword(length) {
        var size = length || 16;
        var required = [
            randomChar(upper),
            randomChar(lower),
            randomChar(numbers),
            randomChar(symbols)
        ];
        var passwordChars = required.slice();

        while (passwordChars.length < size) {
            passwordChars.push(randomChar(allChars));
        }

        return shuffleChars(passwordChars).join('');
    }

    function getPasswordStrength(password) {
        if (!password) {
            return { level: 'weak', label: window.ctcwI18n('password.weak') };
        }

        var hasUpper = /[A-Z]/.test(password);
        var hasLower = /[a-z]/.test(password);
        var hasNumber = /[0-9]/.test(password);
        var hasSymbol = /[!@#$%^&*()_+\-=[\]{};:,.?/~]/.test(password);
        var types = [hasUpper, hasLower, hasNumber, hasSymbol].filter(Boolean).length;

        if (password.length >= 16 && hasUpper && hasLower && hasNumber && hasSymbol) {
            return { level: 'strong', label: window.ctcwI18n('password.strong') };
        }
        if (password.length >= 8 && types >= 2) {
            return { level: 'normal', label: window.ctcwI18n('password.normal') };
        }
        return { level: 'weak', label: window.ctcwI18n('password.weak') };
    }

    function updateStrengthIndicator() {
        if (!strengthRoot || !strengthText) {
            return;
        }

        var strength = getPasswordStrength(passwordInput.value);
        strengthRoot.classList.remove('is-weak', 'is-normal', 'is-strong');
        strengthRoot.classList.add('is-' + strength.level);
        strengthText.textContent = strength.label;
    }

    function updateMatchError() {
        if (!matchError) {
            return;
        }

        var mismatch = confirmInput.value !== '' && passwordInput.value !== confirmInput.value;
        matchError.hidden = !mismatch;
        confirmInput.setCustomValidity(mismatch ? window.ctcwI18n('password.match_error') : '');
    }

    function fillGeneratedPassword(value) {
        passwordInput.value = value;
        confirmInput.value = value;
        passwordInput.type = 'text';
        confirmInput.type = 'text';
        if (toggleButton) {
            toggleButton.textContent = window.ctcwI18n('password.hide');
        }
        updateStrengthIndicator();
        updateMatchError();
        passwordInput.focus();
        passwordInput.select();
    }

    if (generateButton) {
        generateButton.addEventListener('click', function () {
            fillGeneratedPassword(generateSecurePassword(16));
        });
    }

    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            var showing = passwordInput.type === 'text';
            passwordInput.type = showing ? 'password' : 'text';
            confirmInput.type = showing ? 'password' : 'text';
            toggleButton.textContent = showing ? window.ctcwI18n('password.show') : window.ctcwI18n('password.hide');
        });
    }

    passwordInput.addEventListener('input', function () {
        updateStrengthIndicator();
        updateMatchError();
    });
    confirmInput.addEventListener('input', updateMatchError);

    form.addEventListener('submit', function (event) {
        updateMatchError();

        if (passwordInput.value === '') {
            event.preventDefault();
            passwordInput.reportValidity();
            return;
        }

        if (confirmInput.value === '') {
            event.preventDefault();
            confirmInput.reportValidity();
            return;
        }

        if (passwordInput.value.length < 8) {
            event.preventDefault();
            passwordInput.setCustomValidity(window.ctcwI18n('password.min_length'));
            passwordInput.reportValidity();
            passwordInput.setCustomValidity('');
            return;
        }

        if (passwordInput.value !== confirmInput.value) {
            event.preventDefault();
            updateMatchError();
            confirmInput.focus();
        }
    });

    updateStrengthIndicator();
    updateMatchError();
}

function initAdminLivePreview() {
    var form = document.querySelector('[data-widget-form]');
    var previewToggle = document.querySelector('[data-role="admin-live-preview-toggle"], [data-live-preview-toggle]');
    var previewDebugEnabled = new URLSearchParams(window.location.search).get('preview_debug') === '1';

    function normalizeWidgetStyle(style) {
        return style === 'style-5' ? 'style-8' : style;
    }

    function normalizeTelegramWidgetStyle(style) {
        var telegramStyles = ['tg-compact', 'tg-icon', 'tg-pill'];
        var normalized = normalizeWidgetStyle(String(style || '').trim());
        if (telegramStyles.indexOf(normalized) !== -1) {
            return normalized;
        }
        if (['style-2', 'style-3', 'style-3-large', 'style-7'].indexOf(normalized) !== -1) {
            return 'tg-icon';
        }
        if (['style-1', 'style-6', 'style-8'].indexOf(normalized) !== -1) {
            return 'tg-pill';
        }
        return 'tg-compact';
    }

    function previewDebugLog() {
        if (!previewDebugEnabled) {
            return;
        }

        console.log.apply(console, arguments);
    }

    if (!previewToggle) {
        console.warn('[CTC] Admin live-preview toggle was not found.');
        return;
    }

    if (!form) {
        console.warn('[CTC] Widget edit form was not found for admin live preview.');
        return;
    }

    var previewEnabledStorageKey = 'ctcw_admin_live_preview_enabled';
    var previewGreetingTimer = null;
    var previewPlaceholderDestination = '+60123456789';
    var previewPositionClasses = [
        'ctcw-preview-bottom-right',
        'ctcw-preview-bottom-left',
        'ctcw-preview-top-right',
        'ctcw-preview-top-left'
    ];
    var debugFrameEnabled = new URLSearchParams(window.location.search).get('debug_preview_frame') === '1';
    var iconNode = document.getElementById('ctcw-preview-icon');
    var telegramIconNode = document.getElementById('ctcw-preview-icon-telegram');
    var whatsappIcon = '';
    var telegramIcon = '';

    if (iconNode) {
        try {
            whatsappIcon = JSON.parse(iconNode.textContent || '""');
        } catch (error) {
            whatsappIcon = '';
        }
    }
    if (telegramIconNode) {
        try {
            telegramIcon = JSON.parse(telegramIconNode.textContent || '""');
        } catch (error) {
            telegramIcon = '';
        }
    }

    function getSelectedChannelMode() {
        var selected = form.querySelector('[name="channel_mode"]:checked');
        if (selected && selected.value) {
            return selected.value;
        }
        return 'whatsapp_only';
    }

    function previewChannelsForMode(mode) {
        if (mode === 'telegram_only') {
            return ['telegram'];
        }
        if (mode === 'both') {
            return ['whatsapp', 'telegram'];
        }
        return ['whatsapp'];
    }

    function previewChannelLabel(channel, formState) {
        if (channel === 'telegram') {
            return window.ctcwI18n('channel.launcher.telegram') || 'Telegram us';
        }
        return formState.callToAction || window.ctcwI18n('channel.launcher.whatsapp') || window.ctcwI18n('preview.default_cta');
    }

    function previewForcePhoneNote(channel) {
        if (channel === 'telegram') {
            return window.ctcwI18n('preview.phone_required_telegram') || 'Phone required before Telegram';
        }
        return window.ctcwI18n('preview.phone_required') || 'Phone required before WhatsApp';
    }

    function previewContinueLabel(channel, formState) {
        if (channel === 'telegram') {
            return window.ctcwI18n('widget.continue_telegram') || 'Continue on Telegram';
        }
        return formState.greetingSubmitText || window.ctcwI18n('widget.continue_whatsapp') || 'Continue on WhatsApp';
    }

    function getOrCreateAdminPreviewRoot() {
        var root = document.getElementById('ctcw-admin-live-preview')
            || document.getElementById('ctcwAdminLivePreview');

        if (!root) {
            root = document.createElement('div');
            root.id = 'ctcw-admin-live-preview';
            root.className = 'ctcw-admin-live-preview';
            root.setAttribute('aria-hidden', 'true');
        } else if (root.id !== 'ctcw-admin-live-preview') {
            root.id = 'ctcw-admin-live-preview';
        }

        if (root.parentNode !== document.body) {
            document.body.appendChild(root);
        }

        return root;
    }

    function getOrCreateCustomCssStyle() {
        var style = document.getElementById('ctcwAdminLivePreviewCustomCss');

        if (!style) {
            style = document.createElement('style');
            style.id = 'ctcwAdminLivePreviewCustomCss';
            document.head.appendChild(style);
        } else if (style.parentNode !== document.head) {
            document.head.appendChild(style);
        }

        return style;
    }

    function getPreviewEnabledState() {
        return previewToggle.checked;
    }

    function getFieldValue(name) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) {
            return '';
        }
        if (field.type === 'checkbox') {
            return field.checked;
        }
        return field.value;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function scopePreviewCss(css) {
        var trimmed = String(css || '').trim();
        if (trimmed === '') {
            return '';
        }

        return trimmed.replace(/(^|})\s*([^@{}][^{]*)\{/g, function (match, before, selector) {
            var scoped = selector.split(',').map(function (part) {
                var token = part.trim();
                if (token === '') {
                    return token;
                }
                return '#ctcw-admin-live-preview ' + token;
            }).join(', ');

            return before + scoped + '{';
        });
    }

    function collectCurrentWidgetFormState() {
        var vertical = getFieldValue('desktop_vertical_position_type') || 'bottom';
        var horizontal = getFieldValue('desktop_horizontal_position_type') || 'right';
        var channelMode = getSelectedChannelMode();
        var channels = previewChannelsForMode(channelMode);
        var activeChannel = window.ctcwPreviewActiveChannel && channels.indexOf(window.ctcwPreviewActiveChannel) !== -1
            ? window.ctcwPreviewActiveChannel
            : channels[0];

        return {
            desktopStyle: normalizeWidgetStyle(getFieldValue('desktop_style') || 'style-1'),
            telegramDesktopStyle: normalizeTelegramWidgetStyle(getFieldValue('telegram_desktop_style') || 'tg-compact'),
            desktopVerticalPosition: vertical === 'top' ? 'top' : 'bottom',
            desktopHorizontalPosition: horizontal === 'left' ? 'left' : 'right',
            callToAction: getFieldValue('call_to_action') || window.ctcwI18n('preview.default_cta'),
            greetingEnabled: !!getFieldValue('greeting_enabled'),
            greetingOpenBehavior: getFieldValue('greeting_open_behavior') || 'auto_delay',
            greetingDelaySeconds: parseInt(getFieldValue('greeting_delay_seconds') || '2', 10) || 0,
            greetingTitle: getFieldValue('greeting_title') || 'Hi 👋',
            greetingMessage: getFieldValue('greeting_message') || 'Need Help? Contact Us !',
            greetingCapturePhone: !!getFieldValue('greeting_capture_phone'),
            greetingForcePhoneCapture: !!getFieldValue('greeting_force_phone_capture'),
            greetingPhonePlaceholder: getFieldValue('greeting_phone_placeholder') || window.ctcwI18n('widget.enter_phone_neutral') || 'Enter your phone number to continue',
            greetingSubmitText: getFieldValue('greeting_submit_text') || window.ctcwI18n('widget.continue_whatsapp') || 'Continue on WhatsApp',
            consentNoticeEnabled: !!getFieldValue('consent_notice_enabled'),
            consentNoticeText: getFieldValue('consent_notice_text') || window.ctcwI18n('widget.consent.channel_neutral') || 'I agree that my information may be used to contact me through my selected communication channel.',
            customCss: getFieldValue('custom_css') || '',
            previewDestination: previewPlaceholderDestination,
            channelMode: channelMode,
            channels: channels,
            activeChannel: activeChannel
        };
    }

    function getPreviewPositionClass(formState) {
        return 'ctcw-preview-' + formState.desktopVerticalPosition + '-' + formState.desktopHorizontalPosition;
    }

    function buildGreetingHtml(formState) {
        if (!formState.greetingEnabled) {
            return '';
        }

        var title = escapeHtml(formState.greetingTitle);
        var message = escapeHtml(formState.greetingMessage);
        var placeholder = escapeHtml(formState.greetingPhonePlaceholder);
        var submitText = escapeHtml(previewContinueLabel(formState.activeChannel, formState));
        var forceNote = formState.greetingForcePhoneCapture
            ? '<small class="ctcw-preview-force-note">' + escapeHtml(previewForcePhoneNote(formState.activeChannel)) + '</small>'
            : '';
        var consentHtml = '';
        if (formState.greetingCapturePhone && formState.consentNoticeEnabled) {
            consentHtml = '<p class="ctcw-consent-text">' + escapeHtml(formState.consentNoticeText) + '</p>';
        }

        var activeChannel = formState.activeChannel === 'telegram' ? 'telegram' : 'whatsapp';

        if (!formState.greetingCapturePhone) {
            return '<div class="ctcw-greeting" data-preview-greeting data-active-channel="' + escapeHtml(activeChannel) + '" data-preview-active-channel="' + escapeHtml(activeChannel) + '">'
                + '<strong>' + title + '</strong>'
                + '<p>' + message + '</p>'
                + forceNote
                + '</div>';
        }

        return '<div class="ctcw-greeting has-capture' + (consentHtml ? ' has-consent' : '') + '" data-preview-greeting data-active-channel="' + escapeHtml(activeChannel) + '" data-preview-active-channel="' + escapeHtml(activeChannel) + '">'
            + '<div class="ctcw-greeting-form">'
            + '<strong>' + title + '</strong>'
            + '<p>' + message + '</p>'
            + forceNote
            + '<div class="ctcw-phone-field">'
            + '<div class="ctcw-phone-row">'
            + '<input class="ctcw-phone-input" type="tel" placeholder="' + placeholder + '" readonly tabindex="-1">'
            + '<button type="button" class="ctcw-greeting-submit" aria-label="' + submitText + '" tabindex="-1">'
            + '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>'
            + '</button>'
            + '</div>'
            + '</div>'
            + consentHtml
            + '</div>'
            + '</div>';
    }

    function buildPreviewLauncherHtml(channel, formState) {
        var label = escapeHtml(previewChannelLabel(channel, formState));
        var icon = channel === 'telegram' ? telegramIcon : whatsappIcon;
        var channelClass = channel === 'telegram' ? 'channel-telegram' : 'channel-whatsapp';
        var style = channel === 'telegram'
            ? escapeHtml(formState.telegramDesktopStyle || 'tg-compact')
            : escapeHtml(formState.desktopStyle || 'style-1');
        var shellMod = channel === 'telegram'
            ? 'floating-channel-launcher--telegram'
            : 'floating-channel-launcher--whatsapp';

        return '<div class="ctcw-channel-shell floating-channel-launcher ' + shellMod + ' ' + style + '" data-channel-shell="' + escapeHtml(channel) + '">'
            + '<button type="button" class="ctcw-widget ctcw-launcher ' + channelClass + '" tabindex="0" aria-label="' + label + '" data-preview-destination="' + escapeHtml(formState.previewDestination) + '" data-preview-channel="' + escapeHtml(channel) + '">'
            + '<span class="ctcw-icon">' + icon + '</span>'
            + '<span class="ctcw-text">' + label + '</span>'
            + '</button>'
            + '</div>';
    }

    function buildAdminPreviewMarkup(formState) {
        var channels = Array.isArray(formState.channels) ? formState.channels : ['whatsapp'];
        var modeClass = channels.length > 1 ? 'widget-mode-multiple' : 'widget-mode-single';
        var activeChannel = formState.activeChannel === 'telegram' ? 'telegram' : 'whatsapp';
        var launchers = channels.map(function (channel) {
            return buildPreviewLauncherHtml(channel, formState);
        }).join('');

        return '<div class="ctcw-admin-live-preview-inner">'
            + '<span class="ctcw-preview-badge">' + escapeHtml(window.ctcwI18n('preview.label')) + '</span>'
            + '<div class="ctcw-container is-online ' + modeClass + '" data-preview-channel-mode="' + escapeHtml(formState.channelMode) + '" data-active-channel="' + escapeHtml(activeChannel) + '">'
            + buildGreetingHtml(formState)
            + '<div class="ctcw-launcher-stack ' + modeClass + '" data-preview-launcher-stack>'
            + launchers
            + '</div>'
            + '</div>'
            + '</div>';
    }

    function updateDebugFrameState(root) {
        if (!debugFrameEnabled) {
            root.classList.remove('is-frame-debug');
            return;
        }

        root.classList.add('is-frame-debug');
        if (!document.querySelector('style[data-preview-frame-debug]')) {
            var debugStyle = document.createElement('style');
            debugStyle.setAttribute('data-preview-frame-debug', 'true');
            debugStyle.textContent = '#ctcw-admin-live-preview.is-frame-debug .ctcw-admin-live-preview-inner{outline:2px dashed rgba(37,99,235,.65);background:rgba(37,99,235,.08);}';
            document.head.appendChild(debugStyle);
        }
    }

    function applyPreviewPosition(root, formState) {
        previewPositionClasses.forEach(function (className) {
            root.classList.remove(className);
        });

        root.classList.add(getPreviewPositionClass(formState));
        root.classList.toggle('is-anchor-top', formState.desktopVerticalPosition === 'top');
        root.classList.toggle('is-anchor-left', formState.desktopHorizontalPosition === 'left');
        root.style.removeProperty('top');
        root.style.removeProperty('right');
        root.style.removeProperty('bottom');
        root.style.removeProperty('left');
        root.style.removeProperty('display');
    }

    function renderAdminLivePreview() {
        var previewEnabled = getPreviewEnabledState();
        var root = getOrCreateAdminPreviewRoot();
        var customCssStyle = getOrCreateCustomCssStyle();

        if (!previewEnabled) {
            window.clearTimeout(previewGreetingTimer);
            previewGreetingTimer = null;
            root.classList.remove('is-enabled');
            root.innerHTML = '';
            root.setAttribute('aria-hidden', 'true');
            customCssStyle.textContent = '';
            root.classList.remove('is-frame-debug');
            return;
        }

        var formState = collectCurrentWidgetFormState();

        root.className = [
            'ctcw-admin-live-preview',
            'is-enabled',
            getPreviewPositionClass(formState)
        ].join(' ');
        root.classList.toggle('is-anchor-top', formState.desktopVerticalPosition === 'top');
        root.classList.toggle('is-anchor-left', formState.desktopHorizontalPosition === 'left');
        root.setAttribute('aria-hidden', 'false');
        root.innerHTML = buildAdminPreviewMarkup(formState);
        applyPreviewPosition(root, formState);
        customCssStyle.textContent = scopePreviewCss(formState.customCss);
        updateDebugFrameState(root);

        window.clearTimeout(previewGreetingTimer);
        previewGreetingTimer = null;
        var previewGreeting = root.querySelector('[data-preview-greeting]');
        if (previewGreeting && formState.greetingEnabled && formState.greetingOpenBehavior === 'auto_delay') {
            previewGreetingTimer = window.setTimeout(function () {
                previewGreeting.classList.add('is-visible');
            }, Math.max(0, Number(formState.greetingDelaySeconds || 0)) * 1000);
        }

        previewDebugLog('[CTC] Preview enabled:', previewEnabled);
        previewDebugLog('[CTC] Preview root:', root);
        previewDebugLog('[CTC] Preview state:', formState);
    }

    function setLivePreviewEnabled(enabled) {
        try {
            window.localStorage.setItem(previewEnabledStorageKey, enabled ? '1' : '0');
        } catch (error) {
            // Ignore storage failures in restricted browsers.
        }

        previewToggle.checked = enabled;
        renderAdminLivePreview();
    }

    function initLivePreviewToggle() {
        var saved = null;

        try {
            saved = window.localStorage.getItem(previewEnabledStorageKey);
        } catch (error) {
            saved = null;
        }

        var enabled = saved === null ? true : saved === '1';
        previewToggle.checked = enabled;
        previewToggle.addEventListener('change', function () {
            setLivePreviewEnabled(this.checked);
        });
        renderAdminLivePreview();
    }

    if (!window.ctcwPreviewGreetingClickBound) {
        window.ctcwPreviewGreetingClickBound = true;
        document.addEventListener('click', function (event) {
            var previewButton = event.target.closest('#ctcw-admin-live-preview .ctcw-widget[data-preview-channel]');
            if (!previewButton) {
                return;
            }

            var previewRoot = document.getElementById('ctcw-admin-live-preview');
            if (!previewRoot || !previewRoot.classList.contains('is-enabled')) {
                return;
            }

            var channel = previewButton.getAttribute('data-preview-channel') || 'whatsapp';
            window.ctcwPreviewActiveChannel = channel === 'telegram' ? 'telegram' : 'whatsapp';
            renderAdminLivePreview();

            previewRoot = document.getElementById('ctcw-admin-live-preview');
            var greetingToggle = document.querySelector('[data-role="greeting-dialog-toggle"]');
            var behaviorSelect = document.querySelector('[data-role="greeting-open-behavior"]');
            var previewGreeting = previewRoot ? previewRoot.querySelector('[data-preview-greeting]') : null;
            if (!previewGreeting) {
                return;
            }

            if (greetingToggle && greetingToggle.checked) {
                if (!behaviorSelect || behaviorSelect.value === 'click_only' || behaviorSelect.value === 'auto_delay') {
                    previewGreeting.classList.add('is-visible');
                }
            }
        });
    }

    form.addEventListener('input', renderAdminLivePreview);
    form.addEventListener('change', renderAdminLivePreview);

    document.querySelectorAll('[data-section-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.requestAnimationFrame(renderAdminLivePreview);
        });
    });

    initLivePreviewToggle();
    previewDebugLog('[CTC] Admin preview initialized');
}

function initChannelDestinationsUi() {
    function syncDestinationPanelsForMode(mode) {
        var whatsappEnabled = mode === 'whatsapp_only' || mode === 'both';
        var telegramEnabled = mode === 'telegram_only' || mode === 'both';

        document.querySelectorAll('[data-destinations-panel]').forEach(function (panel) {
            var tabs = panel.querySelector('[data-channel-dest-tabs]');
            var whatsappTab = panel.querySelector('[data-dest-tab-target="whatsapp"]');
            var telegramTab = panel.querySelector('[data-dest-tab-target="telegram"]');
            var whatsappPanel = panel.querySelector('[data-dest-tab-panel="whatsapp"]');
            var telegramPanel = panel.querySelector('[data-dest-tab-panel="telegram"]');

            if (whatsappTab) {
                whatsappTab.classList.toggle('is-channel-disabled', !whatsappEnabled);
                whatsappTab.hidden = !whatsappEnabled;
            }
            if (telegramTab) {
                telegramTab.classList.toggle('is-channel-disabled', !telegramEnabled);
                telegramTab.hidden = !telegramEnabled;
            }
            if (whatsappPanel) {
                whatsappPanel.classList.toggle('is-channel-disabled', !whatsappEnabled);
            }
            if (telegramPanel) {
                telegramPanel.classList.toggle('is-channel-disabled', !telegramEnabled);
            }
            if (tabs) {
                tabs.setAttribute('data-channel-mode', mode);
                tabs.setAttribute('data-visible-count', String((whatsappEnabled ? 1 : 0) + (telegramEnabled ? 1 : 0)));
            }

            var preferred = telegramEnabled && !whatsappEnabled
                ? 'telegram'
                : (whatsappEnabled ? 'whatsapp' : 'telegram');
            var activeTab = panel.querySelector('[data-dest-tab-target].is-active:not(.is-channel-disabled)');
            if (!activeTab) {
                panel.querySelectorAll('[data-dest-tab-target]').forEach(function (tab) {
                    var match = tab.getAttribute('data-dest-tab-target') === preferred;
                    tab.classList.toggle('is-active', match && !tab.classList.contains('is-channel-disabled'));
                });
                panel.querySelectorAll('[data-dest-tab-panel]').forEach(function (tabPanel) {
                    var match = tabPanel.getAttribute('data-dest-tab-panel') === preferred;
                    tabPanel.classList.toggle('is-active', match && !tabPanel.classList.contains('is-channel-disabled'));
                });
            } else if (whatsappPanel && telegramPanel) {
                var activeName = activeTab.getAttribute('data-dest-tab-target');
                panel.querySelectorAll('[data-dest-tab-panel]').forEach(function (tabPanel) {
                    tabPanel.classList.toggle('is-active', tabPanel.getAttribute('data-dest-tab-panel') === activeName);
                });
            }
        });
    }

    function syncStylePanelsForMode(mode) {
        var whatsappEnabled = mode === 'whatsapp_only' || mode === 'both';
        var telegramEnabled = mode === 'telegram_only' || mode === 'both';

        document.querySelectorAll('[data-channel-style-panels]').forEach(function (panel) {
            var tabs = panel.querySelector('[data-channel-style-tabs]');
            var whatsappTab = panel.querySelector('[data-style-tab-target="whatsapp"]');
            var telegramTab = panel.querySelector('[data-style-tab-target="telegram"]');
            var whatsappPanel = panel.querySelector('[data-style-tab-panel="whatsapp"]');
            var telegramPanel = panel.querySelector('[data-style-tab-panel="telegram"]');
            var visibleCount = (whatsappEnabled ? 1 : 0) + (telegramEnabled ? 1 : 0);
            var preferred = telegramEnabled && !whatsappEnabled
                ? 'telegram'
                : (whatsappEnabled ? 'whatsapp' : 'telegram');

            if (whatsappTab) {
                whatsappTab.classList.toggle('is-channel-disabled', !whatsappEnabled);
                whatsappTab.hidden = !whatsappEnabled;
            }
            if (telegramTab) {
                telegramTab.classList.toggle('is-channel-disabled', !telegramEnabled);
                telegramTab.hidden = !telegramEnabled;
            }
            if (tabs) {
                tabs.setAttribute('data-channel-mode', mode);
                tabs.setAttribute('data-visible-count', String(visibleCount));
                tabs.classList.toggle('is-single-channel', visibleCount < 2);
                tabs.hidden = visibleCount < 2;
            }

            panel.setAttribute('data-channel-mode', mode);

            if (whatsappPanel) {
                whatsappPanel.classList.toggle('is-channel-disabled', !whatsappEnabled);
            }
            if (telegramPanel) {
                telegramPanel.classList.toggle('is-channel-disabled', !telegramEnabled);
            }

            if (visibleCount < 2) {
                if (whatsappPanel) {
                    whatsappPanel.hidden = !whatsappEnabled;
                    whatsappPanel.classList.toggle('is-active', whatsappEnabled);
                }
                if (telegramPanel) {
                    telegramPanel.hidden = !telegramEnabled;
                    telegramPanel.classList.toggle('is-active', telegramEnabled);
                }
            } else {
                var activeTab = panel.querySelector('[data-style-tab-target].is-active:not(.is-channel-disabled)');
                var activeName = activeTab && !activeTab.hidden
                    ? activeTab.getAttribute('data-style-tab-target')
                    : preferred;

                panel.querySelectorAll('[data-style-tab-target]').forEach(function (tab) {
                    tab.classList.toggle('is-active', tab.getAttribute('data-style-tab-target') === activeName);
                });
                panel.querySelectorAll('[data-style-tab-panel]').forEach(function (tabPanel) {
                    var match = tabPanel.getAttribute('data-style-tab-panel') === activeName;
                    tabPanel.classList.toggle('is-active', match);
                    tabPanel.hidden = !match;
                });
            }
        });

        if (typeof window.refreshSelectedStyleCards === 'function') {
            window.refreshSelectedStyleCards();
        }
    }

    function telegramSaveEndpoint() {
        var form = document.querySelector('[data-telegram-destination-form]');
        if (form && form.action) {
            return form.action;
        }
        return 'telegram-destination-save.php';
    }

    function telegramCsrfToken() {
        var field = document.querySelector('[data-telegram-destination-form] input[name="csrf_token"]')
            || document.querySelector('input[name="csrf_token"]');
        return field ? String(field.value || '') : '';
    }

    function postTelegramDestinationAction(payload) {
        var body = new URLSearchParams();
        Object.keys(payload).forEach(function (key) {
            if (payload[key] !== undefined && payload[key] !== null) {
                body.append(key, String(payload[key]));
            }
        });
        body.append('csrf_token', telegramCsrfToken());
        body.append('response', 'json');

        return fetch(telegramSaveEndpoint(), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            credentials: 'same-origin',
            body: body.toString()
        }).then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, status: response.status, data: data || {} };
            });
        });
    }

    function reloadDestinationsView() {
        var url = new URL(window.location.href);
        url.hash = 'destinations';
        if (!url.searchParams.get('dest_tab') && !url.searchParams.get('tab')) {
            url.searchParams.set('dest_tab', 'telegram');
        }
        window.location.href = url.toString();
    }

    document.querySelectorAll('[data-channel-mode-grid]').forEach(function (grid) {
        grid.querySelectorAll('[data-channel-mode-input]').forEach(function (input) {
            input.addEventListener('change', function () {
                grid.querySelectorAll('.channel-mode-card').forEach(function (card) {
                    var radio = card.querySelector('[data-channel-mode-input]');
                    card.classList.toggle('is-selected', !!(radio && radio.checked));
                });
                syncDestinationPanelsForMode(input.value);
                syncStylePanelsForMode(input.value);
            });
            if (input.checked) {
                syncDestinationPanelsForMode(input.value);
                syncStylePanelsForMode(input.value);
            }
        });
    });

    document.querySelectorAll('[data-destinations-panel]').forEach(function (panel) {
        panel.querySelectorAll('[data-dest-tab-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.classList.contains('is-channel-disabled') || button.hidden) {
                    return;
                }
                var target = button.getAttribute('data-dest-tab-target');
                panel.querySelectorAll('[data-dest-tab-target]').forEach(function (tab) {
                    tab.classList.toggle('is-active', tab === button);
                });
                panel.querySelectorAll('[data-dest-tab-panel]').forEach(function (tabPanel) {
                    tabPanel.classList.toggle('is-active', tabPanel.getAttribute('data-dest-tab-panel') === target);
                });
            });
        });
    });

    document.querySelectorAll('[data-channel-style-panels]').forEach(function (panel) {
        panel.querySelectorAll('[data-style-tab-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.classList.contains('is-channel-disabled') || button.hidden) {
                    return;
                }
                var target = button.getAttribute('data-style-tab-target');
                panel.querySelectorAll('[data-style-tab-target]').forEach(function (tab) {
                    tab.classList.toggle('is-active', tab === button);
                });
                panel.querySelectorAll('[data-style-tab-panel]').forEach(function (tabPanel) {
                    var match = tabPanel.getAttribute('data-style-tab-panel') === target;
                    tabPanel.classList.toggle('is-active', match);
                    tabPanel.hidden = !match;
                });
            });
        });
    });

    document.querySelectorAll('[data-telegram-dest-action]').forEach(function (button) {
        button.addEventListener('click', function () {
            var action = button.getAttribute('data-telegram-dest-action');
            var destinationId = button.getAttribute('data-destination-id');
            var widgetId = button.getAttribute('data-widget-id');
            if (!action || !destinationId || !widgetId) {
                return;
            }

            if (action === 'delete' && !window.confirm(ctcwI18n('telegram.confirm_delete'))) {
                return;
            }

            var payload = {
                destination_action: action,
                destination_id: destinationId,
                widget_id: widgetId
            };
            if (action === 'toggle') {
                payload.is_active = button.getAttribute('data-is-active') === '1' ? '1' : '';
            }

            button.disabled = true;
            postTelegramDestinationAction(payload)
                .then(function (result) {
                    if (!result.data.success) {
                        window.alert(result.data.message || ctcwI18n('telegram.error.save_failed'));
                        button.disabled = false;
                        return;
                    }
                    reloadDestinationsView();
                })
                .catch(function () {
                    window.alert(ctcwI18n('telegram.error.save_failed'));
                    button.disabled = false;
                });
        });
    });

    var telegramModal = document.querySelector('dialog[data-telegram-modal]');
    if (!telegramModal) {
        return;
    }

    if (telegramModal.parentElement !== document.body) {
        document.body.appendChild(telegramModal);
    }

    var modal = telegramModal;
    var form = modal.querySelector('[data-telegram-destination-form]');
    var idInput = modal.querySelector('[data-telegram-destination-id]');
    var title = modal.querySelector('[data-telegram-modal-title]');
    var typeSelect = modal.querySelector('[data-telegram-type-select]');
    var startWrap = modal.querySelector('[data-telegram-bot-start-wrap]');
    var valueHelp = modal.querySelector('[data-telegram-value-help]');
    var valueLabel = modal.querySelector('[data-telegram-value-label]');
    var valueInput = modal.querySelector('[data-telegram-field="destination_value"]');
    var statusNode = modal.querySelector('[data-telegram-form-status]');
    var saveButton = modal.querySelector('[data-telegram-save-button]');

    function syncTypeUi() {
        if (!typeSelect) {
            return;
        }
        var type = typeSelect.value;
        if (startWrap) {
            startWrap.hidden = type !== 'bot';
        }
        if (type === 'username') {
            if (valueLabel) valueLabel.textContent = ctcwI18n('telegram.label.username');
            if (valueHelp) valueHelp.textContent = ctcwI18n('telegram.help.username');
            if (valueInput) valueInput.placeholder = '@example_support';
        } else if (type === 'bot') {
            if (valueLabel) valueLabel.textContent = ctcwI18n('telegram.label.bot_username');
            if (valueHelp) valueHelp.textContent = ctcwI18n('telegram.help.bot');
            if (valueInput) valueInput.placeholder = '@example_bot';
        } else if (type === 'group') {
            if (valueLabel) valueLabel.textContent = ctcwI18n('telegram.label.group_url');
            if (valueHelp) valueHelp.textContent = ctcwI18n('telegram.help.group');
            if (valueInput) valueInput.placeholder = 'https://t.me/example_group';
        } else if (type === 'channel') {
            if (valueLabel) valueLabel.textContent = ctcwI18n('telegram.label.channel_url');
            if (valueHelp) valueHelp.textContent = ctcwI18n('telegram.help.channel');
            if (valueInput) valueInput.placeholder = 'https://t.me/example_channel';
        } else {
            if (valueLabel) valueLabel.textContent = ctcwI18n('telegram.label.username_or_link');
            if (valueHelp) valueHelp.textContent = ctcwI18n('telegram.help.link');
            if (valueInput) valueInput.placeholder = 'https://t.me/example';
        }
    }

    function clearErrors() {
        modal.querySelectorAll('[data-telegram-error]').forEach(function (node) {
            node.hidden = true;
            node.textContent = '';
        });
        if (statusNode) {
            statusNode.hidden = true;
            statusNode.textContent = '';
            statusNode.classList.remove('is-error', 'is-success');
        }
    }

    function showError(field, message) {
        var node = modal.querySelector('[data-telegram-error="' + field + '"]');
        if (!node) {
            if (statusNode) {
                statusNode.hidden = false;
                statusNode.textContent = message;
                statusNode.classList.add('is-error');
            }
            return;
        }
        node.hidden = false;
        node.textContent = message;
    }

    function showStatus(message, isError) {
        if (!statusNode) {
            return;
        }
        statusNode.hidden = false;
        statusNode.textContent = message;
        statusNode.classList.toggle('is-error', !!isError);
        statusNode.classList.toggle('is-success', !isError);
    }

    function validateInline() {
        clearErrors();
        var type = typeSelect ? typeSelect.value : 'username';
        var value = valueInput ? String(valueInput.value || '').trim() : '';
        var ok = true;

        if (!value) {
            showError('destination_value', ctcwI18n(type === 'group' || type === 'channel' ? 'telegram.error.enter_link' : 'telegram.error.enter_username'));
            ok = false;
        } else if ((type === 'username' || type === 'bot') && /\s/.test(value)) {
            showError('destination_value', ctcwI18n('telegram.error.username_spaces'));
            ok = false;
        } else if ((type === 'username' || type === 'bot') && /^(https?:)?\/\//i.test(value)) {
            showError('destination_value', ctcwI18n('telegram.error.username_not_url'));
            ok = false;
        }

        var startInput = modal.querySelector('[data-telegram-field="bot_start_parameter"]');
        if (type === 'bot' && startInput && startInput.value) {
            if (!/^[A-Za-z0-9_-]{1,64}$/.test(String(startInput.value).trim())) {
                showError('bot_start_parameter', ctcwI18n('telegram.error.start_param_chars'));
                ok = false;
            }
        }

        return ok;
    }

    function openTelegramDestinationModal(data) {
        clearErrors();
        if (form) {
            form.reset();
        }
        if (idInput) {
            idInput.value = data && data.id ? String(data.id) : '';
        }
        if (title) {
            title.textContent = data && data.id
                ? ctcwI18n('telegram.edit_destination')
                : ctcwI18n('telegram.add_destination');
        }
        if (data) {
            var map = {
                display_name: data.display_name || '',
                destination_type: data.destination_type || 'username',
                destination_value: data.destination_value || '',
                bot_start_parameter: data.bot_start_parameter || ''
            };
            Object.keys(map).forEach(function (name) {
                var field = modal.querySelector('[data-telegram-field="' + name + '"]');
                if (field) {
                    field.value = map[name];
                }
            });
            var active = modal.querySelector('input[name="is_active"]');
            if (active) {
                active.checked = data.is_active !== false;
            }
        } else {
            var activeDefault = modal.querySelector('input[name="is_active"]');
            if (activeDefault) {
                activeDefault.checked = true;
            }
        }
        syncTypeUi();
        if (typeof modal.showModal === 'function') {
            if (!modal.open) {
                modal.showModal();
            }
        } else {
            modal.setAttribute('open', 'open');
        }
        window.setTimeout(function () {
            var focusField = modal.querySelector('[data-telegram-field="display_name"]');
            if (focusField) {
                focusField.focus();
            }
        }, 30);
    }

    function closeTelegramDestinationModal() {
        if (typeof modal.close === 'function') {
            if (modal.open) {
                modal.close();
            }
        } else {
            modal.removeAttribute('open');
        }
    }

    window.openTelegramDestinationModal = openTelegramDestinationModal;
    window.closeTelegramDestinationModal = closeTelegramDestinationModal;

    if (typeSelect) {
        typeSelect.addEventListener('change', syncTypeUi);
    }
    if (valueInput) {
        valueInput.addEventListener('input', function () {
            var node = modal.querySelector('[data-telegram-error="destination_value"]');
            if (node) {
                node.hidden = true;
                node.textContent = '';
            }
        });
    }

    // One reusable binder for every Add Telegram Destination button (header + empty state).
    document.querySelectorAll('[data-open-telegram-modal]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openTelegramDestinationModal(null);
        });
    });

    document.querySelectorAll('[data-edit-telegram-destination]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            try {
                openTelegramDestinationModal(JSON.parse(button.getAttribute('data-edit-telegram-destination') || '{}'));
            } catch (error) {
                openTelegramDestinationModal(null);
            }
        });
    });

    modal.querySelectorAll('[data-close-telegram-modal]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            closeTelegramDestinationModal();
        });
    });

    // Backdrop click closes the dialog when supported by native <dialog>.
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeTelegramDestinationModal();
        }
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!validateInline()) {
                return;
            }

            if (saveButton) {
                saveButton.disabled = true;
                saveButton.classList.add('is-loading');
            }

            var formData = new FormData(form);
            formData.set('response', 'json');
            if (!formData.get('destination_action')) {
                formData.set('destination_action', 'save');
            }

            fetch(form.action || telegramSaveEndpoint(), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: formData
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data || {} };
                    });
                })
                .then(function (result) {
                    if (!result.data.success) {
                        var fieldErrors = result.data.field_errors || {};
                        Object.keys(fieldErrors).forEach(function (field) {
                            showError(field, fieldErrors[field]);
                        });
                        showStatus(
                            result.data.message || ctcwI18n('telegram.error.save_failed'),
                            true
                        );
                        if (saveButton) {
                            saveButton.disabled = false;
                            saveButton.classList.remove('is-loading');
                        }
                        return;
                    }

                    showStatus(result.data.message || ctcwI18n('telegram.flash.saved'), false);
                    window.setTimeout(reloadDestinationsView, 250);
                })
                .catch(function () {
                    showStatus(ctcwI18n('telegram.error.save_failed'), true);
                    if (saveButton) {
                        saveButton.disabled = false;
                        saveButton.classList.remove('is-loading');
                    }
                });
        });
    }
}
