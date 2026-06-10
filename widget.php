<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$widgetId = (int) ($_GET['id'] ?? 0);
$publicKey = (string) ($_GET['key'] ?? '');
$widget = $widgetId > 0 && $publicKey !== '' ? find_public_widget($widgetId, $publicKey) : null;

if (!$widget) {
    http_response_code(404);
    exit('Widget not found.');
}

$referrer = $_SERVER['HTTP_REFERER'] ?? null;
$systemDomain = normalize_domain(SYSTEM_BASE_URL);
$referrerDomain = $referrer ? normalize_domain($referrer) : '';
$isSystemPreview = $referrerDomain !== '' && $systemDomain !== '' && $referrerDomain === $systemDomain;
$domainAllowed = $isSystemPreview || domain_matches_referrer((string) $widget['website_domain'], $referrer);
$showWidget = $domainAllowed && !empty($widget['show_global']);
$isOnline = is_widget_online($widget);
$randomNumbers = json_decode((string) ($widget['random_numbers_json'] ?? '[]'), true);
if (!is_array($randomNumbers)) {
    $randomNumbers = [];
}

$widgetConfig = [
    'widgetName' => (string) $widget['widget_name'],
    'site' => (string) $widget['website_domain'],
    'countryCode' => (string) $widget['whatsapp_country_code'],
    'number' => (string) $widget['whatsapp_number'],
    'useRandomNumbers' => !empty($widget['use_random_numbers']),
    'randomNumbers' => $randomNumbers,
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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($widget['widget_name']) ?></title>
    <link rel="stylesheet" href="assets/css/widget.css">
    <?php if ($showWidget): ?>
        <?= $widget['custom_script_head'] ?? '' ?>
        <style>
            .ctcw-container {
                position: <?= e($desktopPositionType) ?>;
                <?= e($desktopVerticalSide) ?>: <?= e($desktopVerticalValue) ?>;
                <?= e($desktopHorizontalSide) ?>: <?= e($desktopHorizontalValue) ?>;
            }
            @media (max-width: 767px) {
                .ctcw-container {
                    position: <?= e($mobilePositionType) ?>;
                    top: auto;
                    bottom: auto;
                    left: auto;
                    right: auto;
                    <?= e($mobileVerticalSide) ?>: <?= e($mobileVerticalValue) ?>;
                    <?= e($mobileHorizontalSide) ?>: <?= e($mobileHorizontalValue) ?>;
                }
            }
        </style>
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
        class="ctcw-container <?= e($desktopStyle) ?> <?= $isOnline ? 'is-online' : 'is-offline' ?>"
        data-mobile-style="<?= e($mobileStyle) ?>"
        data-desktop-style="<?= e($desktopStyle) ?>"
        data-show-desktop="<?= !empty($widget['show_desktop']) ? '1' : '0' ?>"
        data-show-mobile="<?= !empty($widget['show_mobile']) ? '1' : '0' ?>"
    >
        <?php if (!empty($widget['greeting_enabled'])): ?>
            <div class="ctcw-greeting" data-greeting>
                <button class="ctcw-close" type="button" aria-label="Close greeting" data-close-greeting>&times;</button>
                <strong><?= e($widget['greeting_title']) ?></strong>
                <p><?= e($widget['greeting_message']) ?></p>
            </div>
        <?php endif; ?>
        <button class="ctcw-widget" type="button" data-widget-button aria-label="<?= e($widget['call_to_action']) ?>">
            <span class="ctcw-icon"><?= whatsapp_icon_svg() ?></span>
            <span class="ctcw-text"><?= e($isOnline ? (string) $widget['call_to_action'] : (string) $widget['offline_message']) ?></span>
            <?php if ($desktopStyle === 'style-5' || $mobileStyle === 'style-5'): ?>
                <span class="ctcw-hover-box"><?= e($widget['greeting_message'] ?: $widget['call_to_action']) ?></span>
            <?php endif; ?>
        </button>
    </div>
    <script>
        window.CTCW_WIDGET = <?= json_for_html($widgetConfig) ?>;
    </script>
    <script src="assets/js/widget.js"></script>
    <?= $widget['custom_script_footer'] ?? '' ?>
<?php endif; ?>
</body>
</html>
