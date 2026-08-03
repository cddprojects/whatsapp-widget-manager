<?php
declare(strict_types=1);

/**
 * Focused Telegram / channel CLI tests.
 *
 * Usage: php cli/test_telegram_channel.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/functions.php';

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $label): void
{
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] {$label}\n";
        $passed++;
        return;
    }
    echo "[FAIL] {$label}\n";
    $failed++;
}

function assert_eq($expected, $actual, string $label): void
{
    assert_true($expected === $actual, $label . ' (expected=' . var_export($expected, true) . ', actual=' . var_export($actual, true) . ')');
}

echo "=== Telegram validation tests ===\n";

$username = normalize_telegram_username(' @Example_Support ');
assert_true($username['ok'], 'Username normalization succeeds');
assert_eq('Example_Support', $username['value'] ?? null, 'Username strips @ and trims');

$spaces = normalize_telegram_username('bad name');
assert_true(!$spaces['ok'], 'Username with spaces rejected');

$urlAsUsername = normalize_telegram_username('https://t.me/example');
assert_true(!$urlAsUsername['ok'], 'URL rejected in username field');

$bot = normalize_telegram_username('@SupportBot', TELEGRAM_DESTINATION_BOT);
assert_true($bot['ok'], 'Bot username accepted');

$badBot = normalize_telegram_username('@support', TELEGRAM_DESTINATION_BOT);
assert_true(!$badBot['ok'], 'Non-bot username rejected for bot type');

$startOk = normalize_telegram_bot_start_parameter('offer_123');
assert_true($startOk['ok'], 'Valid bot start parameter');

$startBad = normalize_telegram_bot_start_parameter('bad?query=1');
assert_true(!$startBad['ok'], 'Bot start parameter rejects query injection');

$group = normalize_telegram_link('https://t.me/example_group');
assert_true($group['ok'], 'Public group/channel link accepted');
assert_eq('https://t.me/example_group', $group['value'] ?? null, 'Link canonicalized to t.me');

$invite = normalize_telegram_link('https://t.me/+AbCdEfGhIjK');
assert_true($invite['ok'], 'Invite link accepted');

$js = normalize_telegram_link('javascript:alert(1)');
assert_true(!$js['ok'], 'javascript: rejected');

$data = normalize_telegram_link('data:text/html,hi');
assert_true(!$data['ok'], 'data: rejected');

$external = normalize_telegram_link('https://evil.example/t.me/x');
assert_true(!$external['ok'], 'External domain rejected');

$built = build_telegram_redirect_url([
    'destination_type' => 'bot',
    'destination_value' => 'SupportBot',
    'bot_start_parameter' => 'promo1',
]);
assert_true($built['ok'], 'Bot URL builder succeeds');
assert_eq('https://t.me/SupportBot?start=promo1', $built['url'] ?? null, 'Bot URL with start param');
assert_eq('copy_username', $built['fallback']['type'] ?? null, 'Copy-username fallback present');

$userBuilt = build_telegram_redirect_url([
    'destination_type' => 'username',
    'destination_value' => 'example_support',
]);
assert_eq('https://t.me/example_support', $userBuilt['url'] ?? null, 'Username URL builder');

assert_eq('whatsapp', normalize_lead_channel(null), 'Null lead channel treated as WhatsApp');
assert_eq('telegram', normalize_lead_channel('telegram'), 'Telegram lead channel preserved');
assert_eq(null, normalize_widget_channel('sms'), 'Unsupported channel rejected');

echo "\n=== Channel readiness / launcher labels ===\n";
assert_eq('WhatsApp us', channel_launcher_label('whatsapp', true), 'WhatsApp launcher label');
assert_eq('Telegram us', channel_launcher_label('telegram', true), 'Telegram launcher label');
assert_eq('Continue on Telegram', channel_continue_label('telegram'), 'Telegram continue label');
assert_eq('Opening Telegram...', channel_success_label('telegram'), 'Telegram success label');
assert_eq('Phone required before Telegram', channel_force_phone_label('telegram'), 'Telegram force-phone label');

$emptyWidgetErrors = validate_widget_data([
    'website_domain' => 'example.com',
    'custom_url' => '',
    'greeting_capture_phone' => 0,
    'channel_mode' => 'telegram_only',
]);
assert_true($emptyWidgetErrors === [], 'Telegram-only widget validates without destinations');

$bothModeErrors = validate_widget_data([
    'website_domain' => 'example.com',
    'custom_url' => '',
    'greeting_capture_phone' => 0,
    'channel_mode' => 'both',
]);
assert_true($bothModeErrors === [], 'Both-channel widget validates without destinations');

$jsI18n = dashboard_js_i18n();
assert_true(isset($jsI18n['telegram.add_destination']), 'JS i18n includes telegram.add_destination');
assert_eq('Add Telegram Destination', $jsI18n['telegram.add_destination'], 'JS i18n add destination label');
assert_true(isset($jsI18n['telegram.error.save_failed']), 'JS i18n includes save_failed');
assert_true(str_contains(telegram_icon_svg(), 'viewBox="0 0 24 24"'), 'Telegram icon SVG present');
assert_true(str_contains(file_get_contents(dirname(__DIR__) . '/assets/css/widget.css'), '#0088cc')
    || str_contains(file_get_contents(dirname(__DIR__) . '/assets/css/widget.css'), '#0088CC'),
    'Widget CSS uses Telegram brand blue');

$widgetFormSource = file_get_contents(dirname(__DIR__) . '/includes/widget-form.php');
assert_true(
    (bool) preg_match('/<\/form>\s*<\?php\s*\/\/ Render Telegram modal/s', $widgetFormSource),
    'Widget form reopens PHP before Telegram modal include'
);
assert_true(
    !preg_match('/<\/form>\s*\/\/ Render Telegram modal/s', $widgetFormSource),
    'Widget form does not print Telegram modal PHP as plain text'
);

echo "\n=== Consent notice + Telegram styles ===\n";
$defaults = default_widget_data();
assert_eq(0, (int) ($defaults['consent_notice_enabled'] ?? 1), 'Consent notice defaults OFF');
assert_eq('tg-compact', (string) ($defaults['telegram_desktop_style'] ?? ''), 'Telegram desktop style defaults to tg-compact');
assert_eq('tg-compact', (string) ($defaults['telegram_mobile_style'] ?? ''), 'Telegram mobile style defaults to tg-compact');
assert_eq('tg-compact', default_telegram_widget_style(), 'Default Telegram style helper returns tg-compact');
assert_true(array_key_exists('tg-compact', telegram_widget_styles()), 'Telegram catalog includes compact style');
assert_true(array_key_exists('tg-icon', telegram_widget_styles()), 'Telegram catalog includes icon style');
assert_true(array_key_exists('tg-pill', telegram_widget_styles()), 'Telegram catalog includes pill style');
assert_true(array_key_exists('tg-reveal', telegram_widget_styles()), 'Telegram catalog includes reveal-on-hover style');
assert_true(!array_key_exists('style-9-left-hover', telegram_widget_styles()), 'Telegram styles exclude Style 9');
assert_true(!array_key_exists('style-4', telegram_widget_styles()), 'Telegram styles exclude WhatsApp Style 4');
assert_eq('tg-reveal', sanitize_telegram_widget_style('style-9-left-hover'), 'Style 9 sanitizes to tg-reveal');
assert_eq('tg-reveal', sanitize_telegram_widget_style('reveal_label_hover'), 'Semantic alias maps to tg-reveal');
assert_eq('tg-compact', sanitize_telegram_widget_style('style-4'), 'Legacy style-4 sanitizes to tg-compact');
assert_eq('tg-icon', sanitize_telegram_widget_style('style-3'), 'Legacy icon style sanitizes to tg-icon');
assert_eq('tg-pill', sanitize_telegram_widget_style('style-8'), 'Legacy button style sanitizes to tg-pill');
assert_eq('tg-compact', sanitize_telegram_widget_style('unknown-style'), 'Unknown Telegram style falls back to tg-compact');
assert_eq('', widget_consent_notice_text(['consent_notice_enabled' => 0]), 'Consent text empty when disabled');
assert_true(widget_consent_notice_text(['consent_notice_enabled' => 1]) !== '', 'Consent text present when enabled');
assert_true(str_contains($widgetFormSource, 'name="consent_notice_enabled"'), 'Form includes consent notice toggle');
assert_true(str_contains($widgetFormSource, 'name="telegram_desktop_style"'), 'Form includes Telegram desktop style');
assert_true(str_contains($widgetFormSource, 'data-channel-style-panels'), 'Form includes channel style panels');
assert_true(str_contains($widgetFormSource, 'channel-style-tab'), 'Form uses modern segmented style tabs');
assert_true(
    !str_contains($widgetFormSource, 'badge.recommended')
    && !str_contains($widgetFormSource, 'style-recommended-badge')
    && !str_contains($widgetFormSource, 'is-recommended'),
    'Telegram style UI has no Recommended badge references'
);
assert_true(
    is_file(dirname(__DIR__) . '/migrations/020_consent_notice_and_telegram_styles.sql'),
    'Migration 020 exists'
);
$widgetPhp = file_get_contents(dirname(__DIR__) . '/widget.php');
assert_true(str_contains($widgetPhp, 'data-channel-shell="telegram"'), 'Public widget renders Telegram style shell');
assert_true(str_contains($widgetPhp, 'widget_consent_notice_text'), 'Public widget uses consent helper');
assert_true(
    str_contains($widgetPhp, "channelMode === 'both'")
        || str_contains($widgetPhp, '$channelMode === \'both\''),
    'Full-width greeting CTA is gated on both-channel mode'
);
assert_true(str_contains($widgetPhp, 'data-greeting-cta'), 'Public widget markup supports full-width greeting CTA');
assert_true(str_contains($widgetPhp, 'has-channel-cta'), 'Greeting marks both-channel CTA mode');
assert_true(str_contains($widgetPhp, '<form class="ctcw-greeting-form"'), 'Phone capture uses a form for shared submit');
$dashboardJsPreview = file_get_contents(dirname(__DIR__) . '/assets/js/dashboard.js');
assert_true(
    str_contains($dashboardJsPreview, "channelMode === 'both'")
        || str_contains($dashboardJsPreview, 'channelMode === "both"'),
    'Live preview gates full CTA on both-channel mode'
);
$dashboardJs = file_get_contents(dirname(__DIR__) . '/assets/js/dashboard.js');
assert_true(str_contains($dashboardJs, 'consentNoticeEnabled'), 'Live preview tracks consent toggle');
assert_true(str_contains($dashboardJs, 'telegramDesktopStyle'), 'Live preview tracks Telegram style');
assert_true(str_contains($dashboardJs, 'tg-compact'), 'Live preview normalizes to Telegram-native styles');
assert_true(str_contains($dashboardJs, 'tg-reveal'), 'Live preview supports Telegram reveal style');
assert_true(str_contains($dashboardJs, 'aria-selected'), 'Style tabs manage aria-selected');
$enLang = file_get_contents(dirname(__DIR__) . '/languages/en.php');
$zhLang = file_get_contents(dirname(__DIR__) . '/languages/zh-CN.php');
assert_true(str_contains($enLang, "'widget_style.tg-compact'"), 'EN includes tg-compact label');
assert_true(str_contains($zhLang, "'widget_style.tg-compact'"), 'ZH includes tg-compact label');
assert_true(str_contains($enLang, "'widget_style.tg-reveal'"), 'EN includes tg-reveal label');
assert_true(str_contains($zhLang, "'widget_style.tg-reveal'"), 'ZH includes tg-reveal label');
assert_true(!str_contains($enLang, "'badge.recommended'"), 'EN Recommended badge key removed');
assert_true(!str_contains($zhLang, "'badge.recommended'"), 'ZH Recommended badge key removed');
assert_true(!str_contains($enLang, 'is recommended'), 'EN helper no longer says recommended');
assert_true(str_contains($enLang, "'widget.open_whatsapp'"), 'EN includes Open WhatsApp');
assert_true(str_contains($widgetCss = file_get_contents(dirname(__DIR__) . '/assets/css/widget.css'), 'data-active-channel="telegram"'), 'Widget CSS has Telegram active-channel tokens');
assert_true(str_contains($widgetCss, '.tg-compact'), 'Widget CSS defines tg-compact launcher');
assert_true(str_contains($widgetCss, '.tg-reveal'), 'Widget CSS defines tg-reveal launcher');
assert_true(str_contains($widgetCss, 'hover: none') || str_contains($widgetCss, 'pointer: coarse'), 'Widget CSS has touch hover fallback for reveal');
assert_true(str_contains($widgetCss, '.ctcw-greeting-cta'), 'Widget CSS defines full-width greeting CTA');
assert_true(str_contains($widgetCss, ':not(.has-channel-cta)'), 'Widget CSS collapses CTA space outside both mode');
assert_true(str_contains($widgetCss, '--ctcw-channel'), 'Widget CSS defines channel color tokens');
$widgetJsSource = file_get_contents(dirname(__DIR__) . '/assets/js/widget.js');
assert_true(str_contains($widgetJsSource, 'submitLeadForActiveChannel'), 'Widget JS shares one submitLeadForActiveChannel helper');
assert_true(str_contains($widgetJsSource, 'isSubmittingLead'), 'Widget JS guards against duplicate submissions');
assert_true(
    str_contains($widgetJsSource, "greetingForm.addEventListener('submit'")
        || str_contains($widgetJsSource, 'greetingForm.addEventListener("submit"'),
    'Widget JS binds shared form submit handler'
);
assert_true(str_contains($widgetCss, '#0077b5') || str_contains($widgetCss, '#0077B5'), 'Widget CSS uses Telegram hover #0077B5');
$styleCss = file_get_contents(dirname(__DIR__) . '/assets/css/style.css');
assert_true(str_contains($styleCss, '.channel-style-tab'), 'Admin CSS defines segmented style tabs');
assert_true(!str_contains($styleCss, 'style-recommended-badge'), 'Admin CSS Recommended badge styles removed');

$embedJs = file_get_contents(dirname(__DIR__) . '/embed.js.php');
assert_true(!preg_match('/height\s*=\s*255/', $embedJs), 'Embed script does not force greeting height to 255');
assert_true(str_contains($embedJs, 'availableWidth'), 'Embed script sends availableWidth to iframe');
assert_true(str_contains($embedJs, 'availableHeight'), 'Embed script sends availableHeight to iframe');
$widgetJs = file_get_contents(dirname(__DIR__) . '/assets/js/widget.js');
assert_true(str_contains($widgetJs, '--ctcw-available-height'), 'Widget JS sets available height CSS var');
assert_true(str_contains($widgetJs, 'lastPostedSize'), 'Widget JS debounces duplicate size posts');

$widgetCss = file_get_contents(dirname(__DIR__) . '/assets/css/widget.css');
assert_true(
    !preg_match('/\.ctcw-launcher\.is-dialog-open\s*\{[^}]*outline:\s*2px\s+solid\s+rgba\(255,\s*255,\s*255/s', $widgetCss),
    'Launcher dialog-open state no longer uses white rectangular outline'
);
assert_true(
    str_contains($widgetCss, '.ctcw-launcher:focus-visible')
        || str_contains($widgetCss, '.ctcw-widget:focus-visible'),
    'Launcher keyboard focus-visible styles are present'
);
assert_true(
    str_contains($widgetCss, '--ctcw-visual-edge-allowance')
        || str_contains($widgetCss, 'ctcw-visual-edge-allowance'),
    'Widget CSS defines visual edge allowance for hover/focus'
);
assert_true(
    str_contains($widgetJs, 'VISUAL_EDGE_ALLOWANCE'),
    'Widget JS applies visual edge allowance when measuring'
);

echo "\n=== Migration runner smoke (no DB required for help) ===\n";
$migrateHelp = [];
exec('php ' . escapeshellarg(dirname(__DIR__) . '/migrate.php') . ' --help 2>&1', $migrateHelp, $helpCode);
assert_eq(0, $helpCode, 'migrate.php --help exits 0');
assert_true(str_contains(implode("\n", $migrateHelp), 'Usage:'), 'migrate.php prints usage');

echo "\n=== Optional database-backed checks ===\n";
try {
    $pdo = db();
    $pdo->query('SELECT 1');
    echo "Database reachable. Running schema-aware checks...\n";

    $hasChannels = database_table_exists('widget_channels');
    $hasDestinations = database_table_exists('channel_destinations');
    assert_true(true, 'DB connection ok');

    if ($hasChannels && $hasDestinations) {
        assert_true(channel_schema_ready(), 'Channel schema ready');
    } else {
        echo "[SKIP] Channel tables not present yet — run migrations locally.\n";
    }
} catch (Throwable $exception) {
    echo "[SKIP] Database not available in this environment (" . $exception->getMessage() . ")\n";
}

echo "\nPassed: {$passed}\nFailed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
