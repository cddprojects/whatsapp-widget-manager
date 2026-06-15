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

    document.querySelectorAll('[data-style-select]').forEach(function (select) {
        select.addEventListener('change', refreshSelectedStyleCards);
    });
    refreshSelectedStyleCards();

    var initialSection = window.location.hash ? window.location.hash.slice(1) : '';
    showSettingsPanel(initialSection || 'whatsapp-number', false);
})();
