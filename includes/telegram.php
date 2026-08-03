<?php
declare(strict_types=1);

const TELEGRAM_DESTINATION_USERNAME = 'username';
const TELEGRAM_DESTINATION_BOT = 'bot';
const TELEGRAM_DESTINATION_GROUP = 'group';
const TELEGRAM_DESTINATION_CHANNEL = 'channel';

/**
 * @return list<string>
 */
function telegram_destination_types(): array
{
    return [
        TELEGRAM_DESTINATION_USERNAME,
        TELEGRAM_DESTINATION_BOT,
        TELEGRAM_DESTINATION_GROUP,
        TELEGRAM_DESTINATION_CHANNEL,
    ];
}

function normalize_telegram_destination_type(string $type): ?string
{
    $type = strtolower(trim($type));
    return in_array($type, telegram_destination_types(), true) ? $type : null;
}

/**
 * @return array{ok: bool, value?: string, error?: string}
 */
function normalize_telegram_username(string $raw, string $kind = 'username'): array
{
    $value = trim($raw);
    if ($value === '') {
        return ['ok' => false, 'error' => t('telegram.error.enter_username')];
    }

    if (preg_match('#^(https?:)?//#i', $value) || str_contains($value, 't.me/') || str_contains($value, 'telegram.')) {
        return ['ok' => false, 'error' => t('telegram.error.username_not_url')];
    }

    if (str_contains($value, ' ')) {
        return ['ok' => false, 'error' => t('telegram.error.username_spaces')];
    }

    if (str_starts_with($value, '@')) {
        $value = substr($value, 1);
    }

    $value = trim($value);
    if ($value === '') {
        return ['ok' => false, 'error' => t('telegram.error.enter_username')];
    }

    // Telegram usernames: 5-32 chars, Latin letters, digits, underscore.
    if (!preg_match('/^[A-Za-z0-9_]{5,32}$/', $value)) {
        return ['ok' => false, 'error' => t('telegram.error.username_format')];
    }

    if ($kind === TELEGRAM_DESTINATION_BOT && !preg_match('/bot$/i', $value)) {
        return ['ok' => false, 'error' => t('telegram.error.bot_suffix')];
    }

    return ['ok' => true, 'value' => $value];
}

/**
 * @return array{ok: bool, value?: string|null, error?: string}
 */
function normalize_telegram_bot_start_parameter(?string $raw): array
{
    $value = trim((string) $raw);
    if ($value === '') {
        return ['ok' => true, 'value' => null];
    }

    if (strlen($value) > 64) {
        return ['ok' => false, 'error' => t('telegram.error.start_param_length')];
    }

    // Official deep-link start parameter: up to 64 base64url characters.
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
        return ['ok' => false, 'error' => t('telegram.error.start_param_chars')];
    }

    if (str_contains($value, '?') || str_contains($value, '&') || str_contains($value, '=')) {
        return ['ok' => false, 'error' => t('telegram.error.start_param_chars')];
    }

    return ['ok' => true, 'value' => $value];
}

/**
 * @return array{ok: bool, value?: string, error?: string}
 */
