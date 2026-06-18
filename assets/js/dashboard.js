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
            if (!window.confirm('Delete this number from the list?')) {
                return;
            }
            phoneRow.remove();
            renumberPhoneRows(phoneList);
            showPhoneEmptyState(phoneList);
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
                window.alert('Please add at least one WhatsApp number.');
            }
        });
    });
})();
