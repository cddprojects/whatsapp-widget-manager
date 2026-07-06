<?php
declare(strict_types=1);

const SUPPORTED_LOCALES = ['en', 'zh-CN'];
const DEFAULT_LOCALE = 'en';
const LOCALE_COOKIE = 'ctcw_preferred_language';

/** @var array<string, string> */
$GLOBALS['ctcw_translations'] = [];

/** @var string */
$GLOBALS['ctcw_current_locale'] = DEFAULT_LOCALE;

function normalize_locale(?string $locale): string
{
    $locale = trim((string) $locale);

    return in_array($locale, SUPPORTED_LOCALES, true) ? $locale : DEFAULT_LOCALE;
}

function current_locale(): string
{
    return (string) ($GLOBALS['ctcw_current_locale'] ?? DEFAULT_LOCALE);
}

function html_lang(): string
{
    return current_locale() === 'zh-CN' ? 'zh-CN' : 'en';
}

function set_current_locale(string $locale): void
{
    $GLOBALS['ctcw_current_locale'] = normalize_locale($locale);
    load_translations($GLOBALS['ctcw_current_locale']);
}

function load_translations(string $locale): void
{
    static $loaded = [];

    $locale = normalize_locale($locale);
    if (isset($loaded[$locale])) {
        $GLOBALS['ctcw_translations'] = $loaded[$locale];

        return;
    }

    $path = __DIR__ . '/../languages/' . $locale . '.php';
    if (!is_file($path)) {
        $path = __DIR__ . '/../languages/en.php';
    }

    /** @var array<string, string> $translations */
    $translations = require $path;
    $loaded[$locale] = $translations;
    $GLOBALS['ctcw_translations'] = $translations;
}

function t(string $key, array $replace = []): string
{
    $translations = $GLOBALS['ctcw_translations'] ?? [];
    $text = $translations[$key] ?? $key;

    if ($replace === []) {
        return $text;
    }

    foreach ($replace as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }

    return $text;
}

function locale_label(string $locale): string
{
    return match (normalize_locale($locale)) {
        'zh-CN' => '中文',
        default => 'English',
    };
}

function set_preferred_language_cookie(string $locale): void
{
    $locale = normalize_locale($locale);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(LOCALE_COOKIE, $locale, [
        'expires' => time() + 31536000,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function safe_redirect_path(?string $path, string $fallback): string
{
    $path = trim((string) $path);
    if ($path === '' || str_contains($path, '://') || str_starts_with($path, '//')) {
        return $fallback;
    }

    return $path;
}

function current_request_path(): string
{
    $path = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php'));
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

    return $query !== '' ? $path . '?' . $query : $path;
}

function translate_user_status(string $status): string
{
    $key = 'status.' . $status;
    $translated = t($key);

    return $translated !== $key ? $translated : $status;
}

function translate_feature_status(bool $enabled): string
{
    return $enabled ? t('status.enabled') : t('status.disabled');
}

function translate_day(string $day): string
{
    $key = 'day.' . strtolower($day);
    $translated = t($key);

    return $translated !== $key ? $translated : ucfirst($day);
}

function translate_widget_style(string $styleKey): string
{
    $key = 'widget_style.' . $styleKey;
    $translated = t($key);

    return $translated !== $key ? $translated : $styleKey;
}

function bootstrap_locale(): void
{
    if (!empty($_SESSION['locale'])) {
        set_current_locale((string) $_SESSION['locale']);

        return;
    }

    if (!empty($_SESSION['user_id'])) {
        $stmt = db()->prepare('SELECT preferred_language FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        $preferred = $stmt->fetchColumn();
        if ($preferred !== false) {
            $_SESSION['locale'] = normalize_locale((string) $preferred);
            set_current_locale((string) $_SESSION['locale']);

            return;
        }
    }

    if (!empty($_COOKIE[LOCALE_COOKIE])) {
        set_current_locale((string) $_COOKIE[LOCALE_COOKIE]);

        return;
    }

    set_current_locale(DEFAULT_LOCALE);
}

function update_user_preferred_language(int $userId, string $locale): void
{
    $locale = normalize_locale($locale);
    $stmt = db()->prepare('UPDATE users SET preferred_language = :locale, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute(['locale' => $locale, 'id' => $userId]);
}

function dashboard_js_i18n(): array
{
    return [
        'phone.delete_confirm' => t('phone.delete_confirm'),
        'phone.delete_last_confirm' => t('phone.delete_last_confirm'),
        'phone.bulk_delete_confirm' => t('phone.bulk_delete_confirm'),
        'phone.bulk_delete_title' => t('phone.bulk_delete_title'),
        'phone.bulk_delete_button' => t('phone.bulk_delete_button'),
        'phone.selected_count' => t('phone.selected_count'),
        'phone.min_one_required' => t('phone.min_one_required'),
        'phone.min_one_form' => t('phone.min_one_form'),
        'custom_code.reset_confirm' => t('custom_code.reset_confirm'),
        'widget.delete_confirm' => t('widget.delete_confirm'),
        'embed.copied' => t('embed.copied'),
        'embed.copy_code' => t('embed.copy_code'),
        'password.weak' => t('password.weak'),
        'password.normal' => t('password.normal'),
        'password.strong' => t('password.strong'),
        'password.show' => t('password.show'),
        'password.hide' => t('password.hide'),
        'password.match_error' => t('password.match_error'),
        'password.min_length' => t('password.min_length'),
        'preview.label' => t('preview.label'),
        'preview.phone_required' => t('preview.phone_required'),
        'preview.default_cta' => t('preview.default_cta'),
        'preview.default_offline' => t('preview.default_offline'),
        'distribution.js_summary_round_robin' => t('distribution.js_summary_round_robin'),
        'distribution.js_summary_random' => t('distribution.js_summary_random'),
    ];
}

bootstrap_locale();
