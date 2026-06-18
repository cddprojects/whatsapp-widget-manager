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

    initLiveWidgetPreview();
})();

function initLiveWidgetPreview() {
    var form = document.querySelector('[data-widget-form]');
    var canvas = document.getElementById('ctcwLivePreviewCanvas');
    var previewRoot = document.getElementById('ctcwLiveWidgetPreview');
    if (!form || !canvas || !previewRoot) {
        return;
    }

    var toast = document.getElementById('ctcwLivePreviewToast');
    var greetingToggle = document.getElementById('ctcwToggleGreetingPreview');
    var badgesWrap = document.querySelector('[data-live-preview-badges]');
    var customCssStyle = document.getElementById('ctcwLivePreviewCustomCss');
    var iconNode = document.getElementById('ctcw-preview-icon');
    var whatsappIcon = '';
    var greetingVisible = false;
    var toastTimer = null;

    if (iconNode) {
        try {
            whatsappIcon = JSON.parse(iconNode.textContent || '""');
        } catch (error) {
            whatsappIcon = '';
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
                return '#ctcwLivePreviewCanvas ' + token;
            }).join(', ');

            return before + scoped + '{';
        });
    }

    function showPreviewToast(message) {
        if (!toast) {
            return;
        }
        toast.textContent = message;
        toast.hidden = false;
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () {
            toast.hidden = true;
        }, 2600);
    }

    function isPreviewOnline() {
        var mode = getFieldValue('business_hours_mode');
        if (mode === 'always_closed') {
            return false;
        }
        if (mode === 'always_open') {
            return true;
        }
        return true;
    }

    function buildGreetingHtml() {
        if (!getFieldValue('greeting_enabled')) {
            return '';
        }

        var title = escapeHtml(getFieldValue('greeting_title') || 'Hi 👋');
        var message = escapeHtml(getFieldValue('greeting_message') || 'Need Help? Contact Us !');
        var capturePhone = !!getFieldValue('greeting_capture_phone');
        var placeholder = escapeHtml(getFieldValue('greeting_phone_placeholder') || 'Enter your phone number');
        var submitText = escapeHtml(getFieldValue('greeting_submit_text') || 'Continue to WhatsApp');

        if (!capturePhone) {
            return '<div class="ctcw-greeting' + (greetingVisible ? ' is-visible' : '') + '" data-preview-greeting>'
                + '<button type="button" class="ctcw-close" aria-label="Close greeting" data-preview-close-greeting>&times;</button>'
                + '<strong>' + title + '</strong>'
                + '<p>' + message + '</p>'
                + '</div>';
        }

        return '<div class="ctcw-greeting has-capture' + (greetingVisible ? ' is-visible' : '') + '" data-preview-greeting>'
            + '<button type="button" class="ctcw-close" aria-label="Close greeting" data-preview-close-greeting>&times;</button>'
            + '<div class="ctcw-greeting-form">'
            + '<strong>' + title + '</strong>'
            + '<p>' + message + '</p>'
            + '<div class="ctcw-phone-field">'
            + '<div class="ctcw-phone-row">'
            + '<input class="ctcw-phone-input" type="tel" placeholder="' + placeholder + '" data-preview-phone>'
            + '<button type="button" class="ctcw-greeting-submit" aria-label="' + submitText + '" data-preview-greeting-submit>'
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

        previewRoot.style.position = 'absolute';
        previewRoot.style.top = 'auto';
        previewRoot.style.bottom = 'auto';
        previewRoot.style.left = 'auto';
        previewRoot.style.right = 'auto';
        previewRoot.style[verticalType] = verticalValue;
        previewRoot.style[horizontalType] = horizontalValue;

        var container = previewRoot.querySelector('.ctcw-container');
        if (container) {
            container.style.alignItems = horizontalType === 'left' ? 'flex-start' : 'flex-end';
        }
    }

    function updatePreviewBadges() {
        if (!badgesWrap) {
            return;
        }

        badgesWrap.innerHTML = '';
        var badges = [];

        if (getFieldValue('greeting_force_phone_capture')) {
            badges.push('Phone required before WhatsApp');
        }
        if (!getFieldValue('show_desktop')) {
            badges.push('Hidden on desktop');
        }
        if (!getFieldValue('show_global')) {
            badges.push('Hidden globally');
        }

        badges.forEach(function (label) {
            var badge = document.createElement('span');
            badge.className = 'ctcw-live-preview-badge';
            badge.textContent = label;
            badgesWrap.appendChild(badge);
        });

        badgesWrap.hidden = badges.length === 0;
    }

    function updateGreetingToggle() {
        if (!greetingToggle) {
            return;
        }

        var enabled = !!getFieldValue('greeting_enabled');
        greetingToggle.hidden = !enabled;
        greetingToggle.textContent = greetingVisible ? 'Hide greeting' : 'Show greeting';
    }

    function updateCustomCssPreview() {
        if (!customCssStyle) {
            return;
        }

        customCssStyle.textContent = scopePreviewCss(getFieldValue('custom_css'));
    }

    function bindPreviewInteractions() {
        var container = previewRoot.querySelector('.ctcw-container');
        var button = previewRoot.querySelector('[data-preview-widget-button]');
        if (!container || !button) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();

            if (!isPreviewOnline()) {
                showPreviewToast('Preview mode: widget is offline.');
                return;
            }

            if (getFieldValue('greeting_force_phone_capture') && getFieldValue('greeting_capture_phone')) {
                greetingVisible = true;
                renderLiveWidgetPreview();
                showPreviewToast('Preview mode: enter phone number to continue.');
                return;
            }

            showPreviewToast('Preview mode: WhatsApp redirect disabled.');
        });

        container.addEventListener('mouseenter', function () {
            container.classList.add('is-hovering');
        });
        container.addEventListener('mouseleave', function () {
            container.classList.remove('is-hovering');
        });

        var closeGreeting = previewRoot.querySelector('[data-preview-close-greeting]');
        if (closeGreeting) {
            closeGreeting.addEventListener('click', function (event) {
                event.preventDefault();
                greetingVisible = false;
                renderLiveWidgetPreview();
            });
        }

        var greetingSubmit = previewRoot.querySelector('[data-preview-greeting-submit]');
        if (greetingSubmit) {
            greetingSubmit.addEventListener('click', function (event) {
                event.preventDefault();
                showPreviewToast('Preview mode: lead capture disabled.');
            });
        }
    }

    function renderLiveWidgetPreview() {
        if (!getFieldValue('greeting_enabled')) {
            greetingVisible = false;
        }

        var style = getFieldValue('desktop_style') || 'style-1';
        var online = isPreviewOnline();
        var cta = escapeHtml(online ? (getFieldValue('call_to_action') || 'WhatsApp us') : (getFieldValue('offline_message') || 'We are currently offline.'));
        var greetingMessage = escapeHtml(getFieldValue('greeting_message') || getFieldValue('call_to_action') || 'WhatsApp us');
        var onlineClass = isPreviewOnline() ? 'is-online' : 'is-offline';
        var hiddenClass = getFieldValue('show_desktop') ? '' : ' is-hidden';
        var hoverBox = style === 'style-5'
            ? '<span class="ctcw-hover-box">' + greetingMessage + '</span>'
            : '';

        previewRoot.innerHTML = '<div class="ctcw-container ' + escapeHtml(style) + ' ' + onlineClass + hiddenClass + '">'
            + buildGreetingHtml()
            + '<button type="button" class="ctcw-widget" data-preview-widget-button>'
            + '<span class="ctcw-icon">' + whatsappIcon + '</span>'
            + '<span class="ctcw-text">' + cta + '</span>'
            + hoverBox
            + '</button>'
            + '</div>';

        updatePreviewPosition();
        updatePreviewBadges();
        updateGreetingToggle();
        updateCustomCssPreview();
        bindPreviewInteractions();
    }

    if (greetingToggle) {
        greetingToggle.addEventListener('click', function () {
            if (!getFieldValue('greeting_enabled')) {
                return;
            }
            greetingVisible = !greetingVisible;
            renderLiveWidgetPreview();
        });
    }

    var watchedNames = [
        'desktop_style',
        'mobile_style',
        'call_to_action',
        'desktop_position_type',
        'desktop_vertical_position_type',
        'desktop_vertical_position_value',
        'desktop_horizontal_position_type',
        'desktop_horizontal_position_value',
        'mobile_position_type',
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

    renderLiveWidgetPreview();
}
