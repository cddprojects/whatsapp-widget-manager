(function () {
    'use strict';

    var config = window.CTCW_WIDGET || {};
    var container = document.querySelector('.ctcw-container');
    var button = document.querySelector('[data-widget-button]');
    var greeting = document.querySelector('[data-greeting]');
    var closeGreeting = document.querySelector('[data-close-greeting]');
    var styleNames = ['style-1', 'style-2', 'style-3', 'style-3-large', 'style-4', 'style-5', 'style-6', 'style-7', 'style-7-extend', 'style-8'];
    var isOpening = false;

    if (!container || !button) {
        return;
    }

    function cleanDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function isMobile() {
        return window.matchMedia('(max-width: 767px)').matches;
    }

    function applyResponsiveState() {
        var mobile = isMobile();
        var activeStyle = mobile ? container.dataset.mobileStyle : container.dataset.desktopStyle;

        styleNames.forEach(function (style) {
            container.classList.remove(style);
        });
        container.classList.add(activeStyle || 'style-1');
        container.classList.toggle('is-hidden', mobile ? !config.showMobile : !config.showDesktop);
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

    if (config.greetingEnabled && greeting) {
        window.setTimeout(function () {
            greeting.classList.add('is-visible');
        }, Math.max(0, Number(config.greetingDelaySeconds || 0)) * 1000);
    }

    if (closeGreeting) {
        closeGreeting.addEventListener('click', function () {
            greeting.classList.remove('is-visible');
        });
    }

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
            button.classList.add('is-shaking');
            window.setTimeout(function () { button.classList.remove('is-shaking'); }, 400);
            return;
        }
        openUrl(buildUrl());
    }, true);

    applyResponsiveState();
    window.addEventListener('resize', applyResponsiveState);
})();
