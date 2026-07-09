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
    var styleNames = ['style-1', 'style-2', 'style-3', 'style-3-large', 'style-4', 'style-6', 'style-7', 'style-7-extend', 'style-8', 'style-9-left-hover'];
    var isOpening = false;
    var currentStyle = container ? container.dataset.desktopStyle : 'style-1';
    var currentState = 'button';
    var parentViewportWidth = config.initialMode === 'mobile' ? 767 : (config.initialMode === 'desktop' ? 768 : null);
    var hoverTimer = null;
    var pageContext = {
        siteName: '',
        siteUrl: '',
        site: '',
        title: '',
        url: '/',
        urlFull: ''
    };
    var iconOnlyStyles = ['style-2', 'style-3', 'style-3-large', 'style-7'];
    var stateMinimums = {
        icon: { width: 110, height: 110 },
        button: { width: 260, height: 110 },
        hover: { width: 260, height: 110 },
        greeting: { width: 380, height: 300 },
        'greeting-phone': { width: 390, height: 340 }
    };
    var mobileStateMinimums = {
        icon: { width: 68, height: 68 },
        button: { width: 150, height: 72 },
        hover: { width: 150, height: 72 },
        greeting: { width: 320, height: 260 },
        'greeting-phone': { width: 336, height: 290 }
    };
    var mobileCollapsedIconStyles = ['style-2', 'style-3', 'style-3-large', 'style-7', 'style-7-extend', 'style-9-left-hover'];

    function getMeasureSelectors() {
        if (isGreetingVisible()) {
            return '.ctcw-widget, .ctcw-greeting, .ctcw-widget-popup';
        }
        if (container && container.classList.contains('is-hovering')) {
            return '.ctcw-widget, .ctcw-hover-box';
        }
        return '.ctcw-widget';
    }

    function getBoundsPadding() {
        return isMobile() ? 8 : 16;
    }

    function minimumForState(state) {
        var table = isMobile() ? mobileStateMinimums : stateMinimums;
        return table[state] || table.icon;
    }

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

    function normalizeWidgetStyle(style) {
        return style === 'style-5' ? 'style-8' : style;
    }

    function isIconOnlyStyle() {
        if (isMobile() && mobileCollapsedIconStyles.indexOf(currentStyle) !== -1) {
            return true;
        }

        return iconOnlyStyles.indexOf(currentStyle) !== -1;
    }

    function isCollapsedIconOnlyStyle() {
        return isIconOnlyStyle();
    }

    function getMobileViewportLimit() {
        return Math.max(280, (parentViewportWidth || window.innerWidth || 320) - 24);
    }

    function measureTriggerElement(el) {
        if (!el) {
            return { width: 0, height: 0 };
        }

        var rect = el.getBoundingClientRect();
        return {
            width: Math.ceil(Math.max(rect.width, el.scrollWidth, el.offsetWidth)),
            height: Math.ceil(Math.max(rect.height, el.scrollHeight, el.offsetHeight))
        };
    }

    function measureCollapsedTriggerBounds() {
        var padding = getBoundsPadding();
        var trigger = button;

        if (!trigger) {
            return minimumForState(resolveSizeState());
        }

        if (isGreetingVisible()) {
            return getVisibleWidgetBounds();
        }

        if (isCollapsedIconOnlyStyle()) {
            var iconRect = measureTriggerElement(trigger);
            return {
                width: Math.max(minimumForState('icon').width, iconRect.width + padding),
                height: Math.max(minimumForState('icon').height, iconRect.height + padding)
            };
        }

        var triggerSize = measureTriggerElement(trigger);
        var width = Math.max(minimumForState('button').width, triggerSize.width + padding);
        var height = Math.max(minimumForState('button').height, triggerSize.height + padding);
        var viewportLimit = isMobile() ? getMobileViewportLimit() : null;

        if (viewportLimit) {
            width = Math.min(width, viewportLimit);
        }

        return { width: width, height: height };
    }

    function renderCollapsedTrigger() {
        container.classList.remove('is-hovering');
        container.classList.toggle('ctcw-greeting-open', false);
        container.classList.toggle('ctcw-greeting-closed', !!greeting);

        if (greeting) {
            greeting.classList.remove('is-visible');
        }

        currentState = isCollapsedIconOnlyStyle() ? 'icon' : 'button';
    }

    function requestWidgetResize() {
        window.requestAnimationFrame(function () {
            sendActualWidgetSize();
            scheduleSizeReports();
        });
    }

    function resolveSizeState() {
        if (isGreetingVisible()) {
            if (config.greetingCapturePhone && greeting && greeting.classList.contains('has-capture')) {
                return 'greeting-phone';
            }
            return 'greeting';
        }
        if (currentState === 'hover') {
            return isIconOnlyStyle() ? 'icon' : 'button';
        }
        if (isIconOnlyStyle()) {
            return 'icon';
        }
        return 'button';
    }

    function isElementVisible(el) {
        if (!el) {
            return false;
        }
        var style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') {
            return false;
        }
        if ((el.classList.contains('ctcw-greeting') || el.classList.contains('ctcw-widget-popup'))
            && !el.classList.contains('is-visible')) {
            return false;
        }
        if (el.classList.contains('ctcw-hover-box')) {
            if (style.opacity === '0') {
                return false;
            }
        }
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function getVisibleWidgetBounds() {
        var elements = Array.from(document.querySelectorAll(getMeasureSelectors())).filter(isElementVisible);
        if (!elements.length) {
            var fallback = minimumForState(resolveSizeState());
            return { width: fallback.width, height: fallback.height };
        }

        var minX = Infinity;
        var minY = Infinity;
        var maxX = -Infinity;
        var maxY = -Infinity;
        var padding = getBoundsPadding();

        elements.forEach(function (el) {
            var rect = el.getBoundingClientRect();
            minX = Math.min(minX, rect.left);
            minY = Math.min(minY, rect.top);
            maxX = Math.max(maxX, rect.right);
            maxY = Math.max(maxY, rect.bottom);
        });

        return {
            width: Math.ceil(maxX - minX + padding),
            height: Math.ceil(maxY - minY + padding)
        };
    }

    function sendActualWidgetSize() {
        window.requestAnimationFrame(function () {
            var root = document.querySelector('.ctcw-widget-root');
            if (!root || root.classList.contains('is-hidden')) {
                return;
            }

            var state = resolveSizeState();
            var bounds = isGreetingVisible() ? getVisibleWidgetBounds() : measureCollapsedTriggerBounds();
            var minimum = minimumForState(state);
            var viewportLimit = isMobile() ? getMobileViewportLimit() : null;
            var width = Math.max(minimum.width, bounds.width);
            var height = Math.max(minimum.height, bounds.height);

            if (viewportLimit) {
                width = Math.min(width, viewportLimit);
            }

            window.parent.postMessage({
                type: 'ctcw:size',
                id: String(config.widgetId || window.CTCW_WIDGET_ID || ''),
                width: width,
                height: height,
                state: state
            }, '*');
        });
    }

    function scheduleSizeReports() {
        [0, 50, 150, 300, 600, 900].forEach(function (delay) {
            window.setTimeout(sendActualWidgetSize, delay);
        });
    }

    function closeGreetingDialog() {
        if (!greeting) {
            return;
        }

        showPhoneError('');
        if (phoneInput) {
            phoneInput.removeAttribute('aria-invalid');
        }
        if (greetingSuccess) {
            greetingSuccess.hidden = true;
        }
        resetSubmitButton(greetingSubmit);
        renderCollapsedTrigger();
        requestWidgetResize();
    }

    function applyResponsiveState() {
        var mobile = isMobile();
        var activeStyle = normalizeWidgetStyle(mobile ? container.dataset.mobileStyle : container.dataset.desktopStyle);
        currentStyle = activeStyle || 'style-1';

        document.documentElement.classList.toggle('ctcw-mobile', mobile);
        styleNames.forEach(function (style) {
            container.classList.remove(style);
        });
        container.classList.add(activeStyle || 'style-1');
        container.classList.toggle('is-hidden', mobile ? !config.showMobile : !config.showDesktop);
        if (mobile) {
            container.style.maxWidth = getMobileViewportLimit() + 'px';
        } else {
            container.style.removeProperty('max-width');
        }
        sendActualWidgetSize();
        scheduleSizeReports();
    }

    function capturedPhoneHasInvalidPlusPlacement(value) {
        var plusMatches = String(value || '').match(/\+/g);
        if (!plusMatches) {
            return false;
        }
        if (plusMatches.length > 1) {
            return true;
        }

        return String(value || '').indexOf('+') !== 0;
    }

    function validateCapturedPhoneNumber(rawValue) {
        var value = String(rawValue || '').trim();
        var messages = config.phoneValidation || {};
        var allowPlus = !!config.allowPhonePlusSymbol;

        if (!value) {
            return {
                valid: false,
                message: messages.empty || 'Enter your phone number.'
            };
        }

        if (!allowPlus && value.indexOf('+') !== -1) {
            return {
                valid: false,
                message: messages.withoutPlus || 'Enter numbers without the + symbol.'
            };
        }

        if (allowPlus && capturedPhoneHasInvalidPlusPlacement(value)) {
            return {
                valid: false,
                message: messages.invalid || 'Enter a valid phone number.'
            };
        }

        var pattern = allowPlus ? /^\+?[0-9\s().-]+$/ : /^[0-9\s().-]+$/;
        if (!pattern.test(value)) {
            return {
                valid: false,
                message: messages.invalid || 'Enter a valid phone number.'
            };
        }

        var digits = value.replace(/\D/g, '');

        if (digits.length < 8) {
            return {
                valid: false,
                message: messages.minDigits || 'Enter a valid phone number.'

            };
        }

        if (digits.length > 15) {
            return {
                valid: false,
                message: messages.invalid || 'Enter a valid phone number.'
            };
        }

        return {
            valid: true,
            normalizedDigits: digits,
            normalizedPhone: allowPlus ? '+' + digits : digits
        };
    }

    function setPhoneInputInvalid(message) {
        showPhoneError(message);
        if (phoneInput) {
            phoneInput.setAttribute('aria-invalid', 'true');
            phoneInput.focus();
        }
        sendActualWidgetSize();
        scheduleSizeReports();
    }

    function clearPhoneInputInvalid() {
        showPhoneError('');
        if (phoneInput) {
            phoneInput.removeAttribute('aria-invalid');
        }
    }

    function getTrustedParentOrigin() {
        try {
            if (!document.referrer) {
                return null;
            }
            return new URL(document.referrer).origin;
        } catch (error) {
            return null;
        }
    }

    function notifyPhoneSubmitSuccess() {
        var submitButtonId = config.phoneSubmitButtonId
            || (greetingSubmit && greetingSubmit.getAttribute('data-ctcw-submit-id'))
            || ('ctcw-phone-submit-' + String(config.widgetId || window.CTCW_WIDGET_ID || ''));
        var trustedOrigin = getTrustedParentOrigin();
        var payload = {
            type: 'ctcw:phone-submit-success',
            widgetId: config.widgetId || window.CTCW_WIDGET_ID || '',
            submitButtonId: submitButtonId
        };

        try {
            window.parent.postMessage(payload, trustedOrigin || '*');
        } catch (error) {
            window.parent.postMessage(payload, '*');
        }
    }

    function showPhoneError(message) {
        if (!phoneError) {
            return;
        }
        phoneError.textContent = message;
        phoneError.hidden = !message;
    }

    function isTrustedParentMessage(event) {
        if (!event || !event.data) {
            return false;
        }

        var trustedOrigin = getTrustedParentOrigin();
        if (trustedOrigin && event.origin !== trustedOrigin) {
            return false;
        }

        return true;
    }

    function updatePageContext(data) {
        if (!data) {
            return;
        }

        if (typeof data.siteName === 'string') {
            pageContext.siteName = data.siteName;
        }
        if (typeof data.siteUrl === 'string') {
            pageContext.siteUrl = data.siteUrl;
        }
        if (typeof data.site === 'string' && data.site !== '') {
            pageContext.site = data.site;
        }
        if (typeof data.title === 'string') {
            pageContext.title = data.title;
        }
        if (typeof data.url === 'string' && data.url !== '') {
            pageContext.url = data.url;
        }
        if (typeof data.urlFull === 'string' && data.urlFull !== '') {
            pageContext.urlFull = data.urlFull;
        } else if (typeof data.url === 'string' && data.url !== '' && /^https?:\/\//i.test(data.url)) {
            pageContext.urlFull = data.url;
        }
    }

    function hasPageContext() {
        return !!(
            pageContext.siteName
            || pageContext.siteUrl
            || pageContext.site
            || pageContext.title
            || pageContext.urlFull
        );
    }

    function getMessageContext() {
        if (hasPageContext()) {
            return {
                siteName: pageContext.siteName || config.websiteName || pageContext.site || config.site || '',
                siteUrl: pageContext.siteUrl || '',
                site: pageContext.site || config.site || '',
                title: pageContext.title || '',
                url: pageContext.url || '/',
                urlFull: pageContext.urlFull || ''
            };
        }

        return {
            siteName: config.websiteName || config.site || '',
            siteUrl: '',
            site: config.site || '',
            title: '',
            url: '/',
            urlFull: document.referrer || ''
        };
    }

    function resolvePrefilledMessage(template, context) {
        var replacements = {
            '{site_name}': context.siteName || context.site || '',
            '{site_url}': context.siteUrl || '',
            '{site}': context.site || '',
            '{title}': context.title || '',
            '{url}': context.url || '/',
            '{url_full}': context.urlFull || ''
        };

        return String(template || '').replace(
            /\{site_name\}|\{site_url\}|\{site\}|\{title\}|\{url_full\}|\{url\}/g,
            function (token) {
                return Object.prototype.hasOwnProperty.call(replacements, token)
                    ? replacements[token]
                    : token;
            }
        );
    }

    function getLeadPageContext() {
        var context = getMessageContext();

        return {
            url: context.urlFull || document.referrer || '',
            title: context.title || ''
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

    function resolveDestination() {
        if (!config.destinationResolveUrl || !config.publicKey) {
            return Promise.reject(new Error('Destination resolver unavailable'));
        }

        var leadPage = getLeadPageContext();

        return fetch(config.destinationResolveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                widget_id: config.widgetId,
                public_key: config.publicKey,
                source_url: leadPage.url
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.success || !data.full_number) {
                    throw new Error(data && data.message ? data.message : 'Unable to resolve destination');
                }

                return cleanDigits(data.full_number);
            });
    }

    function buildUrlWithPhone(phone) {
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

    function redirectWithResolvedDestination() {
        return resolveDestination()
            .then(function (phone) {
                redirectToWhatsapp(buildUrlWithPhone(phone));
            });
    }

    function handlePhoneCaptureSubmit(submitButton) {
        if (!config.online) {
            return;
        }

        var phone = phoneInput ? phoneInput.value.trim() : '';
        var forceMode = isForcePhoneCapture();
        var saveFailedMessage = (config.phoneValidation && config.phoneValidation.saveFailed)
            || 'We could not save your phone number. Please try again.';
        var redirectFailedMessage = (config.phoneValidation && config.phoneValidation.redirectFailed)
            || 'We could not connect you to WhatsApp. Please try again.';

        if (phone === '' && !forceMode && !config.greetingPhoneRequired) {
            clearPhoneInputInvalid();
            closeGreetingDialog();
            redirectWithResolvedDestination().catch(function () {
                setPhoneInputInvalid(redirectFailedMessage);
            });
            return;
        }

        var validation = validateCapturedPhoneNumber(phone);
        if (!validation.valid) {
            if (forceMode || config.greetingPhoneRequired || phone !== '') {
                setPhoneInputInvalid(validation.message);
                return;
            }
        }

        clearPhoneInputInvalid();

        var phoneToSave = validation.valid && validation.normalizedPhone
            ? validation.normalizedPhone
            : phone;

        if (!config.saveLeadUrl) {
            if (forceMode) {
                setPhoneInputInvalid(saveFailedMessage);
                return;
            }
            closeGreetingDialog();
            redirectWithResolvedDestination().catch(function () {
                setPhoneInputInvalid(redirectFailedMessage);
            });
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('is-loading');
        }

        if (!forceMode) {
            closeGreetingDialog();
        }

        saveLead(phoneToSave, '')
            .then(function () {
                notifyPhoneSubmitSuccess();
                if (forceMode && greetingSuccess) {
                    greetingSuccess.hidden = false;
                }

                return redirectWithResolvedDestination();
            })
            .catch(function (error) {
                var message = (error && error.message) ? error.message : saveFailedMessage;
                if (forceMode) {
                    resetSubmitButton(submitButton);
                    setPhoneInputInvalid(message);
                    return;
                }

                redirectWithResolvedDestination().catch(function () {
                    setPhoneInputInvalid(redirectFailedMessage);
                });
            });
    }

    function buildMessage() {
        return resolvePrefilledMessage(config.prefilledMessage, getMessageContext());
    }

    function appendTextParam(url, encodedMessage) {
        var joiner = url.indexOf('?') === -1 ? '?' : '&';
        return url + joiner + 'text=' + encodedMessage;
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
        container.classList.toggle('ctcw-greeting-open', true);
        container.classList.toggle('ctcw-greeting-closed', false);
        currentState = config.greetingCapturePhone && greeting.classList.contains('has-capture')
            ? 'greeting-phone'
            : 'greeting';
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
        if (phoneInput) {
            phoneInput.removeAttribute('aria-invalid');
        }
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

    function isClickOnlyGreeting() {
        return config.greetingOpenBehavior === 'click_only';
    }

    function shouldAutoOpenGreeting() {
        return !!(config.greetingEnabled && greeting && !isClickOnlyGreeting());
    }

    function openGreetingOnClick() {
        if (isForcePhoneCapture()) {
            showGreetingPhoneCapture();
            return;
        }

        revealGreeting();
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
                currentState = isIconOnlyStyle() ? 'icon' : 'button';
                sendActualWidgetSize();
            }, 400);
            return;
        }

        if (config.greetingEnabled && greeting && !isGreetingVisible()) {
            if (isClickOnlyGreeting()) {
                openGreetingOnClick();
                return;
            }
        }

        if (isClickOnlyGreeting() && isGreetingVisible()) {
            if (config.greetingCapturePhone && greeting.classList.contains('has-capture')) {
                return;
            }

            closeGreetingDialog();
            redirectWithResolvedDestination().catch(function () {
                currentState = isIconOnlyStyle() ? 'icon' : 'button';
                sendActualWidgetSize();
            });
            return;
        }

        if (isForcePhoneCapture()) {
            showGreetingPhoneCapture();
            return;
        }

        redirectWithResolvedDestination().catch(function () {
            currentState = isIconOnlyStyle() ? 'icon' : 'button';
            sendActualWidgetSize();
        });
    }

    if (shouldAutoOpenGreeting()) {
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
            clearPhoneInputInvalid();
            sendActualWidgetSize();
        });
    }

    function startHover() {
        if (isMobile() || isGreetingVisible()) {
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
        if (isMobile() || isGreetingVisible()) {
            return;
        }
        window.clearTimeout(hoverTimer);
        container.classList.remove('is-hovering');
        window.setTimeout(function () {
            if (!isGreetingVisible()) {
                currentState = isIconOnlyStyle() ? 'icon' : 'button';
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
        if (!isTrustedParentMessage(event)) {
            return;
        }

        if (event.data.type === 'ctcw:page-context') {
            parentViewportWidth = parseInt(event.data.width, 10) || parentViewportWidth;
            updatePageContext(event.data);
            applyResponsiveState();
            return;
        }

        if (event.data.type === 'ctcw:viewport') {
            parentViewportWidth = parseInt(event.data.width, 10) || parentViewportWidth;
            var legacyUrl = typeof event.data.url === 'string' ? event.data.url : '';
            var legacyPath = '/';
            if (legacyUrl !== '') {
                try {
                    legacyPath = new URL(legacyUrl).pathname || '/';
                } catch (error) {
                    legacyPath = '/';
                }
            }
            updatePageContext({
                title: event.data.title || '',
                urlFull: legacyUrl,
                url: legacyPath
            });
            applyResponsiveState();
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
