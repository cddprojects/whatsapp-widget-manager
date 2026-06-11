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
$desktopSize = widget_style_frame_size((string) ($widget['desktop_style'] ?? 'style-1'));
$mobileSize = widget_style_frame_size((string) ($widget['mobile_style'] ?? 'style-1'));
$config = [
    'widgetId' => (string) $widget['id'],
    'frameId' => $frameId,
    'src' => $widgetSrc,
    'desktop' => [
        'position' => iframe_position_settings($widget, 'desktop'),
        'size' => $desktopSize,
    ],
    'mobile' => [
        'position' => iframe_position_settings($widget, 'mobile'),
        'size' => $mobileSize,
    ],
];
?>
(function () {
    'use strict';

    var config = <?= json_for_html($config) ?>;
    var iframe = document.getElementById(config.frameId);
    var lastSize = null;

    function isMobile() {
        var screenWidth = window.screen && window.screen.width ? window.screen.width : window.innerWidth;
        return screenWidth <= 767;
    }

    function activeConfig() {
        return isMobile() ? config.mobile : config.desktop;
    }

    function maxWidth() {
        return Math.max(96, window.innerWidth - 16);
    }

    function maxHeight() {
        return Math.max(96, window.innerHeight - 16);
    }

    function clampSize(width, height) {
        return {
            width: Math.min(maxWidth(), Math.max(96, parseInt(width, 10) || 96)),
            height: Math.min(maxHeight(), Math.max(96, parseInt(height, 10) || 96))
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

    function applySize(width, height) {
        var size = clampSize(width, height);
        lastSize = size;
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
        iframe.style.maxWidth = 'calc(100vw - 16px)';
        iframe.style.maxHeight = 'calc(100vh - 16px)';
        applyPosition();
        applySize(activeConfig().size.width, activeConfig().size.height);
    }

    function mount() {
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = config.frameId;
            iframe.src = config.src;
        }

        applyBaseStyles();

        if (!iframe.parentNode) {
            document.body.appendChild(iframe);
        }
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
        if (!event.data || event.data.type !== 'ctcw:size' || String(event.data.id) !== config.widgetId) {
            return;
        }
        applySize(event.data.width, event.data.height);
    });

    window.addEventListener('resize', function () {
        if (!iframe) {
            return;
        }
        applyPosition();
        if (lastSize) {
            applySize(lastSize.width, lastSize.height);
            return;
        }
        applySize(activeConfig().size.width, activeConfig().size.height);
    });
})();
