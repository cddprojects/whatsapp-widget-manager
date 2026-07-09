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

    function minimumForState(state) {
        if (isMobile()) {
            if (state === 'greeting-phone') {
                return { width: 330, height: 300 };
            }
            if (state === 'greeting') {
                return { width: 310, height: 240 };
            }
            if (state === 'button' || state === 'hover') {
                return { width: 116, height: 72 };
            }
            return { width: 78, height: 78 };
        }

        if (state === 'greeting-phone') {
            return { width: 390, height: 240 };
        }
        if (state === 'greeting') {
            return { width: 380, height: 240 };
        }
        if (state === 'button' || state === 'hover') {
            return { width: 260, height: 110 };
        }
        return { width: 110, height: 110 };
    }

    function clampSize(width, height, state) {
        var minimum = minimumForState(state || lastState || 'icon');
        var margin = viewportMargins();
        var requestedWidth = parseInt(width, 10) || minimum.width;

        if (isMobile() && (state === 'button' || state === 'hover')) {
            if (requestedWidth >= 150) {
                requestedWidth = 180; // long button style
            } else {
                requestedWidth = 116; // short button style
            }
        }

        return {
            width: Math.min(window.innerWidth - margin, Math.max(minimum.width, requestedWidth)),
            height: Math.min(window.innerHeight - margin, Math.max(minimum.height, parseInt(height, 10) || minimum.height))
        };
    }

    function applyPosition() {
        var active = activeConfig();
        var position = active.position;

        iframe.style.position = position.position;
        iframe.style.top = 'auto';
        iframe.style.bottom = 'auto';
        iframe.style.left = 'auto';
        iframe.style.right = 'auto';

        if (isMobile()) {
            if (position.verticalSide === 'bottom') {
                iframe.style.bottom = 'max(12px, env(safe-area-inset-bottom))';
            } else {
                iframe.style.top = 'max(12px, env(safe-area-inset-top))';
            }

            if (position.horizontalSide === 'right') {
                iframe.style.right = 'max(12px, env(safe-area-inset-right))';
            } else {
                iframe.style.left = 'max(12px, env(safe-area-inset-left))';
            }
            return;
        }

        iframe.style[position.verticalSide] = position.verticalValue;
        iframe.style[position.horizontalSide] = position.horizontalValue;
    }

function applySize(width, height, state) {
    var nextState = state || lastState || 'icon';

    // Force desktop greeting iframe height to 235px
    if (!isMobile() && (nextState === 'greeting' || nextState === 'greeting-phone')) {
        height = 255;
    }

    var size = clampSize(width, height, nextState);

    if (
        lastSize &&
        lastSize.width === size.width &&
        lastSize.height === size.height &&
        lastState === nextState
    ) {
        return;
    }

    lastSize = size;
    lastState = nextState;

    iframe.style.width = size.width + 'px';
    iframe.style.height = size.height + 'px';
}

    function applyBaseStyles() {
        iframe.setAttribute('scrolling', 'no');
        iframe.setAttribute('allowtransparency', 'true');
        iframe.setAttribute('title', 'Click to WhatsApp chat widget');
        iframe.style.border = '0';
        iframe.style.background = 'transparent';
        iframe.style.zIndex = '999999';
        iframe.style.overflow = 'hidden';
        iframe.style.pointerEvents = 'auto';
        iframe.style.boxShadow = 'none';
        iframe.style.maxWidth = 'calc(100vw - ' + viewportMargins() + 'px)';
        iframe.style.maxHeight = isMobile() ? 'calc(100svh - ' + viewportMargins() + 'px)' : 'calc(100vh - ' + viewportMargins() + 'px)';
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

        iframe.contentWindow.postMessage({
            type: 'ctcw:page-context',
            width: window.innerWidth,
            height: window.innerHeight,
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

        if (!event.data) {
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