function normalize_telegram_link(string $raw): array
{
    $value = trim($raw);
    if ($value === '') {
        return ['ok' => false, 'error' => t('telegram.error.enter_link')];
    }

    $lower = strtolower($value);
    foreach (['javascript:', 'data:', 'file:', 'vbscript:'] as $dangerous) {
        if (str_starts_with($lower, $dangerous)) {
            return ['ok' => false, 'error' => t('telegram.error.unsupported_link')];
        }
    }

    if (str_starts_with($value, '//')) {
        return ['ok' => false, 'error' => t('telegram.error.unsupported_link')];
    }

    if (!preg_match('#^https?://#i', $value)) {
        if (preg_match('#^(t\.me|telegram\.me|telegram\.dog)/#i', $value)) {
            $value = 'https://' . $value;
        } else {
            return ['ok' => false, 'error' => t('telegram.error.enter_supported_link')];
        }
    }

    $parts = parse_url($value);
    if ($parts === false || empty($parts['host'])) {
        return ['ok' => false, 'error' => t('telegram.error.enter_supported_link')];
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if ($scheme !== 'https') {
        return ['ok' => false, 'error' => t('telegram.error.https_required')];
    }

    if (isset($parts['user']) || isset($parts['pass'])) {
        return ['ok' => false, 'error' => t('telegram.error.unsupported_link')];
    }

    if (isset($parts['port'])) {
        return ['ok' => false, 'error' => t('telegram.error.unsupported_link')];
    }

    $host = strtolower((string) $parts['host']);
    $allowedHosts = ['t.me', 'telegram.me', 'telegram.dog'];
    if (!in_array($host, $allowedHosts, true)) {
        return ['ok' => false, 'error' => t('telegram.error.external_domain')];
    }

    $path = (string) ($parts['path'] ?? '');
    $path = '/' . ltrim($path, '/');
    if ($path === '/' || $path === '') {
        return ['ok' => false, 'error' => t('telegram.error.enter_supported_link')];
    }

    // Public username / public group / public channel: /name
    // Invite links: /+hash or /joinchat/hash
    $isUsernamePath = (bool) preg_match('#^/[A-Za-z0-9_]{5,32}/?$#', $path);
    $isPlusInvite = (bool) preg_match('#^/\+[A-Za-z0-9_-]{1,128}/?$#', $path);
    $isJoinChat = (bool) preg_match('#^/joinchat/[A-Za-z0-9_-]{1,128}/?$#', $path);

    if (!$isUsernamePath && !$isPlusInvite && !$isJoinChat) {
        return ['ok' => false, 'error' => t('telegram.error.enter_supported_link')];
    }

    // Reject arbitrary/unsafe query strings on group/channel invite/public links.
    if (!empty($parts['query'])) {
        parse_str((string) $parts['query'], $query);
        $allowedQueryKeys = [];
        foreach (array_keys($query) as $key) {
            if (!in_array((string) $key, $allowedQueryKeys, true)) {
                return ['ok' => false, 'error' => t('telegram.error.unsupported_query')];
            }
        }
    }

    if (!empty($parts['fragment'])) {
        return ['ok' => false, 'error' => t('telegram.error.unsupported_link')];
    }

    $canonicalPath = rtrim($path, '/');
    $canonical = 'https://t.me' . $canonicalPath;

    return ['ok' => true, 'value' => $canonical];
}

/**
 * Validate and normalize a Telegram destination for storage.
 *
 * @return array{ok: bool, destination_type?: string, destination_value?: string, bot_start_parameter?: ?string, error?: string, field?: string}
 */
function validate_telegram_destination_input(array $input): array
{
    $type = normalize_telegram_destination_type((string) ($input['destination_type'] ?? ''));
    if ($type === null) {
        return ['ok' => false, 'error' => t('telegram.error.destination_type'), 'field' => 'destination_type'];
    }

    $rawValue = (string) ($input['destination_value'] ?? '');
    $startParam = $input['bot_start_parameter'] ?? null;

    if ($type === TELEGRAM_DESTINATION_USERNAME || $type === TELEGRAM_DESTINATION_BOT) {
        $normalized = normalize_telegram_username($rawValue, $type);
        if (!$normalized['ok']) {
            return ['ok' => false, 'error' => $normalized['error'], 'field' => 'destination_value'];
        }

        $start = ['ok' => true, 'value' => null];
        if ($type === TELEGRAM_DESTINATION_BOT) {
            $start = normalize_telegram_bot_start_parameter(is_string($startParam) ? $startParam : null);
            if (!$start['ok']) {
                return ['ok' => false, 'error' => $start['error'], 'field' => 'bot_start_parameter'];
            }
        }

        return [
            'ok' => true,
            'destination_type' => $type,
            'destination_value' => $normalized['value'],
            'bot_start_parameter' => $start['value'],
        ];
    }

    $normalized = normalize_telegram_link($rawValue);
    if (!$normalized['ok']) {
        return ['ok' => false, 'error' => $normalized['error'], 'field' => 'destination_value'];
    }

    return [
        'ok' => true,
        'destination_type' => $type,
        'destination_value' => $normalized['value'],
        'bot_start_parameter' => null,
    ];
}

/**
 * Central server-side Telegram URL builder. Never trust browser-supplied final URLs.
 *
 * @param array{destination_type: string, destination_value: string, bot_start_parameter?: ?string} $destination
 * @return array{ok: bool, url?: string, fallback?: array{type: string, username: string}, error?: string}
 */
function build_telegram_redirect_url(array $destination): array
{
    $type = normalize_telegram_destination_type((string) ($destination['destination_type'] ?? ''));
    $value = trim((string) ($destination['destination_value'] ?? ''));

    if ($type === null || $value === '') {
        return ['ok' => false, 'error' => t('telegram.error.invalid_destination')];
    }

    if ($type === TELEGRAM_DESTINATION_USERNAME || $type === TELEGRAM_DESTINATION_BOT) {
        $normalized = normalize_telegram_username($value, $type === TELEGRAM_DESTINATION_BOT ? TELEGRAM_DESTINATION_BOT : 'username');
        if (!$normalized['ok']) {
            // Stored values are already normalized without @; allow direct use if format matches.
            if (!preg_match('/^[A-Za-z0-9_]{5,32}$/', $value)) {
                return ['ok' => false, 'error' => t('telegram.error.invalid_destination')];
            }
            $username = $value;
        } else {
            $username = $normalized['value'];
        }

        $url = 'https://t.me/' . rawurlencode($username);
        // rawurlencode is too aggressive for usernames (encodes underscore etc). Use validated value directly.
        $url = 'https://t.me/' . $username;

        if ($type === TELEGRAM_DESTINATION_BOT) {
            $start = normalize_telegram_bot_start_parameter(
                isset($destination['bot_start_parameter']) ? (string) $destination['bot_start_parameter'] : null
            );
            if (!$start['ok']) {
                return ['ok' => false, 'error' => $start['error']];
            }
            if ($start['value'] !== null) {
                $url .= '?start=' . rawurlencode($start['value']);
            }
        }

        return [
            'ok' => true,
            'url' => $url,
            'fallback' => [
                'type' => 'copy_username',
                'username' => $username,
            ],
        ];
    }

    $normalized = normalize_telegram_link($value);
    if (!$normalized['ok']) {
        // Accept already-canonical stored https://t.me/... values.
        if (!preg_match('#^https://t\.me/(?:[A-Za-z0-9_]{5,32}|\+[A-Za-z0-9_-]{1,128}|joinchat/[A-Za-z0-9_-]{1,128})$#', $value)) {
            return ['ok' => false, 'error' => t('telegram.error.invalid_destination')];
        }
        $url = $value;
    } else {
        $url = $normalized['value'];
    }

    return [
        'ok' => true,
        'url' => $url,
    ];
}

function format_telegram_destination_display(array $destination): string
{
    $type = (string) ($destination['destination_type'] ?? '');
    $value = (string) ($destination['destination_value'] ?? '');

    if ($type === TELEGRAM_DESTINATION_USERNAME || $type === TELEGRAM_DESTINATION_BOT) {
        return '@' . ltrim($value, '@');
    }

    return $value;
}
