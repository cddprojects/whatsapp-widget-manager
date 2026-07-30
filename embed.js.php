<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$widgetId = (int) ($_GET['id'] ?? 0);
$publicKey = (string) ($_GET['key'] ?? '');
$widget = $widgetId > 0 && $publicKey !== '' ? find_public_widget($widgetId, $publicKey) : null;

if (!$widget) {
    http_response_code(404);
    exit;
}

$referrer = $_SERVER['HTTP_REFERER'] ?? null;
$systemDomain = normalize_domain(SYSTEM_BASE_URL);
$systemHost = referrer_host(SYSTEM_BASE_URL);
$referrerDomain = referrer_host($referrer);
$isSystemPreview = $referrerDomain !== ''
    && ($referrerDomain === $systemHost || ($systemDomain !== '' && normalize_domain((string) $referrer) === $systemDomain));

if (!$isSystemPreview && !domain_matches_referrer($widget, $referrer)) {
    http_response_code(403);
    exit;
}

if (!$isSystemPreview && !widget_is_publicly_active($widget)) {
    exit;
}

$frameId = 'ctcw-frame-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $widget['id']);
$widgetSrc = SYSTEM_BASE_URL . '/widget.php?id=' . rawurlencode((string) $widget['id']) . '&key=' . rawurlencode((string) $widget['public_key']);
$config = [
    'widgetId' => (string) $widget['id'],
    'frameId' => $frameId,
    'src' => $widgetSrc,
    'websiteName' => (string) ($widget['website_name'] ?? ''),
    'desktop' => [
        'position' => iframe_position_settings($widget, 'desktop'),
    ],
    'mobile' => [
        'position' => iframe_position_settings($widget, 'mobile'),
    ],
];
?>
(function () {
    'use strict';

    var config = <?= json_for_html($config) ?>;
    var iframe = document.getElementById(config.frameId);
    var lastSize = null;
    var lastState = 'icon';
    var defaultSize = { width: 72, height: 72 };
    var mobileDefaultSize = { width: 68, height: 68 };

    function isMobile() {
        return window.innerWidth <= 767;
    }

    function viewportMargins() {
        return isMobile() ? 24 : 16;
    }

    function activeConfig() {
        return isMobile() ? config.mobile : config.desktop;
    }

    function activeMode() {
        return isMobile() ? 'mobile' : 'desktop';
    }

    function iframeSrc() {
        return config.src + '&mode=' + encodeURIComponent(activeMode());
    }

    function cssLengthToPx(value, fallback) {
        var raw = String(value == null ? '' : value).trim();
        if (raw === '') {
            return fallback;
        }
        if (/^-?\d+(\.\d+)?px$/i.test(raw)) {
            return Math.max(0, parseFloat(raw));
        }
        if (/^-?\d+(\.\d+)?$/i.test(raw)) {
            return Math.max(0, parseFloat(raw));
        }
        return fallback;
    }

    function positionInsetPx() {
        var position = activeConfig().position || {};
        return {
            vertical: cssLengthToPx(position.verticalValue, isMobile() ? 12 : 25),
            horizontal: cssLengthToPx(position.horizontalValue, isMobile() ? 12 : 25)
        };
    }

    function availableViewportSize() {
        var margin = viewportMargins();
        var inset = positionInsetPx();
        var width = Math.max(64, window.innerWidth - margin - inset.horizontal);
        var height = Math.max(64, window.innerHeight - margin - inset.vertical);

        return {
            width: Math.floor(width),
            height: Math.floor(height)
        };
    }

    function minimumForState(state) {
        // Closed-launcher floors only. Open greeting sizes come from measured content.
        if (isMobile()) {
            if (state === 'greeting-phone' || state === 'greeting-phone-consent' || state === 'greeting') {
                return { width: 280, height: 180 };
            }
            if (state === 'button' || state === 'hover') {
                return { width: 64, height: 64 };
            }
            return { width: 64, height: 64 };
        }

        if (state === 'greeting-phone' || state === 'greeting-phone-consent' || state === 'greeting') {
            return { width: 300, height: 180 };
        }
        if (state === 'button' || state === 'hover') {
            return { width: 72, height: 72 };
        }
        return { width: 72, height: 72 };
    }

    function sanitizeDimension(value, fallback) {
        var number = typeof value === 'number' ? value : parseFloat(String(value));
        if (!isFinite(number) || number <= 0) {
            return fallback;
        }
        return Math.ceil(number);
    }

    function clampSize(width, height, state) {
        var minimum = minimumForState(state || lastState || 'icon');
        var available = availableViewportSize();
        var nextWidth = sanitizeDimension(width, minimum.width);
        var nextHeight = sanitizeDimension(height, minimum.height);

        return {
            width: Math.min(available.width, Math.max(minimum.width, nextWidth)),
            height: Math.min(available.height, Math.max(minimum.height, nextHeight))
        };
    }

    function applyPosition() {
        var active = activeConfig();
        var position = active.position;
        var verticalValue = position.verticalValue || '25px';
        var horizontalValue = position.horizontalValue || '25px';

        iframe.style.position = position.position;
        iframe.style.top = 'auto';
        iframe.style.bottom = 'auto';
        iframe.style.left = 'auto';
        iframe.style.right = 'auto';

        if (isMobile()) {
            if (position.verticalSide === 'bottom') {
                iframe.style.bottom = 'calc(' + verticalValue + ' + env(safe-area-inset-bottom, 0px))';
            } else {
                iframe.style.top = 'calc(' + verticalValue + ' + env(safe-area-inset-top, 0px))';
            }

            iframe.style[position.horizontalSide] = horizontalValue;
            return;
        }

        iframe.style[position.verticalSide] = verticalValue;
        iframe.style[position.horizontalSide] = horizontalValue;
    }

    function applySize(width, height, state) {
        var nextState = state || lastState || 'icon';
        var size = clampSize(width, height, nextState);

        if (
            lastSize &&
            lastSize.width === size.width &&
            lastSize.height === size.height &&
            lastState === nextState
        ) {
            return;
        }

        var stateChanged = lastState !== nextState;
        lastSize = size;
        lastState = nextState;

        iframe.style.width = size.width + 'px';
        iframe.style.height = size.height + 'px';

        if (stateChanged && isMobile() && (nextState === 'button' || nextState === 'icon')) {
            window.setTimeout(function () {
                sendViewport();
            }, 80);
        }
    }

    function applyBaseStyles() {
        iframe.setAttribute('scrolling', 'no');
        iframe.setAttribute('allowtransparency', 'true');
        iframe.setAttribute('title', 'Click to Chat widget');
        iframe.style.border = '0';
        iframe.style.background = 'transparent';
        iframe.style.zIndex = '999999';
        iframe.style.overflow = 'hidden';
        iframe.style.pointerEvents = 'auto';
        iframe.style.boxShadow = 'none';
        iframe.style.maxWidth = 'calc(100vw - ' + viewportMargins() + 'px)';
        iframe.style.maxHeight = isMobile()
            ? 'calc(100svh - ' + viewportMargins() + 'px)'
            : 'calc(100vh - ' + viewportMargins() + 'px)';
        iframe.style.transition = 'none';
        applyPosition();
        var initial = isMobile() ? mobileDefaultSize : defaultSize;
        applySize(initial.width, initial.height, 'icon');
    }

    function trustedIframeOrigin() {
        try {
            return new URL(config.src).origin;
        } catch (error) {
            return '';
        }
    }

    function hostnameSiteLabel(hostname) {
        var normalized = String(hostname || '').replace(/^www\./i, '');
        if (!normalized) {
            return '';
        }

        var parts = normalized.split('.').filter(Boolean);
        if (parts.length > 1) {
            return parts[0];
        }

        return normalized;
    }

    function getPageContext() {
        var ogSiteName = document.querySelector('meta[property="og:site_name"]');
        var applicationName = document.querySelector('meta[name="application-name"]');
        var ogTitle = document.querySelector('meta[property="og:title"]');
        var savedSiteName = String(config.websiteName || '').trim();
        var detectedSiteName = (ogSiteName ? ogSiteName.getAttribute('content') : '') || '';
        detectedSiteName = detectedSiteName.trim();
        if (!detectedSiteName && applicationName) {
            detectedSiteName = String(applicationName.getAttribute('content') || '').trim();
        }
        if (!detectedSiteName) {
            detectedSiteName = hostnameSiteLabel(window.location.hostname);
        }

        var title = String(document.title || '').trim();
        if (!title && ogTitle) {
            title = String(ogTitle.getAttribute('content') || '').trim();
        }

        return {
            siteName: savedSiteName || detectedSiteName || '',
            siteUrl: window.location.origin || '',
            site: window.location.hostname.replace(/^www\./i, ''),
            title: title,
            url: window.location.pathname || '/',
            urlFull: window.location.href
        };
    }

    function sendViewport() {
        if (!iframe || !iframe.contentWindow) {
            return;
        }

        var pageContext = getPageContext();
        var targetOrigin = trustedIframeOrigin() || '*';
        var available = availableViewportSize();

        iframe.contentWindow.postMessage({
            type: 'ctcw:page-context',
            width: window.innerWidth,
            height: window.innerHeight,
            availableWidth: available.width,
            availableHeight: available.height,
            siteName: pageContext.siteName,
            siteUrl: pageContext.siteUrl,
            site: pageContext.site,
            title: pageContext.title,
            url: pageContext.url,
            urlFull: pageContext.urlFull
        }, targetOrigin);
    }

    function mount() {
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = config.frameId;
            iframe.src = iframeSrc();
            iframe.addEventListener('load', function () {
                sendViewport();
                window.setTimeout(sendViewport, 100);
                window.setTimeout(sendViewport, 500);
            });
        } else if (!iframe.src || iframe.src.indexOf('mode=') === -1) {
            iframe.src = iframeSrc();
        }

        applyBaseStyles();

        if (!iframe.parentNode) {
            document.body.appendChild(iframe);
        }
        sendViewport();
    }

    function ready(callback) {
        if (document.body) {
            callback();
            return;
        }
        document.addEventListener('DOMContentLoaded', callback);
    }

    ready(mount);

    window.addEventListener('message', function (event) {
        if (!iframe || event.source !== iframe.contentWindow) {
            return;
        }

        var trustedWidgetOrigin = '';
        try {
            trustedWidgetOrigin = new URL(config.src).origin;
        } catch (error) {
            trustedWidgetOrigin = '';
        }

        if (trustedWidgetOrigin && event.origin !== trustedWidgetOrigin) {
            return;
        }

        if (!event.data || typeof event.data !== 'object') {
            return;
        }

        if (event.data.type === 'ctcw:size' || event.data.type === 'ctcw') {
            if (String(event.data.id) !== config.widgetId) {
                return;
            }
            applySize(event.data.width, event.data.height, event.data.state || 'icon');
            return;
        }

        if (event.data.type === 'ctcw:phone-submit-success') {
            if (String(event.data.widgetId) !== config.widgetId) {
                return;
            }

            window.dispatchEvent(new CustomEvent('ctcw:phone-submit-success', {
                detail: {
                    widgetId: event.data.widgetId,
                    submitButtonId: event.data.submitButtonId
                }
            }));
        }
    });

    window.addEventListener('resize', function () {
        if (!iframe) {
            return;
        }
        applyPosition();
        sendViewport();
        if (lastSize) {
            applySize(lastSize.width, lastSize.height, lastState);
        }
    });

    window.addEventListener('popstate', sendViewport);
    window.addEventListener('hashchange', sendViewport);
})();
