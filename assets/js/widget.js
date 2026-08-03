(function () {
    'use strict';

    var config = window.CTCW_WIDGET || {};
    var container = document.querySelector('.ctcw-widget-root');
    var launcherButtons = Array.from(document.querySelectorAll('[data-widget-button]'));
    var button = launcherButtons[0] || null;
    var launcherStack = container ? container.querySelector('[data-launcher-stack]') : null;
    var channelShells = container ? Array.from(container.querySelectorAll('[data-channel-shell]')) : [];
    var greeting = document.querySelector('[data-greeting]');
    var closeGreeting = document.querySelector('[data-close-greeting]');
    var greetingSubmitButtons = Array.from(document.querySelectorAll('[data-greeting-submit]'));
    var greetingSubmit = greetingSubmitButtons[0] || null;
    var greetingCta = document.querySelector('[data-greeting-cta]');
    var phoneInput = document.querySelector('[data-greeting-phone]');
    var phoneError = document.querySelector('[data-greeting-phone-error]');
    var greetingSuccess = document.querySelector('[data-greeting-success]');
    var channelUnavailable = container ? container.querySelector('[data-channel-unavailable]') : null;
    var channelUnavailableText = container ? container.querySelector('[data-channel-unavailable-text]') : null;
    var styleNames = [
        'style-1', 'style-2', 'style-3', 'style-3-large', 'style-4', 'style-6',
        'style-7', 'style-7-extend', 'style-8', 'style-9-left-hover',
        'tg-compact', 'tg-icon', 'tg-pill', 'tg-reveal'
    ];
    var telegramStyleNames = ['tg-compact', 'tg-icon', 'tg-pill', 'tg-reveal'];
    var isOpening = false;
    var isSubmittingLead = false;
    var currentStyle = container ? container.dataset.desktopStyle : 'style-1';
    var currentState = 'button';
    var selectedChannel = String(config.defaultChannel || 'whatsapp');
    var activeLauncherButton = button;
    var parentViewportWidth = config.initialMode === 'mobile' ? 767 : (config.initialMode === 'desktop' ? 768 : null);
    var parentViewportHeight = null;
    var parentAvailableWidth = null;
    var parentAvailableHeight = null;
    var hoverTimer = null;
    var sizeReportFrame = null;
    var lastPostedSize = { width: 0, height: 0, state: '' };
    // Covers focus rings, translateY(-2px) hover, and subpixel anti-alias edges.
    var VISUAL_EDGE_ALLOWANCE = 6;
    var pageContext = {
        siteName: '',
        siteUrl: '',
        site: '',
        title: '',
        url: '/',
        urlFull: ''
    };
    var iconOnlyStyles = ['style-2', 'style-3', 'style-3-large', 'style-7', 'tg-icon'];
    var stateMinimums = {
        icon: { width: 72, height: 72 },
        button: { width: 72, height: 72 },
        hover: { width: 72, height: 72 },
        greeting: { width: 300, height: 160 },
        'greeting-phone': { width: 300, height: 180 },
        'greeting-phone-consent': { width: 300, height: 200 }
    };
    var mobileStateMinimums = {
        icon: { width: 64, height: 64 },
        button: { width: 64, height: 64 },
        hover: { width: 64, height: 64 },
        greeting: { width: 280, height: 150 },
        'greeting-phone': { width: 280, height: 170 },
        'greeting-phone-consent': { width: 280, height: 190 }
    };
    var mobileCollapsedIconStyles = ['style-2', 'style-3', 'style-3-large', 'style-7', 'style-7-extend', 'tg-icon'];

    function getMeasureSelectors() {
        return [
            '.ctcw-greeting.is-visible',
            '.ctcw-launcher-stack',
            '.ctcw-channel-shell',
            '.ctcw-widget',
            '.ctcw-channel-unavailable:not([hidden])',
            '.ctcw-telegram-fallback:not([hidden])'
        ].join(', ');
    }

    function getBoundsPadding() {
        return VISUAL_EDGE_ALLOWANCE + (isMobile() ? 2 : 4);
    }

    function minimumForState(state) {
        var table = isMobile() ? mobileStateMinimums : stateMinimums;
        return table[state] || table.icon;
    }

    if (!container || launcherButtons.length === 0) {
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
        var buttons = submitButton ? [submitButton] : greetingSubmitButtons;
        buttons.forEach(function (button) {
            if (!button) {
                return;
            }
            button.disabled = false;
            button.classList.remove('is-loading');
        });
        if (!submitButton) {
            isSubmittingLead = false;
        }
    }

    function setSubmitButtonsLoading(isLoading) {
        greetingSubmitButtons.forEach(function (button) {
            if (!button) {
                return;
            }
            button.disabled = !!isLoading;
            button.classList.toggle('is-loading', !!isLoading);
        });
    }

    function normalizeWidgetStyle(style) {
        return style === 'style-5' ? 'style-8' : style;
    }

    function normalizeTelegramStyle(style) {
        var normalized = normalizeWidgetStyle(String(style || '').trim());
        if (normalized === 'reveal_label_hover') {
            return 'tg-reveal';
        }
        if (telegramStyleNames.indexOf(normalized) !== -1) {
            return normalized;
        }
        if (['style-2', 'style-3', 'style-3-large', 'style-7'].indexOf(normalized) !== -1) {
            return 'tg-icon';
        }
        if (['style-1', 'style-6', 'style-8'].indexOf(normalized) !== -1) {
            return 'tg-pill';
        }
        if (['style-7-extend', 'style-9-left-hover'].indexOf(normalized) !== -1) {
            return 'tg-reveal';
        }
        // style-4 (old default), empty, unknown → compact
        return 'tg-compact';
    }

    function isRevealLabelStyle(shell) {
        var style = styleForShell(shell || activeChannelShell());
        return style === 'tg-reveal' || style === 'style-9-left-hover';
    }

    function shellForElement(el) {
        if (!el || !el.closest) {
            return null;
        }
        return el.closest('[data-channel-shell]');
    }

    function activeChannelShell() {
        if (activeLauncherButton) {
            var shell = shellForElement(activeLauncherButton);
            if (shell) {
                return shell;
            }
        }
        return channelShells[0] || null;
    }

    function styleForShell(shell) {
        if (!shell) {
            return currentStyle || 'style-1';
        }
        return shell.dataset.activeStyle
            || (isMobile() ? shell.dataset.mobileStyle : shell.dataset.desktopStyle)
            || currentStyle
            || 'style-1';
    }

    function isStyle9LeftHover(shell) {
        var target = shell || activeChannelShell();
        return styleForShell(target) === 'style-9-left-hover';
    }

    function isStyle9MobileExpanded(shell) {
        var target = shell || activeChannelShell();
        return isMobile()
            && isStyle9LeftHover(target)
            && target
            && (target.classList.contains('is-style-9-mobile-expanded')
                || target.classList.contains('is-hovering'));
    }

    function isStyle9MobileCollapsed(shell) {
        var target = shell || activeChannelShell();
        return isMobile() && isStyle9LeftHover(target) && !isStyle9MobileExpanded(target);
    }

    function updateViewportCssVars() {
        var viewportWidth = parentAvailableWidth
            || parentViewportWidth
            || window.innerWidth
            || 320;
        var viewportHeight = parentAvailableHeight
            || parentViewportHeight
            || window.innerHeight
            || 640;
        var launcherReserve = 72;
        if (launcherStack) {
            var stackRect = launcherStack.getBoundingClientRect();
            if (stackRect.height > 0) {
                launcherReserve = Math.ceil(stackRect.height + 16);
            } else if (launcherButtons.length > 1) {
                launcherReserve = 72 * launcherButtons.length + 10 * (launcherButtons.length - 1);
            }
        }

        document.documentElement.style.setProperty('--ctcw-viewport-width', viewportWidth + 'px');
        document.documentElement.style.setProperty('--ctcw-available-width', viewportWidth + 'px');
        document.documentElement.style.setProperty('--ctcw-available-height', viewportHeight + 'px');
        document.documentElement.style.setProperty('--ctcw-launchers-reserve', launcherReserve + 'px');

        if (greeting) {
            var needsScroll = greeting.classList.contains('has-consent');
            if (greeting.classList.contains('is-visible')) {
                var form = greeting.querySelector('.ctcw-greeting-form');
                if (form && form.scrollHeight > form.clientHeight + 1) {
                    needsScroll = true;
                }
            }
            greeting.classList.toggle('is-scrollable', needsScroll);
        }
    }

    function postSizeToParent(width, height, state) {
        var trustedOrigin = getTrustedParentOrigin();
        var payload = {
            type: 'ctcw:size',
            id: String(config.widgetId || window.CTCW_WIDGET_ID || ''),
            width: width,
            height: height,
            state: state
        };

        try {
            window.parent.postMessage(payload, trustedOrigin || '*');
        } catch (error) {
            try {
                window.parent.postMessage(payload, '*');
            } catch (ignored) {
                // Fail safely: keep local UI usable even if messaging is blocked.
            }
        }
    }

    function getVisibleWidgetBounds() {
        var elements = Array.from(document.querySelectorAll(getMeasureSelectors())).filter(isElementVisible);
        if (!elements.length && container) {
            var rootRect = container.getBoundingClientRect();
            if (rootRect.width > 0 && rootRect.height > 0) {
                return {
                    width: Math.ceil(rootRect.width),
                    height: Math.ceil(rootRect.height)
                };
            }
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

        if (!isFinite(minX) || !isFinite(minY) || !isFinite(maxX) || !isFinite(maxY)) {
            var emptyFallback = minimumForState(resolveSizeState());
            return { width: emptyFallback.width, height: emptyFallback.height };
        }

        return {
            width: Math.ceil(maxX - minX + padding),
            height: Math.ceil(maxY - minY + padding)
        };
    }

    function sendActualWidgetSize() {
        if (sizeReportFrame) {
            window.cancelAnimationFrame(sizeReportFrame);
        }

        sizeReportFrame = window.requestAnimationFrame(function () {
            sizeReportFrame = null;
            var root = document.querySelector('.ctcw-widget-root');
            if (!root || root.classList.contains('is-hidden')) {
                return;
            }

            updateViewportCssVars();

            var state = resolveSizeState();
            var bounds = getVisibleWidgetBounds();
            var minimum = minimumForState(state);
            var availableWidth = parentAvailableWidth
                || (isMobile() ? getMobileViewportLimit() : null)
                || window.innerWidth;
            var availableHeight = parentAvailableHeight || parentViewportHeight || window.innerHeight;
            var width = Math.max(minimum.width, bounds.width);
            var height = Math.max(minimum.height, bounds.height);

            if (availableWidth && isFinite(availableWidth)) {
                width = Math.min(width, Math.max(minimum.width, Math.ceil(availableWidth)));
            }
            if (availableHeight && isFinite(availableHeight)) {
                height = Math.min(height, Math.max(minimum.height, Math.ceil(availableHeight)));
            }

            width = Math.ceil(width);
            height = Math.ceil(height);

            if (
                lastPostedSize.width === width
                && lastPostedSize.height === height
                && lastPostedSize.state === state
            ) {
                return;
            }

            lastPostedSize = { width: width, height: height, state: state };
            postSizeToParent(width, height, state);
        });
    }

    function scheduleSizeReports() {
        [0, 80, 200, 450].forEach(function (delay) {
            window.setTimeout(sendActualWidgetSize, delay);
        });
    }

    function collapseStyle9Mobile(shell) {
        var targets = shell ? [shell] : channelShells;
        targets.forEach(function (target) {
            target.classList.remove('is-style-9-mobile-expanded');
            if (isMobile() && isStyle9LeftHover(target)) {
                target.classList.remove('is-hovering');
            }
        });
        if (!container.querySelector('[data-channel-shell].is-hovering')) {
            container.classList.remove('is-hovering');
        }
    }

    function expandStyle9Mobile(shell) {
        var target = shell || activeChannelShell();
        if (!isMobile() || !isStyle9LeftHover(target) || !target) {
            return;
        }
        target.classList.add('is-style-9-mobile-expanded');
        target.classList.add('is-hovering');
        container.classList.add('is-hovering');
        currentState = 'hover';
        requestWidgetResize();
    }

    function isIconOnlyStyle(shell) {
        var style = styleForShell(shell || activeChannelShell());
        if (isMobile() && mobileCollapsedIconStyles.indexOf(style) !== -1) {
            return true;
        }

        return iconOnlyStyles.indexOf(style) !== -1;
    }

    function isCollapsedIconOnlyStyle(shell) {
        if (isStyle9MobileCollapsed(shell)) {
            return true;
        }

        return isIconOnlyStyle(shell);
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
        var trigger = launcherStack || button;

        if (!trigger) {
            return minimumForState(resolveSizeState());
        }

        if (isGreetingVisible()) {
            return getVisibleWidgetBounds();
        }

        if (isCollapsedIconOnlyStyle() && launcherButtons.length === 1) {
            var iconRect = measureTriggerElement(button);
            return {
                width: Math.max(minimumForState('icon').width, iconRect.width + padding),
                height: Math.max(minimumForState('icon').height, iconRect.height + padding)
            };
        }

        var triggerSize = measureTriggerElement(trigger);
        var minWidth = launcherButtons.length > 1
            ? Math.max(minimumForState('button').width, 110)
            : minimumForState('button').width;
        var width = Math.max(minWidth, triggerSize.width + padding);
        var height = Math.max(minimumForState('button').height, triggerSize.height + padding);
        var viewportLimit = isMobile() ? getMobileViewportLimit() : null;

        if (viewportLimit) {
            width = Math.min(width, viewportLimit);
        }

        return { width: width, height: height };
    }

    function renderCollapsedTrigger() {
        container.classList.remove('is-hovering');
        channelShells.forEach(function (shell) {
            shell.classList.remove('is-hovering');
            shell.classList.remove('is-style-9-mobile-expanded');
        });
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
                if (greeting.classList.contains('has-consent') || greeting.querySelector('.ctcw-consent-text')) {
                    return 'greeting-phone-consent';
                }
                return 'greeting-phone';
            }
            return 'greeting';
        }
        if (currentState === 'hover') {
            return isIconOnlyStyle() && !isStyle9MobileExpanded() ? 'icon' : 'button';
        }
        if (isStyle9MobileExpanded()) {
            return 'hover';
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

    function closeGreetingDialog() {
        if (!greeting) {
            return;
        }

        showPhoneError('');
        hideChannelUnavailable();
        hideTelegramFallback();
        if (phoneInput) {
            phoneInput.removeAttribute('aria-invalid');
        }
        if (greetingSuccess) {
            greetingSuccess.hidden = true;
        }
        isSubmittingLead = false;
        resetSubmitButton();
        launcherButtons.forEach(function (btn) {
            btn.classList.remove('is-dialog-open');
            btn.setAttribute('aria-expanded', 'false');
        });
        if (container) {
            container.removeAttribute('data-active-channel');
        }
        renderCollapsedTrigger();
        requestWidgetResize();
    }

    function applyResponsiveState() {
        var mobile = isMobile();

        document.documentElement.classList.toggle('ctcw-mobile', mobile);
        styleNames.forEach(function (style) {
            container.classList.remove(style);
        });

        if (channelShells.length) {
            channelShells.forEach(function (shell) {
                var channel = shell.getAttribute('data-channel-shell') || 'whatsapp';
                var desktop = shell.dataset.desktopStyle
                    || (channel === 'telegram' ? container.dataset.telegramDesktopStyle : container.dataset.desktopStyle)
                    || 'style-1';
                var mobileStyle = shell.dataset.mobileStyle
                    || (channel === 'telegram' ? container.dataset.telegramMobileStyle : container.dataset.mobileStyle)
                    || desktop;
                var activeStyle = channel === 'telegram'
                    ? normalizeTelegramStyle(mobile ? mobileStyle : desktop)
                    : normalizeWidgetStyle(mobile ? mobileStyle : desktop);

                styleNames.forEach(function (style) {
                    shell.classList.remove(style);
                });
                shell.classList.add(activeStyle || (channel === 'telegram' ? 'tg-compact' : 'style-1'));
                shell.dataset.activeStyle = activeStyle || (channel === 'telegram' ? 'tg-compact' : 'style-1');
            });
            currentStyle = styleForShell(activeChannelShell());
        } else {
            var legacyStyle = normalizeWidgetStyle(mobile ? container.dataset.mobileStyle : container.dataset.desktopStyle);
            currentStyle = legacyStyle || 'style-1';
            container.classList.add(currentStyle);
        }

        container.classList.toggle('is-hidden', mobile ? !config.showMobile : !config.showDesktop);
        updateViewportCssVars();
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

    var savedLeadId = null;
    var telegramFallbackState = {
        url: '',
        username: ''
    };
    var channelPicker = container.querySelector('[data-channel-picker]');
    var telegramFallback = container.querySelector('[data-telegram-fallback]');
    var telegramFallbackText = container.querySelector('[data-telegram-fallback-text]');
    var telegramCopyBtn = container.querySelector('[data-telegram-copy]');
    var channelMode = String(config.channelMode || 'whatsapp_only');
    var enabledChannels = Array.isArray(config.enabledChannels) ? config.enabledChannels : ['whatsapp'];
    var readyChannels = Array.isArray(config.readyChannels) && config.readyChannels.length
        ? config.readyChannels
        : enabledChannels.slice();
    var channelLabels = config.channelLabels || {};
    var widgetI18n = config.i18n || {};
    selectedChannel = normalizeChannel(config.defaultChannel || readyChannels[0] || 'whatsapp');

    function normalizeChannel(channel) {
        return channel === 'telegram' ? 'telegram' : 'whatsapp';
    }

    function channelCopy(channel, key, fallback) {
        var labels = channelLabels[normalizeChannel(channel)] || {};
        if (labels[key]) {
            return labels[key];
        }
        return fallback;
    }

    function applyChannelCopy(channel) {
        var ch = normalizeChannel(channel);
        selectedChannel = ch;
        if (container) {
            container.setAttribute('data-active-channel', ch);
        }
        if (greeting) {
            greeting.setAttribute('data-active-channel', ch);
        }
        var continueLabel = channelCopy(ch, 'continue', ch === 'telegram'
            ? (widgetI18n.continueTelegram || 'Continue on Telegram')
            : (widgetI18n.continueWhatsApp || 'Continue on WhatsApp'));
        greetingSubmitButtons.forEach(function (button) {
            if (!button) {
                return;
            }
            button.setAttribute('aria-label', continueLabel);
            if (button.hasAttribute('data-greeting-cta') || button.classList.contains('ctcw-greeting-cta')) {
                button.textContent = continueLabel;
            }
        });
        if (greetingCta && !greetingCta.hasAttribute('data-greeting-submit')) {
            greetingCta.textContent = continueLabel;
            greetingCta.setAttribute('aria-label', continueLabel);
        }
        if (greetingSuccess) {
            greetingSuccess.textContent = channelCopy(ch, 'success', ch === 'telegram'
                ? (widgetI18n.redirectingTelegram || 'Opening Telegram...')
                : (widgetI18n.redirectingWhatsApp || 'Opening WhatsApp...'));
        }
    }

    function hideChannelUnavailable() {
        if (channelUnavailable) {
            channelUnavailable.hidden = true;
        }
        if (channelUnavailableText) {
            channelUnavailableText.textContent = '';
        }
        var form = greeting ? greeting.querySelector('[data-greeting-form]') : null;
        if (form) {
            form.hidden = false;
        }
    }

    function showChannelUnavailable(message) {
        var form = greeting ? greeting.querySelector('[data-greeting-form]') : null;
        if (form) {
            form.hidden = true;
        }
        if (channelPicker) {
            channelPicker.hidden = true;
        }
        if (telegramFallback) {
            telegramFallback.hidden = true;
        }
        if (channelUnavailableText) {
            channelUnavailableText.textContent = message;
        }
        if (channelUnavailable) {
            channelUnavailable.hidden = false;
        }
        revealGreeting();
        sendActualWidgetSize();
        scheduleSizeReports();
    }

    function unavailableMessageFor(channel, serverMessage, errorCode) {
        var ch = normalizeChannel(channel);
        var code = String(errorCode || '');
        var msg = String(serverMessage || '');
        if (code === 'no_active_destination' || /no active .*destination/i.test(msg)) {
            return channelCopy(ch, 'noDestination', ch === 'telegram'
                ? (widgetI18n.noTelegramDestination || 'No active Telegram destination is configured.')
                : (widgetI18n.noWhatsAppDestination || 'No active WhatsApp destination is configured.'));
        }
        if (code === 'channel_disabled' || code === 'channel_unavailable' || /unavailable|disabled|offline|not available/i.test(msg)) {
            return channelCopy(ch, 'unavailable', ch === 'telegram'
                ? (widgetI18n.telegramUnavailable || 'Telegram is currently unavailable.')
                : (widgetI18n.whatsappUnavailable || 'WhatsApp is currently unavailable.'));
        }
        return widgetI18n.unableToContinue || 'Unable to continue right now.';
    }

    function isMultiChannel() {
        return launcherButtons.length > 1 || (channelMode === 'both' && readyChannels.length > 1);
    }

    function isTelegramOnly() {
        return channelMode === 'telegram_only'
            || (readyChannels.length === 1 && readyChannels[0] === 'telegram')
            || (enabledChannels.length === 1 && enabledChannels[0] === 'telegram');
    }

    function showChannelPicker() {
        // Legacy fallback if an old embed still renders a picker.
        if (!channelPicker) {
            return continueWithChannel(selectedChannel || (isTelegramOnly() ? 'telegram' : 'whatsapp'));
        }
        if (greeting && greeting.querySelector('.ctcw-greeting-form')) {
            greeting.querySelector('.ctcw-greeting-form').hidden = true;
        }
        if (telegramFallback) {
            telegramFallback.hidden = true;
        }
        channelPicker.hidden = false;
        currentState = 'channel-picker';
        revealGreeting();
        sendActualWidgetSize();
        scheduleSizeReports();
    }

    function hideChannelPicker() {
        if (channelPicker) {
            channelPicker.hidden = true;
        }
    }

    function hideTelegramFallback() {
        if (telegramFallback) {
            telegramFallback.hidden = true;
        }
    }

    function showTelegramFallback(result) {
        telegramFallbackState.url = result.redirect_url || '';
        telegramFallbackState.username = result.fallback && result.fallback.username
            ? result.fallback.username
            : '';
        if (greeting && greeting.querySelector('.ctcw-greeting-form')) {
            greeting.querySelector('.ctcw-greeting-form').hidden = true;
        }
        hideChannelPicker();
        hideChannelUnavailable();
        if (telegramFallback) {
            telegramFallback.hidden = false;
            if (telegramFallbackText) {
                telegramFallbackText.textContent = telegramFallbackState.username
                    ? (widgetI18n.copyUsername || 'Copy Telegram Username')
                    : (widgetI18n.redirectingTelegram || widgetI18n.continueTelegram || 'Opening Telegram...');
            }
            if (telegramCopyBtn) {
                telegramCopyBtn.hidden = !telegramFallbackState.username;
            }
        }
        currentState = 'telegram-fallback';
        revealGreeting();
        sendActualWidgetSize();
        scheduleSizeReports();
    }

    function saveLead(phone, url, channel) {
        var leadPage = getLeadPageContext();
        var payload = {
            widget_id: config.widgetId,
            public_key: config.publicKey,
            visitor_phone: phone,
            source_url: leadPage.url,
            page_title: leadPage.title,
            whatsapp_redirect_url: url || '',
            website: '',
            channel: normalizeChannel(channel || selectedChannel || 'whatsapp')
        };
        if (savedLeadId) {
            payload.lead_id = savedLeadId;
        }

        return fetch(config.saveLeadUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error(data && data.message ? data.message : 'Save failed');
                }
                if (data.lead_id) {
                    savedLeadId = data.lead_id;
                }
                return data;
            });
    }

    function resolveDestination(channel) {
        if (!config.destinationResolveUrl || !config.publicKey) {
            return Promise.reject(new Error('Destination resolver unavailable'));
        }

        var leadPage = getLeadPageContext();
        var payload = {
            widget_id: config.widgetId,
            public_key: config.publicKey,
            source_url: leadPage.url,
            channel: normalizeChannel(channel || selectedChannel || 'whatsapp')
        };
        if (savedLeadId) {
            payload.lead_id = savedLeadId;
        }

        return fetch(config.destinationResolveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data || {} };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    var error = new Error(result.data.message || 'Unable to resolve destination');
                    error.channel = normalizeChannel(channel || selectedChannel || 'whatsapp');
                    error.serverMessage = result.data.message || '';
                    error.errorCode = result.data.error || '';
                    throw error;
                }
                return result.data;
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

    function continueWithChannel(channel) {
        hideChannelPicker();
        hideChannelUnavailable();
        var ch = normalizeChannel(channel);
        selectedChannel = ch;
        applyChannelCopy(ch);

        if (ch === 'telegram') {
            return resolveDestination('telegram')
                .then(function (result) {
                    if (!result.redirect_url) {
                        showChannelUnavailable(unavailableMessageFor('telegram', 'Telegram is currently unavailable'));
                        return;
                    }
                    openUrl(result.redirect_url);
                    if (result.fallback && result.fallback.type === 'copy_username') {
                        showTelegramFallback(result);
                    }
                })
                .catch(function (error) {
                    showChannelUnavailable(unavailableMessageFor(
                        'telegram',
                        error && (error.serverMessage || error.message),
                        error && error.errorCode
                    ));
                });
        }

        return resolveDestination('whatsapp')
            .then(function (result) {
                var phone = cleanDigits(result.full_number || '');
                if (!phone) {
                    showChannelUnavailable(unavailableMessageFor('whatsapp', 'No active destination', 'no_active_destination'));
                    return;
                }
                redirectToWhatsapp(buildUrlWithPhone(phone));
            })
            .catch(function (error) {
                showChannelUnavailable(unavailableMessageFor(
                    'whatsapp',
                    error && (error.serverMessage || error.message),
                    error && error.errorCode
                ));
            });
    }

    function afterLeadSavedContinue() {
        // Channel is known from the launcher the visitor clicked.
        return continueWithChannel(selectedChannel || (isTelegramOnly() ? 'telegram' : 'whatsapp'));
    }

    function redirectWithResolvedDestination() {
        return afterLeadSavedContinue();
    }

    function handlePhoneCaptureSubmit(submitButton) {
        if (!config.online || isSubmittingLead) {
            return;
        }

        var phone = phoneInput ? phoneInput.value.trim() : '';
        var forceMode = isForcePhoneCapture();
        var saveFailedMessage = (config.phoneValidation && config.phoneValidation.saveFailed)
            || 'We could not save your phone number. Please try again.';
        var redirectFailedMessage = unavailableMessageFor(
            selectedChannel,
            selectedChannel === 'telegram'
                ? (widgetI18n.telegramUnavailable || 'Telegram is currently unavailable.')
                : (widgetI18n.whatsappUnavailable || 'WhatsApp is currently unavailable.')
        );

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

        isSubmittingLead = true;
        setSubmitButtonsLoading(true);

        if (!forceMode) {
            closeGreetingDialog();
        }

        saveLead(phoneToSave, '', selectedChannel)
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
                    isSubmittingLead = false;
                    resetSubmitButton();
                    setPhoneInputInvalid(message);
                    return;
                }

                redirectWithResolvedDestination().catch(function () {
                    isSubmittingLead = false;
                    resetSubmitButton();
                    showChannelUnavailable(redirectFailedMessage);
                });
            });
    }

    function submitLeadForActiveChannel(submitButton) {
        return handlePhoneCaptureSubmit(submitButton || greetingSubmit);
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
        isSubmittingLead = false;
        resetSubmitButton();
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

    function handleLauncherClick(event) {
        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }

        if (isOpening || isSubmittingLead) {
            return;
        }

        var clickedButton = event.currentTarget;
        var channel = normalizeChannel(clickedButton.getAttribute('data-channel') || selectedChannel || 'whatsapp');
        selectedChannel = channel;
        activeLauncherButton = clickedButton;
        currentStyle = styleForShell(shellForElement(clickedButton));
        applyChannelCopy(channel);
        hideChannelUnavailable();
        hideTelegramFallback();
        hideChannelPicker();

        launcherButtons.forEach(function (btn) {
            var isActive = btn === clickedButton;
            btn.classList.toggle('is-dialog-open', isActive);
            btn.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        });

        var clickedShell = shellForElement(clickedButton);
        if (isStyle9MobileCollapsed(clickedShell)) {
            expandStyle9Mobile(clickedShell);
            return;
        }

        isOpening = true;
        window.setTimeout(function () {
            isOpening = false;
        }, 1200);

        if (!config.online) {
            currentState = 'animation';
            clickedButton.classList.add('is-shaking');
            sendActualWidgetSize();
            scheduleSizeReports();
            window.setTimeout(function () {
                clickedButton.classList.remove('is-shaking');
                currentState = isIconOnlyStyle() ? 'icon' : 'button';
                sendActualWidgetSize();
            }, 400);
            return;
        }

        if (readyChannels.indexOf(channel) === -1) {
            showChannelUnavailable(unavailableMessageFor(channel, 'No active destination'));
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

        // Greeting already open from another launcher — replace with this channel's flow.
        if (config.greetingEnabled && greeting && isGreetingVisible() && config.greetingCapturePhone) {
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
            function () {
                applyChannelCopy(selectedChannel);
                revealGreeting();
            },
            Math.max(0, Number(config.greetingDelaySeconds || 0)) * 1000
        );
    }

    if (closeGreeting) {
        closeGreeting.addEventListener('click', closeGreetingDialog);
    }

    var greetingForm = greeting ? greeting.querySelector('form[data-greeting-form]') : null;
    if (greetingForm) {
        greetingForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitLeadForActiveChannel(greetingSubmit);
        });
    } else {
        greetingSubmitButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                submitLeadForActiveChannel(button);
            });
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            clearPhoneInputInvalid();
            sendActualWidgetSize();
        });
    }

    container.querySelectorAll('[data-select-channel]').forEach(function (legacyButton) {
        legacyButton.addEventListener('click', function () {
            var channel = normalizeChannel(legacyButton.getAttribute('data-select-channel') || 'whatsapp');
            selectedChannel = channel;
            applyChannelCopy(channel);
            continueWithChannel(channel).catch(function () {
                showChannelUnavailable(unavailableMessageFor(channel));
                sendActualWidgetSize();
            });
        });
    });

    if (telegramCopyBtn) {
        telegramCopyBtn.addEventListener('click', function () {
            if (!telegramFallbackState.username || !navigator.clipboard) {
                return;
            }
            navigator.clipboard.writeText('@' + telegramFallbackState.username).then(function () {
                telegramCopyBtn.textContent = widgetI18n.copiedUsername || 'Copied';
                if (savedLeadId && config.saveLeadUrl) {
                    fetch(config.saveLeadUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            widget_id: config.widgetId,
                            public_key: config.publicKey,
                            lead_id: savedLeadId,
                            fallback_type: 'copy_username',
                            website: '',
                            channel: 'telegram'
                        })
                    }).catch(function () {});
                }
            });
        });
    }

    function startHover(event) {
        if (isGreetingVisible()) {
            return;
        }
        var shell = shellForElement(event && event.currentTarget ? event.currentTarget : null);
        if (isMobile()) {
            if (isStyle9LeftHover(shell) && isStyle9MobileCollapsed(shell)) {
                expandStyle9Mobile(shell);
            }
            return;
        }
        window.clearTimeout(hoverTimer);
        currentState = 'hover';
        // Apply hover class immediately. Expand styles reserve layout width in CSS so
        // the iframe does not need to catch up after a delayed max-width animation.
        if (shell) {
            shell.classList.add('is-hovering');
        }
        container.classList.add('is-hovering');
        sendActualWidgetSize();
    }

    function endHover(event) {
        if (isGreetingVisible()) {
            return;
        }
        if (isMobile()) {
            return;
        }

        var shell = shellForElement(event && event.currentTarget ? event.currentTarget : null);
        var buttonEl = event && event.currentTarget && event.currentTarget.getAttribute
            ? event.currentTarget
            : (shell ? shell.querySelector('[data-widget-button]') : null);

        // Keep hover/focus expansion while the control remains hovered or focused.
        if (buttonEl) {
            try {
                if (buttonEl.matches(':hover') || buttonEl === document.activeElement) {
                    return;
                }
            } catch (ignored) {
                // :hover matching can throw in some older engines; fall through.
            }
        }

        window.clearTimeout(hoverTimer);
        if (shell) {
            shell.classList.remove('is-hovering');
        }
        if (!container.querySelector('[data-channel-shell].is-hovering')) {
            container.classList.remove('is-hovering');
        }
        currentState = isIconOnlyStyle() ? 'icon' : 'button';
        sendActualWidgetSize();
    }

    launcherButtons.forEach(function (launcherButton) {
        launcherButton.addEventListener('pointerenter', startHover);
        launcherButton.addEventListener('pointerleave', endHover);
        launcherButton.addEventListener('focusin', startHover);
        launcherButton.addEventListener('focusout', function (event) {
            if (launcherButton.contains(event.relatedTarget)) {
                return;
            }
            endHover(event);
        });
        launcherButton.addEventListener('click', handleLauncherClick, true);
        launcherButton.addEventListener('transitionend', function () {
            sendActualWidgetSize();
        });
    });
    applyChannelCopy(selectedChannel);

    window.addEventListener('message', function (event) {
        if (!isTrustedParentMessage(event)) {
            return;
        }

        if (event.data.type === 'ctcw:page-context') {
            parentViewportWidth = parseInt(event.data.width, 10) || parentViewportWidth;
            parentViewportHeight = parseInt(event.data.height, 10) || parentViewportHeight;
            if (event.data.availableWidth != null) {
                parentAvailableWidth = parseInt(event.data.availableWidth, 10) || parentAvailableWidth;
            } else {
                parentAvailableWidth = parentViewportWidth;
            }
            if (event.data.availableHeight != null) {
                parentAvailableHeight = parseInt(event.data.availableHeight, 10) || parentAvailableHeight;
            } else {
                parentAvailableHeight = parentViewportHeight;
            }
            updatePageContext(event.data);
            updateViewportCssVars();
            applyResponsiveState();
            return;
        }

        if (event.data.type === 'ctcw:viewport') {
            parentViewportWidth = parseInt(event.data.width, 10) || parentViewportWidth;
            if (event.data.height != null) {
                parentViewportHeight = parseInt(event.data.height, 10) || parentViewportHeight;
            }
            if (event.data.availableWidth != null) {
                parentAvailableWidth = parseInt(event.data.availableWidth, 10) || parentAvailableWidth;
            }
            if (event.data.availableHeight != null) {
                parentAvailableHeight = parseInt(event.data.availableHeight, 10) || parentAvailableHeight;
            }
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
            updateViewportCssVars();
            applyResponsiveState();
        }
    });

    if (typeof ResizeObserver !== 'undefined') {
        var resizeObserver = new ResizeObserver(function () {
            sendActualWidgetSize();
        });
        resizeObserver.observe(container);
        if (launcherStack) {
            resizeObserver.observe(launcherStack);
        }
        if (greeting) {
            resizeObserver.observe(greeting);
        }
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
