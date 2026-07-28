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
