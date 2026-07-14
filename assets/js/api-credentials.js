(function () {
    'use strict';

    var modal = document.getElementById('api-key-modal');
    if (!modal) {
        return;
    }

    var bootstrapNode = document.getElementById('api-key-bootstrap');
    var bootstrap = { client: {}, widgets: {}, i18n: {} };
    if (bootstrapNode && bootstrapNode.textContent) {
        try {
            bootstrap = JSON.parse(bootstrapNode.textContent);
        } catch (error) {
            bootstrap = { client: {}, widgets: {}, i18n: {} };
        }
    }

    var state = {
        ownerType: '',
        ownerId: 0,
        ownerLabel: '',
        clientLabel: '',
        credential: null
    };

    var elements = {
        title: document.getElementById('api-key-modal-title'),
        context: modal.querySelector('[data-api-key-context]'),
        empty: modal.querySelector('[data-api-key-empty]'),
        details: modal.querySelector('[data-api-key-details]'),
        generate: modal.querySelector('[data-api-key-generate]'),
        masked: modal.querySelector('[data-api-key-masked]'),
        statusPill: modal.querySelector('[data-api-key-status-pill]'),
        created: modal.querySelector('[data-api-key-created]'),
        lastUsed: modal.querySelector('[data-api-key-last-used]'),
        copy: modal.querySelector('[data-api-key-copy]'),
        regenerate: modal.querySelector('[data-api-key-regenerate]'),
        toggle: modal.querySelector('[data-api-key-toggle]'),
        feedback: modal.querySelector('[data-api-key-feedback]'),
        error: modal.querySelector('[data-api-key-error]')
    };

    function t(key) {
        return (bootstrap.i18n && bootstrap.i18n[key]) || key;
    }

    function manageUrl() {
        return modal.getAttribute('data-manage-url') || '/api-credentials/manage';
    }

    function csrfToken() {
        return modal.getAttribute('data-csrf-token') || '';
    }

    function cryptoReady() {
        return modal.getAttribute('data-crypto-ready') === '1';
    }

    function schemaReady() {
        return modal.getAttribute('data-schema-ready') === '1';
    }

    function setFeedback(message, isError) {
        if (!elements.feedback) {
            return;
        }
        elements.feedback.hidden = !message;
        elements.feedback.textContent = message || '';
        elements.feedback.classList.toggle('is-error', !!isError);
    }

    function setError(message) {
        if (!elements.error) {
            return;
        }
        elements.error.hidden = !message;
        elements.error.textContent = message || '';
    }

    function getStoredCredential() {
        if (state.ownerType === 'client') {
            return bootstrap.client || null;
        }
        if (state.ownerType === 'widget') {
            return (bootstrap.widgets && bootstrap.widgets[String(state.ownerId)]) || null;
        }
        return null;
    }

    function storeCredential(view) {
        if (state.ownerType === 'client') {
            bootstrap.client = view;
            return;
        }
        if (!bootstrap.widgets) {
            bootstrap.widgets = {};
        }
        bootstrap.widgets[String(state.ownerId)] = view;
    }

    function renderContext() {
        if (!elements.context) {
            return;
        }
        if (state.ownerType === 'widget') {
            elements.context.textContent = t('widget_label') + ': ' + state.ownerLabel + ' · ' + t('client_label') + ': ' + state.clientLabel;
            if (elements.title) {
                elements.title.textContent = t('widget_title');
            }
            if (elements.generate) {
                elements.generate.textContent = t('generate_widget');
            }
            if (elements.empty && elements.empty.querySelector('p')) {
                elements.empty.querySelector('p').textContent = t('none_widget');
            }
            return;
        }

        elements.context.textContent = t('client_label') + ': ' + state.ownerLabel;
        if (elements.title) {
            elements.title.textContent = t('client_title');
        }
        if (elements.generate) {
            elements.generate.textContent = t('generate_client');
        }
        if (elements.empty && elements.empty.querySelector('p')) {
            elements.empty.querySelector('p').textContent = t('none_client');
        }
    }

    function renderCredential(view) {
        state.credential = view || null;
        setError('');
        setFeedback('');
        renderContext();

        var exists = !!(view && view.exists);
        if (elements.empty) {
            elements.empty.hidden = exists;
        }
        if (elements.details) {
            elements.details.hidden = !exists;
        }
        if (!exists) {
            return;
        }

        if (elements.masked) {
            elements.masked.textContent = view.masked_key || '';
        }
        if (elements.created) {
            elements.created.textContent = view.created_label || '';
        }
        if (elements.lastUsed) {
            elements.lastUsed.textContent = view.last_used_label || '';
        }
        if (elements.statusPill) {
            elements.statusPill.textContent = view.status_label || (view.is_active ? t('status_active') : t('status_disabled'));
            elements.statusPill.classList.toggle('status-active', !!view.is_active);
            elements.statusPill.classList.toggle('status-disabled', !view.is_active);
        }
        if (elements.toggle) {
            elements.toggle.textContent = view.is_active ? t('disable') : t('enable');
        }
    }

    function openModal(trigger) {
        state.ownerType = trigger.getAttribute('data-owner-type') || '';
        state.ownerId = parseInt(trigger.getAttribute('data-owner-id') || '0', 10);
        state.ownerLabel = trigger.getAttribute('data-owner-label') || '';
        state.clientLabel = trigger.getAttribute('data-client-label') || '';

        if (!schemaReady()) {
            renderCredential({ exists: false });
            setError(t('schema_missing'));
            modal.hidden = false;
            return;
        }

        if (!cryptoReady()) {
            renderCredential({ exists: false });
            setError(t('crypto_missing'));
            modal.hidden = false;
            return;
        }

        renderCredential(getStoredCredential());
        modal.hidden = false;
    }

    function closeModal() {
        modal.hidden = true;
        setFeedback('');
        setError('');
    }

    function postAction(action) {
        var formData = new FormData();
        formData.append('csrf_token', csrfToken());
        formData.append('action', action);
        formData.append('owner_type', state.ownerType);
        formData.append('owner_id', String(state.ownerId));

        return fetch(manageUrl(), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(function (response) {
            return response.json().catch(function () {
                return { success: false, message: t('operation_failed') };
            }).then(function (data) {
                return { ok: response.ok, data: data };
            });
        });
    }

    function applyServerCredential(data) {
        if (!data || !data.credential) {
            return;
        }
        var view = data.credential;
        if (data.created_label) {
            view.created_label = data.created_label;
        }
        if (data.last_used_label) {
            view.last_used_label = data.last_used_label;
        }
        if (data.status_label) {
            view.status_label = data.status_label;
        }
        storeCredential(view);
        renderCredential(view);
    }

    function fallbackCopy(text) {
        var area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', 'readonly');
        area.style.position = 'fixed';
        area.style.left = '-9999px';
        document.body.appendChild(area);
        area.select();
        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (error) {
            ok = false;
        }
        document.body.removeChild(area);
        return ok;
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).then(function () {
                return true;
            }).catch(function () {
                return fallbackCopy(text);
            });
        }
        return Promise.resolve(fallbackCopy(text));
    }

    modal.querySelectorAll('[data-api-key-modal-close]').forEach(function (node) {
        node.addEventListener('click', closeModal);
    });

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-open-api-key-modal]');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        openModal(trigger);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    if (elements.generate) {
        elements.generate.addEventListener('click', function () {
            postAction('generate').then(function (result) {
                if (!result.ok || !result.data.success) {
                    setError((result.data && result.data.message) || t('operation_failed'));
                    return;
                }
                applyServerCredential(result.data);
                if (result.data.raw_key) {
                    copyText(result.data.raw_key).then(function (ok) {
                        setFeedback(ok ? t('copied') : t('copy_failed'), !ok);
                    });
                }
            }).catch(function () {
                setError(t('operation_failed'));
            });
        });
    }

    if (elements.copy) {
        elements.copy.addEventListener('click', function () {
            postAction('reveal').then(function (result) {
                if (!result.ok || !result.data.success || !result.data.raw_key) {
                    setError((result.data && result.data.message) || t('operation_failed'));
                    return;
                }
                return copyText(result.data.raw_key).then(function (ok) {
                    setFeedback(ok ? t('copied') : t('copy_failed'), !ok);
                });
            }).catch(function () {
                setError(t('operation_failed'));
            });
        });
    }

    if (elements.regenerate) {
        elements.regenerate.addEventListener('click', function () {
            if (!window.confirm(t('regenerate_confirm'))) {
                return;
            }
            postAction('regenerate').then(function (result) {
                if (!result.ok || !result.data.success) {
                    setError((result.data && result.data.message) || t('operation_failed'));
                    return;
                }
                applyServerCredential(result.data);
                if (result.data.raw_key) {
                    copyText(result.data.raw_key).then(function (ok) {
                        setFeedback(ok ? t('copied') : t('copy_failed'), !ok);
                    });
                }
            }).catch(function () {
                setError(t('operation_failed'));
            });
        });
    }

    if (elements.toggle) {
        elements.toggle.addEventListener('click', function () {
            var isActive = !!(state.credential && state.credential.is_active);
            if (isActive && !window.confirm(t('disable_confirm'))) {
                return;
            }
            postAction(isActive ? 'disable' : 'enable').then(function (result) {
                if (!result.ok || !result.data.success) {
                    setError((result.data && result.data.message) || t('operation_failed'));
                    return;
                }
                applyServerCredential(result.data);
                setFeedback(result.data.message || '');
            }).catch(function () {
                setError(t('operation_failed'));
            });
        });
    }
})();
