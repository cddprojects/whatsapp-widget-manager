(function () {
    'use strict';

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

    function refreshGreetingCaptureOptions() {
        var captureToggle = document.querySelector('[data-greeting-capture-toggle]');
        var options = document.querySelector('[data-greeting-capture-options]');
        var forceToggle = document.querySelector('[data-greeting-force-toggle]');
        var requiredToggle = document.querySelector('[data-greeting-phone-required]');
        if (!captureToggle || !options) {
            return;
        }

        var captureEnabled = captureToggle.checked;
        options.hidden = !captureEnabled;
        options.classList.toggle('is-disabled', !captureEnabled);
        options.querySelectorAll('input, select, textarea').forEach(function (field) {
            if (field === captureToggle) {
                return;
            }
            field.disabled = !captureEnabled;
        });

        if (!captureEnabled && forceToggle) {
            forceToggle.checked = false;
        }

        if (forceToggle && requiredToggle) {
            if (forceToggle.checked) {
                requiredToggle.checked = true;
                requiredToggle.disabled = true;
            } else if (captureEnabled) {
                requiredToggle.disabled = false;
            }
        }
    }

    function countPhoneRows(list) {
        return list.querySelectorAll('[data-phone-number-row]').length;
    }

    function getPhoneCard(list) {
        return list ? list.closest('[data-phone-numbers-card]') : null;
    }

    function canRemovePhoneRows(list, removeCount) {
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

        if (countEl) {
            countEl.textContent = selected.length + ' selected';
        }

        if (deleteButton) {
            deleteButton.disabled = selected.length === 0 || selected.length === total;
        }

        if (errorEl) {
            errorEl.hidden = !(total > 0 && selected.length === total);
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

        if (count === 0 || count === total) {
            updateBulkDeleteUI(card);
            return;
        }

        var message = modal.querySelector('[data-phone-bulk-modal-message]');
        var confirmButton = modal.querySelector('[data-phone-bulk-modal-confirm]');

        if (message) {
            message.textContent = 'You are about to remove ' + count + ' phone numbers. This action cannot be undone until you save your changes.';
        }
        if (confirmButton) {
            confirmButton.textContent = 'Delete ' + count + ' numbers';
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
            var hiddenInput = row.querySelector('[data-country-value]');
            var phoneInput = row.querySelector('[data-row-phone]');
            var searchInput = row.querySelector('[data-country-search]');
            var listBaseId = list.id || 'phone-number-list';

            if (hiddenInput) {
                hiddenInput.name = fieldPrefix + '[' + index + '][country_code]';
            }
            if (phoneInput) {
                phoneInput.name = fieldPrefix + '[' + index + '][number]';
            }
            if (searchInput) {
                searchInput.id = listBaseId + '-country-' + index;
                searchInput.setAttribute('list', listBaseId + '-country-' + index + '-list');
            }

            var datalist = row.querySelector('datalist');
            if (datalist) {
                datalist.id = listBaseId + '-country-' + index + '-list';
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

    function populateCountryDatalist(datalist) {
        if (!datalist || datalist.options.length > 0) {
            return;
        }

        countryOptions.forEach(function (option) {
            var node = document.createElement('option');
            node.value = option.label;
            datalist.appendChild(node);
        });
    }

    function preparePhoneRow(row, list) {
        row.querySelectorAll('[data-country-code-field]').forEach(function (field) {
            populateCountryDatalist(field.querySelector('datalist'));
            bindCountrySearch(field);
        });
        renumberPhoneRows(list);
    }

    function syncPhoneListCountryFields(list) {
        list.querySelectorAll('[data-country-code-field]').forEach(function (field) {
            var searchInput = field.querySelector('[data-country-search]');
            var hiddenInput = field.querySelector('[data-country-value]');
            if (!searchInput || !hiddenInput) {
                return;
            }
            var resolved = resolveCountryCode(searchInput.value, hiddenInput.value || '+60');
            syncCountryField(field, resolved);
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
            row.querySelectorAll('[data-country-code-field]').forEach(function (field) {
                syncCountryField(field, '+60');
            });
        } else {
            var template = document.getElementById(list.id + '-template');
            if (!template) {
                return;
            }
            row = template.content.firstElementChild.cloneNode(true);
            row.querySelectorAll('[data-country-code-field]').forEach(function (field) {
                syncCountryField(field, '+60');
            });
        }

        hidePhoneEmptyState(list);
        list.appendChild(row);
        preparePhoneRow(row, list);

        var card = getPhoneCard(list);
        setPhoneBulkToolbarVisible(card, true);
        updateBulkDeleteUI(card);
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
        var selectedStyles = Array.from(document.querySelectorAll('[data-style-select]')).map(function (select) {
            return select.value;
        });

        document.querySelectorAll('[data-style-preview-card]').forEach(function (card) {
            card.classList.toggle('is-selected', selectedStyles.indexOf(card.getAttribute('data-style-preview-card')) !== -1);
        });
    }

    document.addEventListener('click', function (event) {
        var copyButton = event.target.closest('[data-copy-target]');
        if (copyButton) {
            var target = document.querySelector(copyButton.getAttribute('data-copy-target'));
            if (target) {
                target.select();
                navigator.clipboard.writeText(target.value).then(function () {
                    copyButton.textContent = 'Copied!';
                    setTimeout(function () { copyButton.textContent = 'Copy code'; }, 1600);
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
                window.alert('At least one WhatsApp number must remain active.');
                updateBulkDeleteUI(getPhoneCard(phoneList));
                return;
            }
            if (!window.confirm('Delete this number from the list?')) {
                return;
            }
            phoneRow.remove();
            renumberPhoneRows(phoneList);
            showPhoneEmptyState(phoneList);

            var phoneCard = getPhoneCard(phoneList);
            if (countPhoneRows(phoneList) === 0) {
                setPhoneBulkToolbarVisible(phoneCard, false);
            }
            updateBulkDeleteUI(phoneCard);
            return;
        }

        var resetButton = event.target.closest('[data-reset-custom-code]');
        if (resetButton && !confirm('Reset all custom code fields?')) {
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

    var greetingCaptureToggle = document.querySelector('[data-greeting-capture-toggle]');
    var greetingForceToggle = document.querySelector('[data-greeting-force-toggle]');
    if (greetingCaptureToggle) {
        greetingCaptureToggle.addEventListener('change', refreshGreetingCaptureOptions);
    }
    if (greetingForceToggle) {
        greetingForceToggle.addEventListener('change', refreshGreetingCaptureOptions);
    }
    refreshGreetingCaptureOptions();

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

    var countryDataNode = document.getElementById('country-code-data');
    var countryOptions = [];
    if (countryDataNode) {
        try {
            countryOptions = JSON.parse(countryDataNode.textContent || '[]');
        } catch (error) {
            countryOptions = [];
        }
    }

    function cleanDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function resolveCountryCode(searchValue, fallbackCode) {
        var query = String(searchValue || '').trim().toLowerCase();
        if (query === '') {
            return fallbackCode || '+60';
        }

        var exact = countryOptions.find(function (option) {
            return option.code === searchValue || option.label === searchValue;
        });
        if (exact) {
            return exact.code;
        }

        var digitQuery = cleanDigits(query);
        var matches = countryOptions.filter(function (option) {
            var codeDigits = cleanDigits(option.code);
            var label = String(option.label || '').toLowerCase();
            var name = String(option.name || '').toLowerCase();
            return label.indexOf(query) !== -1
                || name.indexOf(query) !== -1
                || (digitQuery !== '' && codeDigits.indexOf(digitQuery) === 0)
                || (digitQuery !== '' && digitQuery.indexOf(codeDigits) === 0);
        });

        if (matches.length === 1) {
            return matches[0].code;
        }

        if (matches.length > 1) {
            var best = matches.find(function (option) {
                return String(option.label || '').toLowerCase() === query
                    || String(option.name || '').toLowerCase() === query
                    || cleanDigits(option.code) === digitQuery;
            });
            if (best) {
                return best.code;
            }
        }

        return fallbackCode || '+60';
    }

    function syncCountryField(field, code) {
        var match = countryOptions.find(function (option) {
            return option.code === code;
        });
        var searchInput = field.querySelector('[data-country-search]');
        var hiddenInput = field.querySelector('[data-country-value]');
        if (hiddenInput) {
            hiddenInput.value = code;
        }
        if (searchInput) {
            searchInput.value = match ? match.label : code;
        }
    }

    function bindCountrySearch(field) {
        var searchInput = field.querySelector('[data-country-search]');
        var hiddenInput = field.querySelector('[data-country-value]');
        if (!searchInput || !hiddenInput) {
            return;
        }

        searchInput.addEventListener('change', function () {
            var resolved = resolveCountryCode(searchInput.value, hiddenInput.value || '+60');
            syncCountryField(field, resolved);
        });

        searchInput.addEventListener('blur', function () {
            var resolved = resolveCountryCode(searchInput.value, hiddenInput.value || '+60');
            syncCountryField(field, resolved);
        });
    }

    document.querySelectorAll('[data-phone-number-list]').forEach(function (list) {
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

            syncPhoneListCountryFields(phoneList);

            if (!phoneList.querySelector('[data-phone-number-row]')) {
                event.preventDefault();
                window.alert('Please keep at least one active WhatsApp number.');
            }
        });
    });

    document.querySelectorAll('[data-phone-numbers-card]').forEach(function (card) {
        initPhoneBulkDelete(card);
    });

    initLiveWidgetPreview();
    initClientCreateForm();
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
            return { level: 'weak', label: 'Weak' };
        }

        var hasUpper = /[A-Z]/.test(password);
        var hasLower = /[a-z]/.test(password);
        var hasNumber = /[0-9]/.test(password);
        var hasSymbol = /[!@#$%^&*()_+\-=[\]{};:,.?/~]/.test(password);
        var types = [hasUpper, hasLower, hasNumber, hasSymbol].filter(Boolean).length;

        if (password.length >= 16 && hasUpper && hasLower && hasNumber && hasSymbol) {
            return { level: 'strong', label: 'Strong' };
        }
        if (password.length >= 8 && types >= 2) {
            return { level: 'normal', label: 'Normal' };
        }
        return { level: 'weak', label: 'Weak' };
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
        confirmInput.setCustomValidity(mismatch ? 'Password and confirm password do not match.' : '');
    }

    function fillGeneratedPassword(value) {
        passwordInput.value = value;
        confirmInput.value = value;
        passwordInput.type = 'text';
        confirmInput.type = 'text';
        if (toggleButton) {
            toggleButton.textContent = 'Hide';
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
            toggleButton.textContent = showing ? 'Show' : 'Hide';
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
            passwordInput.setCustomValidity('Password must be at least 8 characters.');
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

function initLiveWidgetPreview() {
    var form = document.querySelector('[data-widget-form]');
    var previewRoot = document.getElementById('ctcwAdminLivePreview');
    if (!form || !previewRoot) {
        return;
    }

    var customCssStyle = document.getElementById('ctcwAdminLivePreviewCustomCss');
    var previewToggle = document.querySelector('[data-live-preview-toggle]');
    var previewEnabledStorageKey = 'ctcw_admin_live_preview_enabled';
    var debugFrameEnabled = new URLSearchParams(window.location.search).get('debug_preview_frame') === '1';
    var iconNode = document.getElementById('ctcw-preview-icon');
    var whatsappIcon = '';

    if (iconNode) {
        try {
            whatsappIcon = JSON.parse(iconNode.textContent || '""');
        } catch (error) {
            whatsappIcon = '';
        }
    }

    function isLivePreviewEnabled() {
        if (!previewToggle) {
            return true;
        }
        return previewToggle.checked;
    }

    function setLivePreviewEnabled(enabled) {
        try {
            window.localStorage.setItem(previewEnabledStorageKey, enabled ? '1' : '0');
        } catch (error) {
            // Ignore storage failures in restricted browsers.
        }

        if (previewRoot) {
            previewRoot.style.display = enabled ? 'block' : 'none';
            previewRoot.setAttribute('aria-hidden', enabled ? 'false' : 'true');
        }

        if (enabled) {
            renderLiveWidgetPreview();
            return;
        }

        if (previewRoot) {
            previewRoot.innerHTML = '';
        }
    }

    function initLivePreviewToggle() {
        if (!previewToggle) {
            setLivePreviewEnabled(true);
            return;
        }

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
        setLivePreviewEnabled(enabled);
    }

    function updateDebugFrameState() {
        if (!previewRoot || !debugFrameEnabled) {
            if (previewRoot) {
                previewRoot.classList.remove('is-frame-debug');
            }
            return;
        }

        previewRoot.classList.add('is-frame-debug');
        if (!document.querySelector('style[data-preview-frame-debug]')) {
            var debugStyle = document.createElement('style');
            debugStyle.setAttribute('data-preview-frame-debug', 'true');
            debugStyle.textContent = '#ctcwAdminLivePreview.is-frame-debug .ctcw-admin-live-preview-inner{outline:2px dashed rgba(37,99,235,.65);background:rgba(37,99,235,.08);}';
            document.head.appendChild(debugStyle);
        }
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
                return '#ctcwAdminLivePreview ' + token;
            }).join(', ');

            return before + scoped + '{';
        });
    }

    function isPreviewOnline() {
        return getFieldValue('business_hours_mode') !== 'always_closed';
    }

    function buildGreetingHtml(verticalType, horizontalType) {
        if (!getFieldValue('greeting_enabled')) {
            return '';
        }

        var title = escapeHtml(getFieldValue('greeting_title') || 'Hi 👋');
        var message = escapeHtml(getFieldValue('greeting_message') || 'Need Help? Contact Us !');
        var capturePhone = !!getFieldValue('greeting_capture_phone');
        var forcePhone = !!getFieldValue('greeting_force_phone_capture');
        var placeholder = escapeHtml(getFieldValue('greeting_phone_placeholder') || 'Enter your phone number');
        var submitText = escapeHtml(getFieldValue('greeting_submit_text') || 'Continue to WhatsApp');
        var forceNote = forcePhone
            ? '<small class="ctcw-preview-force-note">Phone required before WhatsApp</small>'
            : '';

        if (!capturePhone) {
            return '<div class="ctcw-greeting is-visible">'
                + '<strong>' + title + '</strong>'
                + '<p>' + message + '</p>'
                + forceNote
                + '</div>';
        }

        return '<div class="ctcw-greeting has-capture is-visible">'
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
            + '</div>'
            + '</div>';
    }

    function updatePreviewPosition() {
        var verticalType = getFieldValue('desktop_vertical_position_type') || 'bottom';
        var verticalValue = getFieldValue('desktop_vertical_position_value') || '25px';
        var horizontalType = getFieldValue('desktop_horizontal_position_type') || 'right';
        var horizontalValue = getFieldValue('desktop_horizontal_position_value') || '25px';

        previewRoot.style.position = 'fixed';
        previewRoot.style.top = 'auto';
        previewRoot.style.bottom = 'auto';
        previewRoot.style.left = 'auto';
        previewRoot.style.right = 'auto';
        previewRoot.style[verticalType] = verticalValue;
        previewRoot.style[horizontalType] = horizontalValue;

        previewRoot.classList.toggle('is-anchor-top', verticalType === 'top');
        previewRoot.classList.toggle('is-anchor-left', horizontalType === 'left');
    }

    function updateCustomCssPreview() {
        if (!customCssStyle) {
            return;
        }

        customCssStyle.textContent = scopePreviewCss(getFieldValue('custom_css'));
    }

    function renderLiveWidgetPreview() {
        if (!isLivePreviewEnabled()) {
            return;
        }

        var style = getFieldValue('desktop_style') || 'style-1';
        var verticalType = getFieldValue('desktop_vertical_position_type') || 'bottom';
        var horizontalType = getFieldValue('desktop_horizontal_position_type') || 'right';
        var online = isPreviewOnline();
        var cta = escapeHtml(online ? (getFieldValue('call_to_action') || 'WhatsApp us') : (getFieldValue('offline_message') || 'We are currently offline.'));
        var onlineClass = online ? 'is-online' : 'is-offline';

        previewRoot.innerHTML = '<div class="ctcw-admin-live-preview-inner">'
            + '<span class="ctcw-preview-badge">Preview</span>'
            + '<div class="ctcw-container ' + escapeHtml(style) + ' ' + onlineClass + '">'
            + buildGreetingHtml(verticalType, horizontalType)
            + '<button type="button" class="ctcw-widget" tabindex="-1" aria-label="Widget preview">'
            + '<span class="ctcw-icon">' + whatsappIcon + '</span>'
            + '<span class="ctcw-text">' + cta + '</span>'
            + '</button>'
            + '</div>'
            + '</div>';

        updatePreviewPosition();
        updateCustomCssPreview();
        updateDebugFrameState();
    }

    var watchedNames = [
        'desktop_style',
        'mobile_style',
        'call_to_action',
        'desktop_vertical_position_type',
        'desktop_vertical_position_value',
        'desktop_horizontal_position_type',
        'desktop_horizontal_position_value',
        'mobile_vertical_position_type',
        'mobile_vertical_position_value',
        'mobile_horizontal_position_type',
        'mobile_horizontal_position_value',
        'same_mobile_desktop_settings',
        'show_desktop',
        'show_mobile',
        'show_global',
        'business_hours_mode',
        'offline_message',
        'greeting_enabled',
        'greeting_title',
        'greeting_message',
        'greeting_capture_phone',
        'greeting_force_phone_capture',
        'greeting_phone_placeholder',
        'greeting_submit_text',
        'custom_css'
    ];

    watchedNames.forEach(function (name) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) {
            return;
        }
        field.addEventListener('input', renderLiveWidgetPreview);
        field.addEventListener('change', renderLiveWidgetPreview);
    });

    var greetingCaptureToggle = form.querySelector('[data-greeting-capture-toggle]');
    var greetingForceToggle = form.querySelector('[data-greeting-force-toggle]');
    if (greetingCaptureToggle) {
        greetingCaptureToggle.addEventListener('change', renderLiveWidgetPreview);
    }
    if (greetingForceToggle) {
        greetingForceToggle.addEventListener('change', renderLiveWidgetPreview);
    }

    initLivePreviewToggle();
}
