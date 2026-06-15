(function () {
    'use strict';

    var config = window.CTCW_WIDGET || {};
    var container = document.querySelector('.ctcw-container');
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
    var greetingCaptureSize = [320, 300];
    var sizeMap = {
        'style-1': { normal: [280, 110], hover: [300, 130], greeting: [390, 300] },
        'style-2': { normal: [120, 120], hover: [130, 130], animation: [130, 130], greeting: [390, 300] },
        'style-3': { normal: [120, 120], hover: [130, 130], greeting: [390, 300] },
        'style-3-large': { normal: [150, 150], hover: [160, 160], greeting: [390, 300] },
        'style-3-extend': { normal: [150, 150], hover: [160, 160], greeting: [390, 300] },
        'style-4': { normal: [300, 110], hover: [320, 130], greeting: [390, 300] },
        'style-5': { normal: [420, 260], hover: [420, 260], greeting: [390, 300] },
        'style-6': { normal: [260, 90], hover: [280, 100], greeting: [390, 300] },
        'style-7': { normal: [130, 130], hover: [140, 140], greeting: [390, 300] },
        'style-7-extend': { normal: [420, 160], hover: [420, 160], greeting: [390, 300] },
        'style-8': { normal: [300, 110], hover: [320, 130], greeting: [390, 300] },
        'style-9-left-hover': { normal: [420, 160], hover: [420, 160], greeting: [390, 300] }
    };

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
        reportMappedSize('normal');
    }

    function sizeForState(state) {
        var styleSizes = sizeMap[currentStyle] || sizeMap['style-1'];
        if (state === 'greeting') {
            if (config.greetingCapturePhone) {
                return greetingCaptureSize;
            }
            if (!styleSizes.greeting) {
                return [380, 280];
            }
            return styleSizes.greeting;
        }
        return styleSizes[state] || styleSizes.normal || [120, 120];
    }

    function contentPadding() {
        if (currentState === 'greeting' && config.greetingCapturePhone) {
            return 24;
        }
        return 32;
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

    function getPageTitle() {
        try {
            return window.parent.document.title || '';
        } catch (error) {
            return '';
        }
    }

    function redirectToWhatsapp(url) {
        openUrl(url);
    }

    function saveLeadAndRedirect(url, submitButton) {
        var phone = phoneInput ? phoneInput.value.trim() : '';
        var phoneRequired = !!config.greetingPhoneRequired;
        var shouldSave = phone !== '' && isValidPhone(phone);

        if (phoneRequired && !isValidPhone(phone)) {
            showPhoneError('Please enter a valid phone number.');
            return;
        }

        showPhoneError('');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('is-loading');
        }
        if (greetingSuccess) {
            greetingSuccess.hidden = false;
        }

        if (!shouldSave || !config.saveLeadUrl) {
            redirectToWhatsapp(url);
            return;
        }

        fetch(config.saveLeadUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                widget_id: config.widgetId,
                public_key: config.publicKey,
                visitor_phone: phone,
                source_url: document.referrer || '',
                page_title: getPageTitle(),
                whatsapp_redirect_url: url,
                website: ''
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.success) {
                    console.warn('Lead save failed:', data && data.message ? data.message : 'Unknown error');
                }
            })
            .catch(function (error) {
                console.warn('Lead save failed:', error);
            })
            .finally(function () {
                redirectToWhatsapp(url);
            });
    }

    function sendWidgetSize(width, height, state) {
        window.parent.postMessage({
            type: 'ctcw',
            id: String(config.widgetId || ''),
            state: state || currentState,
            width: width,
            height: height
        }, '*');
    }

    function reportMappedSize(state) {
        currentState = state;
        var size = sizeForState(state);
        sendWidgetSize(size[0], size[1], state);
    }

    function scheduleSizeReports() {
        [50, 300, 800].forEach(function (delay) {
            window.setTimeout(reportSize, delay);
        });
    }

    function reportSize() {
        window.requestAnimationFrame(function () {
            var rects = [container.getBoundingClientRect()];
            var hoverBox = container.querySelector('.ctcw-hover-box');
            if (greeting && greeting.classList.contains('is-visible')) {
                rects.push(greeting.getBoundingClientRect());
            }
            if (hoverBox && window.getComputedStyle(hoverBox).opacity !== '0') {
                rects.push(hoverBox.getBoundingClientRect());
            }

            var minX = Math.min.apply(null, rects.map(function (rect) { return rect.left; }));
            var minY = Math.min.apply(null, rects.map(function (rect) { return rect.top; }));
            var maxX = Math.max.apply(null, rects.map(function (rect) { return rect.right; }));
            var maxY = Math.max.apply(null, rects.map(function (rect) { return rect.bottom; }));
            var minimum = sizeForState(currentState);
            var pad = contentPadding();
            var width = Math.max(minimum[0], Math.ceil(maxX - minX + pad));
            var height = Math.max(minimum[1], Math.ceil(maxY - minY + pad));

            sendWidgetSize(width, height, currentState);
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
        currentState = 'greeting';
        var size = sizeForState('greeting');
        sendWidgetSize(size[0], size[1], 'greeting');
        window.requestAnimationFrame(function () {
            greeting.classList.add('is-visible');
            window.requestAnimationFrame(function () {
                reportSize();
                scheduleSizeReports();
            });
        });
    }

    if (config.greetingEnabled && greeting) {
        window.setTimeout(
            revealGreeting,
            Math.max(0, Number(config.greetingDelaySeconds || 0)) * 1000
        );
    }

    if (closeGreeting) {
        closeGreeting.addEventListener('click', function () {
            greeting.classList.remove('is-visible');
            showPhoneError('');
            reportMappedSize('normal');
            reportSize();
            scheduleSizeReports();
        });
    }

    if (greetingSubmit) {
        greetingSubmit.addEventListener('click', function (event) {
            event.preventDefault();
            if (!config.online) {
                return;
            }
            saveLeadAndRedirect(buildUrl(), greetingSubmit);
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            showPhoneError('');
        });
    }

    function startHover() {
        window.clearTimeout(hoverTimer);
        reportMappedSize('hover');
        hoverTimer = window.setTimeout(function () {
            container.classList.add('is-hovering');
            reportMappedSize('hover');
        }, 40);
    }

    function endHover() {
        window.clearTimeout(hoverTimer);
        container.classList.remove('is-hovering');
        window.setTimeout(function () {
            if (!greeting || !greeting.classList.contains('is-visible')) {
                reportMappedSize('normal');
            }
        }, 250);
    }

    container.addEventListener('mouseenter', startHover);
    container.addEventListener('mouseleave', endHover);
    button.addEventListener('mouseenter', startHover);
    button.addEventListener('mouseleave', endHover);

    button.addEventListener('click', function (event) {
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
            reportMappedSize('animation');
            button.classList.add('is-shaking');
            scheduleSizeReports();
            window.setTimeout(function () { button.classList.remove('is-shaking'); }, 400);
            return;
        }
        openUrl(buildUrl());
    }, true);

    window.addEventListener('message', function (event) {
        if (!event.data) {
            return;
        }
        if (event.data.type !== 'ctcw:viewport' && !(event.data.type === 'ctcw' && !event.data.id)) {
            return;
        }
        parentViewportWidth = parseInt(event.data.width, 10) || parentViewportWidth;
        applyResponsiveState();
        scheduleSizeReports();
    });

    applyResponsiveState();
    reportMappedSize('normal');
    scheduleSizeReports();
    window.addEventListener('resize', applyResponsiveState);
    window.addEventListener('load', reportSize);
    document.addEventListener('DOMContentLoaded', function () {
        reportMappedSize('normal');
        reportSize();
        scheduleSizeReports();
    });

    var reportsRemaining = 8;
    var startupReporter = window.setInterval(function () {
        reportSize();
        reportsRemaining -= 1;
        if (reportsRemaining <= 0) {
            window.clearInterval(startupReporter);
        }
    }, 250);
})();
