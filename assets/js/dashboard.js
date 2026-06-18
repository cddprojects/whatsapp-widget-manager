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
        var mode = document.querySelector('[data-business-hours-mode]');
        var table = document.querySelector('[data-business-hours-table]');
        if (!mode || !table) return;
        table.hidden = mode.value !== 'custom';
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

    function renumberRandomRows() {
        document.querySelectorAll('[data-random-number-list] .repeat-row').forEach(function (row, index) {
            row.querySelectorAll('select, input').forEach(function (input) {
                input.name = input.name.replace(/random_numbers\[\d+\]/, 'random_numbers[' + index + ']');
            });
        });
    }

    function renumberManualRows() {
        document.querySelectorAll('[data-manual-number-list] .repeat-row').forEach(function (row, index) {
            row.querySelectorAll('select, input').forEach(function (input) {
                input.name = input.name.replace(/manual_numbers\[\d+\]/, 'manual_numbers[' + index + ']');
            });
        });
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

        var addButton = event.target.closest('[data-add-random-number]');
        if (addButton) {
            var list = document.querySelector('[data-random-number-list]');
            var first = list && list.querySelector('.repeat-row');
            if (list && first) {
                var clone = first.cloneNode(true);
                clone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                list.appendChild(clone);
                renumberRandomRows();
            }
        }

        var addManualButton = event.target.closest('[data-add-manual-number]');
        if (addManualButton) {
            var manualList = document.querySelector('[data-manual-number-list]');
            var manualFirst = manualList && manualList.querySelector('.repeat-row');
            if (manualList && manualFirst) {
                var manualClone = manualFirst.cloneNode(true);
                manualClone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                manualList.appendChild(manualClone);
                renumberManualRows();
            }
        }

        var removeButton = event.target.closest('[data-remove-row]');
        if (removeButton) {
            var rows = document.querySelectorAll('[data-random-number-list] .repeat-row');
            if (rows.length > 1) {
                removeButton.closest('.repeat-row').remove();
                renumberRandomRows();
            }
        }

        var removeManualButton = event.target.closest('[data-remove-manual-row]');
        if (removeManualButton) {
            var manualRows = document.querySelectorAll('[data-manual-number-list] .repeat-row');
            if (manualRows.length > 1) {
                removeManualButton.closest('.repeat-row').remove();
                renumberManualRows();
            }
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

    document.querySelectorAll('[data-country-code-field]').forEach(bindCountrySearch);

    function renumberClientNumbers(form) {
        var items = form.querySelectorAll('[data-client-number-item]');
        items.forEach(function (item, index) {
            var countryInput = item.querySelector('[data-number-country]');
            var phoneInput = item.querySelector('[data-number-phone]');
            if (countryInput) {
                countryInput.name = 'manual_numbers[' + index + '][country_code]';
            }
            if (phoneInput) {
                phoneInput.name = 'manual_numbers[' + index + '][number]';
            }
        });
    }

    function hideClientEmptyState(form) {
        var emptyState = form.querySelector('[data-client-empty-state]');
        if (emptyState) {
            emptyState.remove();
        }
    }

    function showClientDraft(form) {
        var draft = form.querySelector('[data-client-number-draft]');
        if (!draft) {
            return;
        }
        draft.hidden = false;
        draft.querySelectorAll('[data-country-code-field]').forEach(function (field) {
            syncCountryField(field, '+60');
        });
        var phoneInput = draft.querySelector('[data-client-draft-phone]');
        if (phoneInput) {
            phoneInput.value = '';
            phoneInput.focus();
        }
    }

    function hideClientDraft(form) {
        var draft = form.querySelector('[data-client-number-draft]');
        if (draft) {
            draft.hidden = true;
        }
    }

    function addClientNumber(form, countryCode, phoneNumber) {
        var list = form.querySelector('[data-client-number-list]');
        var template = document.getElementById('client-number-item-template');
        if (!list || !template) {
            return false;
        }

        var digits = cleanDigits(phoneNumber);
        if (digits.length < 7) {
            window.alert('Please enter a valid phone number.');
            return false;
        }

        var duplicate = false;
        list.querySelectorAll('[data-client-number-item]').forEach(function (item) {
            var existingCountry = item.querySelector('[data-number-country]');
            var existingPhone = item.querySelector('[data-number-phone]');
            if (!existingCountry || !existingPhone) {
                return;
            }
            var existingFull = cleanDigits(existingCountry.value) + cleanDigits(existingPhone.value);
            var nextFull = cleanDigits(countryCode) + digits;
            if (existingFull === nextFull) {
                duplicate = true;
            }
        });
        if (duplicate) {
            window.alert('This number is already in your list.');
            return false;
        }

        hideClientEmptyState(form);
        var item = template.content.firstElementChild.cloneNode(true);
        item.querySelector('[data-display-country]').textContent = countryCode;
        item.querySelector('[data-display-phone]').textContent = phoneNumber.trim();
        item.querySelector('[data-number-country]').value = countryCode;
        item.querySelector('[data-number-phone]').value = phoneNumber.trim();
        list.appendChild(item);
        renumberClientNumbers(form);
        return true;
    }

    document.addEventListener('click', function (event) {
        var manualForm = document.querySelector('[data-client-manual-form]');
        if (!manualForm) {
            return;
        }

        if (event.target.closest('[data-client-add-number]')) {
            showClientDraft(manualForm);
            return;
        }

        if (event.target.closest('[data-client-cancel-number]')) {
            hideClientDraft(manualForm);
            return;
        }

        if (event.target.closest('[data-client-confirm-number]')) {
            var draft = manualForm.querySelector('[data-client-number-draft]');
            if (!draft) {
                return;
            }
            var countryField = draft.querySelector('[data-country-code-field]');
            var phoneInput = draft.querySelector('[data-client-draft-phone]');
            var countryCode = '+60';
            if (countryField) {
                var searchInput = countryField.querySelector('[data-country-search]');
                var hiddenInput = countryField.querySelector('[data-country-value]');
                countryCode = resolveCountryCode(
                    searchInput ? searchInput.value : '',
                    hiddenInput ? hiddenInput.value : '+60'
                );
                syncCountryField(countryField, countryCode);
            }
            if (addClientNumber(manualForm, countryCode, phoneInput ? phoneInput.value : '')) {
                hideClientDraft(manualForm);
            }
            return;
        }

        if (event.target.closest('[data-client-remove-number]')) {
            var item = event.target.closest('[data-client-number-item]');
            if (!item) {
                return;
            }
            if (!window.confirm('Remove this number from your list?')) {
                return;
            }
            item.remove();
            renumberClientNumbers(manualForm);
            if (!manualForm.querySelector('[data-client-number-item]')) {
                var list = manualForm.querySelector('[data-client-number-list]');
                if (list && !list.querySelector('[data-client-empty-state]')) {
                    var empty = document.createElement('div');
                    empty.className = 'empty-state compact-empty';
                    empty.setAttribute('data-client-empty-state', '');
                    empty.innerHTML = '<p>No numbers added yet. Click Add Number to get started.</p>';
                    list.appendChild(empty);
                }
            }
        }
    });

    var manualClientForm = document.querySelector('[data-client-manual-form]');
    if (manualClientForm) {
        manualClientForm.addEventListener('submit', function (event) {
            if (!manualClientForm.querySelector('[data-client-number-item]')) {
                event.preventDefault();
                window.alert('Please add at least one phone number before saving.');
            }
        });
    }
})();
