(function () {
    'use strict';

    var config = window.CTCW_WIDGET || {};
    var container = document.querySelector('.ctcw-widget-root');
    var button = document.querySelector('[data-widget-button]');
    var greeting = document.querySelector('[data-greeting]');
    var closeGreeting = document.querySelector('[data-close-greeting]');
    var greetingSubmit = document.querySelector('[data-greeting-submit]');
    var phoneInput = document.querySelector('[data-greeting-phone]');
    var phoneError = document.querySelector('[data-greeting-phone-error]');
    var greetingSuccess = document.querySelector('[data-greeting-success]');
    var styleNames = ['style-1', 'style-2', 'style-3', 'style-3-large', 'style-4', 'style-5', 'style-6', 'style-7', 'style-7-extend', 'style-8', 'style-9-left-hover'];
    var isOpening = false;
    var currentStyle = container ? container.dataset.desktopStyle : 'style-1';
    var currentState = 'normal';
    var parentViewportWidth = config.initialMode === 'mobile' ? 767 : (config.initialMode === 'desktop' ? 768 : null);
    var hoverTimer = null;
    var pageContext = {
        url: '',
        title: ''
    };
    var sizePadding = 24;

    if (!container || !button) {
        return;
    }

    function cleanDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function isMobile() {
        var width = parentViewportWidth || (window.screen && window.screen.width ? window.screen.width : window.innerWidth);
        return width <= 767;
    }

    function isGreetingVisible() {
        return !!(greeting && greeting.classList.contains('is-visible'));
    }

    function isForcePhoneCapture() {
        return !!(
            config.greetingForcePhoneCapture
            && config.greetingCapturePhone
            && config.greetingEnabled
            && greeting
            && phoneInput
        );
    }

    function resetSubmitButton(submitButton) {
        if (!submitButton) {
            return;
        }
        submitButton.disabled = false;
        submitButton.classList.remove('is-loading');
    }

    function sendActualWidgetSize() {
        window.requestAnimationFrame(function () {
            var root = document.querySelector('.ctcw-widget-root');
            if (!root || root.classList.contains('is-hidden')) {
                return;
            }

            var rects = [root.getBoundingClientRect()];
            var hoverBox = root.querySelector('.ctcw-hover-box');
            if (hoverBox) {
                var hoverStyle = window.getComputedStyle(hoverBox);
                if (hoverStyle.opacity !== '0' && hoverStyle.visibility !== 'hidden' && hoverStyle.display !== 'none') {
                    rects.push(hoverBox.getBoundingClientRect());
                }
            }

            var minX = Math.min.apply(null, rects.map(function (rect) { return rect.left; }));
            var minY = Math.min.apply(null, rects.map(function (rect) { return rect.top; }));
            var maxX = Math.max.apply(null, rects.map(function (rect) { return rect.right; }));
            var maxY = Math.max.apply(null, rects.map(function (rect) { return rect.bottom; }));
            var width = Math.ceil(maxX - minX + sizePadding);
            var height = Math.ceil(maxY - minY + sizePadding);

            window.parent.postMessage({
                type: 'ctcw:size',
                id: String(config.widgetId || window.CTCW_WIDGET_ID || ''),
                width: width,
                height: height,
                state: isGreetingVisible() ? 'greeting' : currentState
            }, '*');
        });
    }

    function scheduleSizeReports() {
        [50, 300, 800].forEach(function (delay) {
            window.setTimeout(sendActualWidgetSize, delay);
        });
    }

    function closeGreetingDialog() {
        if (!greeting) {
            return;
        }
        greeting.classList.remove('is-visible');
        showPhoneError('');
        if (greetingSuccess) {
            greetingSuccess.hidden = true;
        }
        resetSubmitButton(greetingSubmit);
        currentState = 'normal';
        sendActualWidgetSize();
        scheduleSizeReports();
    }

    function applyResponsiveState() {
        var mobile = isMobile();
        var activeStyle = mobile ? container.dataset.mobileStyle : container.dataset.desktopStyle;
        currentStyle = activeStyle || 'style-1';

        document.documentElement.classList.toggle('ctcw-mobile', mobile);
        styleNames.forEach(function (style) {
            container.classList.remove(style);
        });
        container.classList.add(activeStyle || 'style-1');
        container.classList.toggle('is-hidden', mobile ? !config.showMobile : !config.showDesktop);
        sendActualWidgetSize();
        scheduleSizeReports();
    }

    function cleanPhone(phone) {
        return String(phone || '').replace(/[^\d+]/g, '');
    }

    function isValidPhone(phone) {
        var cleaned = cleanPhone(phone);
        var digits = cleaned.replace(/\D/g, '');
        return digits.length >= 8 && digits.length <= 15;
    }

    function showPhoneError(message) {
        if (!phoneError) {
            return;
        }
        phoneError.textContent = message;
        phoneError.hidden = !message;
    }

    function updatePageContext(data) {
        if (!data) {
            return;
        }
        if (typeof data.url === 'string' && data.url !== '') {
            pageContext.url = data.url;
        }
        if (typeof data.title === 'string' && data.title !== '') {
            pageContext.title = data.title;
        }
    }

    function getLeadPageContext() {
        if (pageContext.url || pageContext.title) {
            return {
                url: pageContext.url,
                title: pageContext.title
            };
        }

        var url = document.referrer || '';
        var title = '';
        try {
            if (window.parent && window.parent !== window) {
                title = window.parent.document.title || '';
                if (window.parent.location && window.parent.location.href) {
                    url = window.parent.location.href;
                }
            }
        } catch (error) {
            // Cross-origin embed: parent page data arrives via postMessage.
        }

        return {
            url: url,
            title: title
        };
    }

    function redirectToWhatsapp(url) {
        openUrl(url);
    }

    function saveLead(phone, url) {
        var leadPage = getLeadPageContext();

        return fetch(config.saveLeadUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                widget_id: config.widgetId,
                public_key: config.publicKey,
                visitor_phone: phone,
                source_url: leadPage.url,
                page_title: leadPage.title,
                whatsapp_redirect_url: url,
                website: ''
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error(data && data.message ? data.message : 'Save failed');
                }
                return data;
            });
    }

    function handlePhoneCaptureSubmit(submitButton) {
        if (!config.online) {
            return;
        }

        var url = buildUrl();
        var phone = phoneInput ? phoneInput.value.trim() : '';
        var forceMode = isForcePhoneCapture();
        var invalidMessage = forceMode
            ? 'Please enter a valid phone number before continuing.'
            : 'Please enter a valid phone number.';

        if (phone === '' || !isValidPhone(phone)) {
            if (forceMode || config.greetingPhoneRequired || phone !== '') {
                showPhoneError(invalidMessage);
                sendActualWidgetSize();
                scheduleSizeReports();
                return;
            }
        }

        showPhoneError('');

        if (phone === '' || !isValidPhone(phone)) {
            closeGreetingDialog();
            redirectToWhatsapp(url);
            return;
        }

        if (!config.saveLeadUrl) {
            if (forceMode) {
                showPhoneError('We could not save your phone number. Please try again.');
                sendActualWidgetSize();
                scheduleSizeReports();
                return;
            }
            closeGreetingDialog();
            redirectToWhatsapp(url);
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('is-loading');
        }

        if (!forceMode) {
            closeGreetingDialog();
        }

        saveLead(phone, url)
            .then(function () {
                if (forceMode && greetingSuccess) {
                    greetingSuccess.hidden = false;
                }
                redirectToWhatsapp(url);
            })
            .catch(function () {
                if (forceMode) {
                    resetSubmitButton(submitButton);
                    showPhoneError('We could not save your phone number. Please try again.');
                    sendActualWidgetSize();
                    scheduleSizeReports();
                    return;
                }
                redirectToWhatsapp(url);
            });
    }

    function chooseNumber() {
        if (config.useRandomNumbers && Array.isArray(config.randomNumbers) && config.randomNumbers.length) {
            var validNumbers = config.randomNumbers.filter(function (item) {
                return cleanDigits(item.number).length >= 7;
            });
            if (validNumbers.length) {
                var selected = validNumbers[Math.floor(Math.random() * validNumbers.length)];
                return cleanDigits(selected.country_code) + cleanDigits(selected.number);
            }
        }

        return cleanDigits(config.countryCode) + cleanDigits(config.number);
    }

    function pageData() {
        var referrer = document.referrer || '';
        var cleanUrl = referrer ? referrer.split('#')[0].split('?')[0] : '';
        return {
            site: config.site || '',
            title: referrer || config.widgetName || '',
            url: cleanUrl || referrer,
            url_full: referrer
        };
    }

    function buildMessage() {
        var data = pageData();
        return String(config.prefilledMessage || '')
            .replaceAll('{site}', data.site)
            .replaceAll('{title}', data.title)
            .replaceAll('{url_full}', data.url_full)
            .replaceAll('{url}', data.url);
    }

    function appendTextParam(url, encodedMessage) {
        var joiner = url.indexOf('?') === -1 ? '?' : '&';
        return url + joiner + 'text=' + encodedMessage;
    }

    function buildUrl() {
        var phone = chooseNumber();
        var message = buildMessage();
        var encodedMessage = encodeURIComponent(message);
        var structure = isMobile() ? config.mobileUrlStructure : config.desktopUrlStructure;

        if (structure === 'web_whatsapp') {
            return 'https://web.whatsapp.com/send?phone=' + phone + '&text=' + encodedMessage;
        }

        if (structure === 'whatsapp_app') {
            return 'whatsapp://send?phone=' + phone + '&text=' + encodedMessage;
        }

        if (structure === 'custom' && config.customUrl) {
            var hasMessagePlaceholder = String(config.customUrl).indexOf('{message}') !== -1;
            var custom = String(config.customUrl)
                .replaceAll('{phone}', phone)
                .replaceAll('{message}', encodedMessage);
            return hasMessagePlaceholder ? custom : appendTextParam(custom, encodedMessage);
        }

        return 'https://wa.me/' + phone + '?text=' + encodedMessage;
    }

    function openUrl(url) {
        var openType = isMobile() ? config.mobileOpenLinkType : config.desktopOpenLinkType;
        if (openType === 'same_tab') {
            try {
                window.top.location.href = url;
            } catch (error) {
                window.location.href = url;
            }
            return;
        }

        window.open(url, '_blank', 'noopener,noreferrer');
    }

    function revealGreeting() {
        if (!greeting) {
            return;
        }
        currentState = 'greeting';
        window.requestAnimationFrame(function () {
            greeting.classList.add('is-visible');
            window.requestAnimationFrame(function () {
                sendActualWidgetSize();
                scheduleSizeReports();
            });
        });
    }

    function showGreetingPhoneCapture() {
        if (!greeting) {
            return;
        }
        if (greetingSuccess) {
            greetingSuccess.hidden = true;
        }
        showPhoneError('');
        resetSubmitButton(greetingSubmit);
        revealGreeting();
        window.setTimeout(function () {
            if (phoneInput) {
                phoneInput.focus();
            }
            sendActualWidgetSize();
            scheduleSizeReports();
        }, 60);
    }

    function handleWhatsAppClick(event) {
        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }

        if (isOpening) {
            return;
        }
        isOpening = true;
        window.setTimeout(function () {
            isOpening = false;
        }, 1200);

        if (!config.online) {
            currentState = 'animation';
            button.classList.add('is-shaking');
            sendActualWidgetSize();
            scheduleSizeReports();
            window.setTimeout(function () {
                button.classList.remove('is-shaking');
                currentState = 'normal';
                sendActualWidgetSize();
            }, 400);
            return;
        }

        if (isForcePhoneCapture()) {
            showGreetingPhoneCapture();
            return;
        }

        openUrl(buildUrl());
    }

    if (config.greetingEnabled && greeting) {
        window.setTimeout(
            revealGreeting,
            Math.max(0, Number(config.greetingDelaySeconds || 0)) * 1000
        );
    }

    if (closeGreeting) {
        closeGreeting.addEventListener('click', closeGreetingDialog);
    }

    if (greetingSubmit) {
        greetingSubmit.addEventListener('click', function (event) {
            event.preventDefault();
            handlePhoneCaptureSubmit(greetingSubmit);
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            showPhoneError('');
            sendActualWidgetSize();
        });
    }

    function startHover() {
        if (isGreetingVisible()) {
            return;
        }
        window.clearTimeout(hoverTimer);
        currentState = 'hover';
        hoverTimer = window.setTimeout(function () {
            container.classList.add('is-hovering');
            sendActualWidgetSize();
            scheduleSizeReports();
        }, 40);
    }

    function endHover() {
        if (isGreetingVisible()) {
            return;
        }
        window.clearTimeout(hoverTimer);
        container.classList.remove('is-hovering');
        window.setTimeout(function () {
            if (!isGreetingVisible()) {
                currentState = 'normal';
                sendActualWidgetSize();
                scheduleSizeReports();
            }
        }, 250);
    }

    container.addEventListener('mouseenter', startHover);
    container.addEventListener('mouseleave', endHover);
    button.addEventListener('mouseenter', startHover);
    button.addEventListener('mouseleave', endHover);
    button.addEventListener('click', handleWhatsAppClick, true);

    window.addEventListener('message', function (event) {
        if (!event.data) {
            return;
        }
        if (event.data.type === 'ctcw:viewport') {
            parentViewportWidth = parseInt(event.data.width, 10) || parentViewportWidth;
            updatePageContext(event.data);
            applyResponsiveState();
            return;
        }
    });

    if (typeof ResizeObserver !== 'undefined') {
        var resizeObserver = new ResizeObserver(function () {
            sendActualWidgetSize();
        });
        resizeObserver.observe(container);
    }

    applyResponsiveState();
    scheduleSizeReports();
    window.addEventListener('resize', applyResponsiveState);
    window.addEventListener('load', sendActualWidgetSize);
    document.addEventListener('DOMContentLoaded', function () {
        sendActualWidgetSize();
        scheduleSizeReports();
    });
})();
