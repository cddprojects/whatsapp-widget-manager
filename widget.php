<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

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
header('Content-Security-Policy: ' . csp_frame_ancestors($widget));

$domainAllowed = $isSystemPreview || domain_matches_referrer($widget, $referrer);
if (!$domainAllowed) {
    http_response_code(403);
    exit;
}

$showWidget = $domainAllowed && widget_is_publicly_active($widget);
$isOnline = is_widget_online($widget);
$activeNumbers = widget_phone_list($widget);
$destinationCount = count($activeNumbers);
$destinationMethod = effective_destination_selection_method($widget, $destinationCount);

$widgetConfig = [
    'widgetId' => (int) $widget['id'],
    'initialMode' => in_array((string) ($_GET['mode'] ?? ''), ['desktop', 'mobile'], true) ? (string) $_GET['mode'] : '',
    'widgetName' => (string) $widget['widget_name'],
    'site' => (string) $widget['website_domain'],
    'destinationCount' => $destinationCount,
    'destinationSelectionMethod' => $destinationMethod,
    'destinationResolveUrl' => SYSTEM_BASE_URL . '/resolve-widget-destination.php',
    'prefilledMessage' => (string) $widget['prefilled_message'],
    'callToAction' => (string) $widget['call_to_action'],
    'desktopOpenLinkType' => (string) $widget['desktop_open_link_type'],
    'mobileOpenLinkType' => (string) $widget['mobile_open_link_type'],
    'desktopUrlStructure' => (string) $widget['desktop_url_structure'],
    'mobileUrlStructure' => (string) $widget['mobile_url_structure'],
    'customUrl' => (string) $widget['custom_url'],
    'showDesktop' => !empty($widget['show_desktop']),
    'showMobile' => !empty($widget['show_mobile']),
    'online' => $isOnline,
    'offlineMessage' => (string) $widget['offline_message'],
    'greetingEnabled' => !empty($widget['greeting_enabled']),
    'greetingDelaySeconds' => (int) $widget['greeting_delay_seconds'],
    'greetingCapturePhone' => !empty($widget['greeting_capture_phone']),
    'greetingPhoneRequired' => !empty($widget['greeting_phone_required']),
    'greetingForcePhoneCapture' => !empty($widget['greeting_force_phone_capture']),
    'greetingPhonePlaceholder' => (string) ($widget['greeting_phone_placeholder'] ?? 'Enter your phone number'),
    'greetingSubmitText' => (string) ($widget['greeting_submit_text'] ?? 'Continue to WhatsApp'),
    'greetingLeadSuccessMessage' => (string) ($widget['greeting_lead_success_message'] ?? 'Redirecting to WhatsApp...'),
    'publicKey' => (string) $widget['public_key'],
    'saveLeadUrl' => SYSTEM_BASE_URL . '/save-widget-lead.php',
];

$desktopStyle = (string) ($widget['desktop_style'] ?? 'style-1');
$mobileStyle = (string) ($widget['mobile_style'] ?? 'style-1');
$desktopPositionType = (string) ($widget['desktop_position_type'] ?? 'fixed');
$mobilePositionType = (string) ($widget['mobile_position_type'] ?? 'fixed');
$desktopVerticalSide = (string) ($widget['desktop_vertical_position_type'] ?? 'bottom');
$desktopHorizontalSide = (string) ($widget['desktop_horizontal_position_type'] ?? 'right');
$mobileVerticalSide = (string) ($widget['mobile_vertical_position_type'] ?? 'bottom');
$mobileHorizontalSide = (string) ($widget['mobile_horizontal_position_type'] ?? 'right');
$desktopVerticalValue = (string) ($widget['desktop_vertical_position_value'] ?? '25px');
$desktopHorizontalValue = (string) ($widget['desktop_horizontal_position_value'] ?? '25px');
$mobileVerticalValue = (string) ($widget['mobile_vertical_position_value'] ?? '25px');
$mobileHorizontalValue = (string) ($widget['mobile_horizontal_position_value'] ?? '25px');
$desktopAlign = $desktopHorizontalSide === 'left' ? 'flex-start' : 'flex-end';
$mobileAlign = $mobileHorizontalSide === 'left' ? 'flex-start' : 'flex-end';
$desktopAnchorClass = 'is-' . $desktopVerticalSide . ' is-' . $desktopHorizontalSide;
$mobileAnchorClass = 'is-mobile-' . $mobileVerticalSide . ' is-mobile-' . $mobileHorizontalSide;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($widget['widget_name']) ?></title>
    <link rel="stylesheet" href="assets/css/widget.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/widget.css') ?>">
    <?php if ($showWidget): ?>
        <?= $widget['custom_script_head'] ?? '' ?>
        <?php if (!empty($widget['custom_css'])): ?>
            <style>
                <?= $widget['custom_css'] ?>
            </style>
        <?php endif; ?>
    <?php endif; ?>
