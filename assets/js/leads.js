(function () {
    'use strict';

    function ctcwI18n(key, replacements) {
        var node = document.getElementById('ctcw-i18n');
        var strings = {};

        if (node && node.textContent) {
            try {
                strings = JSON.parse(node.textContent);
            } catch (error) {
                strings = {};
            }
        }

        var value = strings[key] || key;
        replacements = replacements || {};

        Object.keys(replacements).forEach(function (token) {
            value = value.split('{' + token + '}').join(String(replacements[token]));
        });

        return value;
    }

    function showLeadToast(root, message, isError) {
        var toast = root.querySelector('[data-lead-toast]');
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.toggle('is-error', !!isError);
        toast.hidden = false;

        window.clearTimeout(showLeadToast._timer);
        showLeadToast._timer = window.setTimeout(function () {
            toast.hidden = true;
        }, 3200);
    }

    function getVisibleLeadCheckboxes(root) {
        var body = root.querySelector('[data-leads-table-body]');
        if (!body) {
            return [];
        }

        return Array.from(body.querySelectorAll('.ctcw-lead-select'));
    }

    function getSelectedLeadIds(root) {
        return getVisibleLeadCheckboxes(root)
            .filter(function (checkbox) {
                return checkbox.checked;
            })
            .map(function (checkbox) {
                return parseInt(checkbox.value, 10);
            })
            .filter(function (id) {
                return id > 0;
            });
    }

    function updateLeadSelectionUi(root) {
        var checkboxes = getVisibleLeadCheckboxes(root);
        var selected = checkboxes.filter(function (checkbox) {
            return checkbox.checked;
        });
        var selectAll = root.querySelector('[data-select-all-leads]');
        var countEl = root.querySelector('[data-selected-lead-count]');
        var deleteButton = root.querySelector('[data-delete-selected-leads]');

        if (countEl) {
            countEl.textContent = ctcwI18n('lead.selected_count', { count: selected.length });
            countEl.hidden = selected.length === 0;
        }

        if (deleteButton) {
            deleteButton.disabled = selected.length === 0;
        }

        if (selectAll) {
            if (selected.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (selected.length === checkboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }

        checkboxes.forEach(function (checkbox) {
            var row = checkbox.closest('[data-lead-row]');
            if (row) {
                row.classList.toggle('is-selected', checkbox.checked);
            }
        });
    }

    function updateLeadCountMeta(root, delta) {
        var meta = root.querySelector('[data-leads-results-meta]');
        var currentTotal = parseInt(root.getAttribute('data-total-leads') || '0', 10);
        var nextTotal = Math.max(0, currentTotal + delta);
        root.setAttribute('data-total-leads', String(nextTotal));

        if (meta) {
            meta.textContent = nextTotal === 1
                ? ctcwI18n('results.leads_found_one', { count: nextTotal })
                : ctcwI18n('results.leads_found_other', { count: nextTotal });
        }
    }

    function ensureLeadEmptyState(root) {
        var body = root.querySelector('[data-leads-table-body]');
        if (body && body.querySelector('[data-lead-row]')) {
            return;
        }

        var tableWrap = root.querySelector('.table-wrap');
        if (tableWrap) {
            tableWrap.remove();
        }

        if (!root.querySelector('[data-leads-empty-state]')) {
            var emptyState = document.createElement('div');
            emptyState.className = 'empty-state compact-empty';
            emptyState.setAttribute('data-leads-empty-state', '');
            emptyState.innerHTML = '<p><strong>' + ctcwI18n('empty.no_leads_found') + '</strong></p><p>' + ctcwI18n('empty.no_leads_subtitle') + '</p>';
            var meta = root.querySelector('[data-leads-results-meta]');
            if (meta && meta.parentNode) {
                meta.parentNode.insertBefore(emptyState, meta.nextSibling);
            } else {
                root.appendChild(emptyState);
            }
        }
    }

    function removeLeadRows(root, leadIds) {
        var ids = {};
        leadIds.forEach(function (id) {
            ids[id] = true;
        });

        root.querySelectorAll('[data-lead-row]').forEach(function (row) {
            var leadId = parseInt(row.getAttribute('data-lead-id') || '0', 10);
            if (ids[leadId]) {
                row.remove();
            }
        });

        updateLeadCountMeta(root, -leadIds.length);
        updateLeadSelectionUi(root);
        ensureLeadEmptyState(root);
    }

    function closeLeadModal(modal) {
        if (modal) {
            modal.hidden = true;
        }
    }

    function setModalLoading(modal, loading) {
        if (!modal) {
            return;
        }

        modal.querySelectorAll('button').forEach(function (button) {
            button.disabled = loading;
        });
    }

    function postLeadAction(url, root, payload) {
        var formData = new FormData();
        formData.append('csrf_token', root.getAttribute('data-csrf-token') || '');

        Object.keys(payload).forEach(function (key) {
            var value = payload[key];
            if (Array.isArray(value)) {
                value.forEach(function (item) {
                    formData.append(key + '[]', String(item));
                });
                return;
            }

            formData.append(key, String(value));
        });

        return fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            return response.json().catch(function () {
                return { success: false, message: ctcwI18n('lead.delete_failed') };
            }).then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        });
    }

    function initLeadDeletion() {
        var root = document.querySelector('[data-leads-page]');
        if (!root) {
            return;
        }

        var singleModal = root.querySelector('[data-lead-single-modal]');
        var bulkModal = root.querySelector('[data-lead-bulk-modal]');
        var pendingLeadId = 0;

        root.querySelectorAll('[data-lead-modal-close], [data-lead-modal-cancel]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeLeadModal(singleModal);
                closeLeadModal(bulkModal);
            });
        });

        var selectAll = root.querySelector('[data-select-all-leads]');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                var checked = selectAll.checked;
                getVisibleLeadCheckboxes(root).forEach(function (checkbox) {
                    checkbox.checked = checked;
                });
                updateLeadSelectionUi(root);
            });
        }

        root.addEventListener('change', function (event) {
            if (event.target.classList.contains('ctcw-lead-select')) {
                updateLeadSelectionUi(root);
            }
        });

        root.addEventListener('click', function (event) {
            var deleteButton = event.target.closest('[data-delete-lead]');
            if (deleteButton) {
                pendingLeadId = parseInt(deleteButton.getAttribute('data-lead-id') || '0', 10);
                var phone = deleteButton.getAttribute('data-lead-phone') || '';
                var title = root.querySelector('#ctcwLeadSingleModalTitle');
                var message = root.querySelector('[data-lead-single-modal-message]');

                if (title) {
                    title.textContent = phone
                        ? ctcwI18n('lead.delete_title_with_phone', { phone: phone })
                        : ctcwI18n('lead.delete_title');
                }
                if (message) {
                    message.textContent = ctcwI18n('lead.delete_body');
                }
                if (singleModal) {
                    singleModal.hidden = false;
                }
                return;
            }

            if (event.target.closest('[data-delete-selected-leads]')) {
                var selectedIds = getSelectedLeadIds(root);
                if (selectedIds.length === 0) {
                    return;
                }

                var bulkMessage = root.querySelector('[data-lead-bulk-modal-message]');
                var bulkConfirm = root.querySelector('[data-lead-bulk-modal-confirm]');
                if (bulkMessage) {
                    bulkMessage.textContent = selectedIds.length === 1
                        ? ctcwI18n('lead.bulk_delete_body_one')
                        : ctcwI18n('lead.bulk_delete_body_other', { count: selectedIds.length });
                }
                if (bulkConfirm) {
                    bulkConfirm.textContent = selectedIds.length === 1
                        ? ctcwI18n('lead.bulk_delete_button_one')
                        : ctcwI18n('lead.bulk_delete_button_other', { count: selectedIds.length });
                }
                if (bulkModal) {
                    bulkModal.hidden = false;
                }
            }
        });

        var singleConfirm = root.querySelector('[data-lead-single-modal-confirm]');
        if (singleConfirm) {
            singleConfirm.addEventListener('click', function () {
                if (pendingLeadId <= 0) {
                    return;
                }

                setModalLoading(singleModal, true);
                postLeadAction('delete-lead.php', root, { lead_id: pendingLeadId })
                    .then(function (result) {
                        if (result.data && result.data.success) {
                            removeLeadRows(root, [pendingLeadId]);
                            showLeadToast(root, result.data.message || ctcwI18n('lead.deleted_one'), false);
                            closeLeadModal(singleModal);
                            pendingLeadId = 0;
                            return;
                        }

                        showLeadToast(root, (result.data && result.data.message) || ctcwI18n('lead.delete_failed'), true);
                    })
                    .catch(function () {
                        showLeadToast(root, ctcwI18n('lead.delete_failed'), true);
                    })
                    .finally(function () {
                        setModalLoading(singleModal, false);
                    });
            });
        }

        var bulkConfirm = root.querySelector('[data-lead-bulk-modal-confirm]');
        if (bulkConfirm) {
            bulkConfirm.addEventListener('click', function () {
                var selectedIds = getSelectedLeadIds(root);
                if (selectedIds.length === 0) {
                    return;
                }

                setModalLoading(bulkModal, true);
                postLeadAction('bulk-delete-leads.php', root, { lead_ids: selectedIds })
                    .then(function (result) {
                        if (result.data && result.data.success) {
                            var deletedIds = Array.isArray(result.data.deleted_ids) ? result.data.deleted_ids : selectedIds;
                            removeLeadRows(root, deletedIds);

                            var message = result.data.message || ctcwI18n('lead.deleted_other', { count: deletedIds.length });
                            if (result.data.partial_message) {
                                message = result.data.partial_message;
                            }

                            showLeadToast(root, message, !!result.data.partial);
                            closeLeadModal(bulkModal);

                            var selectAllCheckbox = root.querySelector('[data-select-all-leads]');
                            if (selectAllCheckbox) {
                                selectAllCheckbox.checked = false;
                                selectAllCheckbox.indeterminate = false;
                            }
                            updateLeadSelectionUi(root);
                            return;
                        }

                        showLeadToast(root, (result.data && result.data.message) || ctcwI18n('lead.delete_failed'), true);
                    })
                    .catch(function () {
                        showLeadToast(root, ctcwI18n('lead.delete_failed'), true);
                    })
                    .finally(function () {
                        setModalLoading(bulkModal, false);
                    });
            });
        }

        updateLeadSelectionUi(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLeadDeletion);
    } else {
        initLeadDeletion();
    }
})();
