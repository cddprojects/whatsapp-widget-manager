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

        var removeButton = event.target.closest('[data-remove-row]');
        if (removeButton) {
            var rows = document.querySelectorAll('[data-random-number-list] .repeat-row');
            if (rows.length > 1) {
                removeButton.closest('.repeat-row').remove();
                renumberRandomRows();
            }
        }

        var resetButton = event.target.closest('[data-reset-custom-code]');
        if (resetButton && !confirm('Reset all custom code fields?')) {
            event.preventDefault();
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
})();