</head>
<body>
<?php if ($showWidget): ?>
    <?= $widget['custom_script_body'] ?? '' ?>
    <div
        class="ctcw-container ctcw-widget-root ctcw-widget-anchor <?= e($desktopAnchorClass) ?> <?= e($mobileAnchorClass) ?> <?= e($desktopStyle) ?> <?= $isOnline ? 'is-online' : 'is-offline' ?>"
        data-mobile-style="<?= e($mobileStyle) ?>"
        data-desktop-style="<?= e($desktopStyle) ?>"
        data-show-desktop="<?= !empty($widget['show_desktop']) ? '1' : '0' ?>"
        data-show-mobile="<?= !empty($widget['show_mobile']) ? '1' : '0' ?>"
        data-desktop-align="<?= e($desktopAlign) ?>"
        data-mobile-align="<?= e($mobileAlign) ?>"
    >
        <?php if (!empty($widget['greeting_enabled'])): ?>
            <div class="ctcw-greeting ctcw-widget-popup<?= !empty($widget['greeting_capture_phone']) ? ' has-capture' : '' ?>" data-greeting>
                <div class="ctcw-greeting-header">
                    <strong class="ctcw-greeting-title"><?= e($widget['greeting_title']) ?></strong>
                    <button class="ctcw-close" type="button" aria-label="Close WhatsApp widget" data-close-greeting>
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php if (!empty($widget['greeting_capture_phone'])): ?>
                    <div class="ctcw-greeting-form">
                        <p class="ctcw-greeting-message"><?= e($widget['greeting_message']) ?></p>
                        <div class="ctcw-phone-field">
                            <div class="ctcw-phone-row">
                                <input
                                    class="ctcw-phone-input"
                                    type="tel"
                                    data-greeting-phone
                                    placeholder="<?= e((string) ($widget['greeting_phone_placeholder'] ?? 'Enter your phone number')) ?>"
                                    autocomplete="tel"
                                >
                                <button
                                    type="button"
                                    class="ctcw-greeting-submit"
                                    data-greeting-submit
                                    aria-label="<?= e((string) ($widget['greeting_submit_text'] ?? 'Continue to WhatsApp')) ?>"
                                >
                                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                                        <path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z"/>
                                    </svg>
                                </button>
                            </div>
                            <span class="ctcw-phone-error" data-greeting-phone-error hidden></span>
                        </div>
                        <span class="ctcw-greeting-success" data-greeting-success hidden><?= e((string) ($widget['greeting_lead_success_message'] ?? 'Redirecting to WhatsApp...')) ?></span>
                    </div>
                <?php else: ?>
                    <p class="ctcw-greeting-message"><?= e($widget['greeting_message']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <button class="ctcw-widget ctcw-launcher" type="button" data-widget-button aria-label="<?= e($widget['call_to_action']) ?>">
            <span class="ctcw-icon"><?= whatsapp_icon_svg() ?></span>
            <span class="ctcw-text"><?= e($isOnline ? (string) $widget['call_to_action'] : (string) $widget['offline_message']) ?></span>
            <?php if ($desktopStyle === 'style-5' || $mobileStyle === 'style-5'): ?>
                <span class="ctcw-hover-box"><?= e($widget['greeting_message'] ?: $widget['call_to_action']) ?></span>
            <?php endif; ?>
        </button>
    </div>
    <script>
        window.CTCW_WIDGET = <?= json_for_html($widgetConfig) ?>;
        window.CTCW_WIDGET_ID = <?= (int) $widget['id'] ?>;
        window.CTCW_PUBLIC_KEY = <?= json_for_html((string) $widget['public_key']) ?>;
        window.CTCW_GREETING_CAPTURE_PHONE = <?= !empty($widget['greeting_capture_phone']) ? 'true' : 'false' ?>;
        window.CTCW_FORCE_PHONE_CAPTURE = <?= !empty($widget['greeting_force_phone_capture']) ? 'true' : 'false' ?>;
        window.CTCW_PHONE_REQUIRED = <?= !empty($widget['greeting_phone_required']) ? 'true' : 'false' ?>;
    </script>
    <script src="assets/js/widget.js?v=<?= (int) filemtime(__DIR__ . '/assets/js/widget.js') ?>"></script>
    <?= $widget['custom_script_footer'] ?? '' ?>
<?php endif; ?>
</body>
</html>
