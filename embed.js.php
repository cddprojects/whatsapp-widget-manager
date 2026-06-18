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

$frameId = 'ctcw-frame-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $widget['id']);
$widgetSrc = SYSTEM_BASE_URL . '/widget.php?id=' . rawurlencode((string) $widget['id']) . '&key=' . rawurlencode((string) $widget['public_key']);
$config = [
    'widgetId' => (string) $widget['id'],
    'frameId' => $frameId,
    'src' => $widgetSrc,
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
    var defaultSize = { width: 110, height: 110 };

    function isMobile() {
        return window.innerWidth <= 767;
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
        if (state === 'greeting-phone') {
            return { width: 390, height: 340 };
        }
        if (state === 'greeting') {
            return { width: 380, height: 300 };
        }
        if (state === 'button' || state === 'hover') {
            return { width: 260, height: 110 };
        }
        return { width: 110, height: 110 };
    }

    function clampSize(width, height, state) {
        var minimum = minimumForState(state || lastState || 'icon');
        return {
            width: Math.min(window.innerWidth - 16, Math.max(minimum.width, parseInt(width, 10) || minimum.width)),
            height: Math.min(window.innerHeight - 16, Math.max(minimum.height, parseInt(height, 10) || minimum.height))
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
        iframe.style[position.verticalSide] = position.verticalValue;
        iframe.style[position.horizontalSide] = position.horizontalValue;
    }

    function applySize(width, height, state) {
        var size = clampSize(width, height, state);
        var nextState = state || lastState || 'icon';
        if (lastSize && lastSize.width === size.width && lastSize.height === size.height && lastState === nextState) {
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
        iframe.style.maxWidth = 'calc(100vw - 16px)';
        iframe.style.maxHeight = 'calc(100vh - 16px)';
        iframe.style.transition = 'none';
        applyPosition();
        applySize(defaultSize.width, defaultSize.height, 'icon');
    }

    function sendViewport() {
        if (!iframe || !iframe.contentWindow) {
            return;
        }
        iframe.contentWindow.postMessage({
            type: 'ctcw:viewport',
            width: window.innerWidth,
            height: window.innerHeight,
            url: window.location.href,
            title: document.title || ''
        }, '*');
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
        if (!event.data || (event.data.type !== 'ctcw:size' && event.data.type !== 'ctcw') || String(event.data.id) !== config.widgetId) {
            return;
        }
        applySize(event.data.width, event.data.height, event.data.state || 'icon');
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
})();
