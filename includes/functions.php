<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $posted = $_POST['csrf_token'] ?? '';
    if (!is_string($posted) || !hash_equals(csrf_token(), $posted)) {
        http_response_code(419);
        exit('Invalid security token. Please go back and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function selected(string $actual, string $expected): string
{
    return $actual === $expected ? ' selected' : '';
}

function checked($value): string
{
    return !empty($value) ? ' checked' : '';
}

function normalize_domain(string $domain): string
{
    $domain = trim(strtolower($domain));
    if ($domain === '') {
        return '';
    }

    if (!preg_match('~^https?://~i', $domain)) {
        $domain = 'https://' . $domain;
    }

    $host = parse_url($domain, PHP_URL_HOST);
    if (!$host) {
        return '';
    }

    $host = preg_replace('/^www\./', '', $host);
    return rtrim((string) $host, '.');
}

function is_valid_domain(string $domain): bool
{
    if ($domain === '') {
        return false;
    }

    return (bool) preg_match('/^(?!-)([a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i', $domain)
        || $domain === 'localhost';
}

function domain_matches_referrer(string $allowedDomain, ?string $referrer): bool
{
    if ($referrer === null || trim($referrer) === '') {
        return true;
    }

    $referrerDomain = normalize_domain($referrer);
    if ($referrerDomain === '') {
        return false;
    }

    $allowedDomain = normalize_domain($allowedDomain);
    return $referrerDomain === $allowedDomain || str_ends_with($referrerDomain, '.' . $allowedDomain);
}

function clean_phone_number(string $number): string
{
    return preg_replace('/\D+/', '', $number) ?? '';
}

function validate_phone_number(string $number): bool
{
    $clean = clean_phone_number($number);
    return strlen($clean) >= 7 && strlen($clean) <= 15;
}

function widget_styles(): array
{
    return [
        'style-1' => 'Style-1: Theme Button',
        'style-2' => 'Style-2: Green Square Icon',
        'style-3' => 'Style-3: Icon',
        'style-3-large' => 'Style-3 Extend: Large Icon',
        'style-4' => 'Style-4: Chip Cylindrical',
        'style-5' => 'Style-5: Image on Hover Content Box',
        'style-6' => 'Style-6: Plain Text',
        'style-7' => 'Style-7: Icon with Padding',
        'style-7-extend' => 'Style-7 Extend: Icon on Hover Extend',
        'style-8' => 'Style-8: Button',
    ];
}

function country_codes(): array
{
    return [
        '+60' => 'Malaysia (+60)',
        '+65' => 'Singapore (+65)',
        '+62' => 'Indonesia (+62)',
        '+66' => 'Thailand (+66)',
        '+63' => 'Philippines (+63)',
        '+84' => 'Vietnam (+84)',
        '+91' => 'India (+91)',
        '+44' => 'United Kingdom (+44)',
        '+1' => 'United States / Canada (+1)',
        '+61' => 'Australia (+61)',
    ];
}

function default_business_hours(): array
{
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $hours = [];
    foreach ($days as $day) {
        $hours[$day] = [
            'enabled' => in_array($day, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], true),
            'open' => '09:00',
            'close' => '18:00',
        ];
    }

    return $hours;
}

function default_widget_data(): array
{
    return [
        'widget_name' => 'My WhatsApp Widget',
        'website_domain' => '',
        'whatsapp_country_code' => '+60',
        'whatsapp_number' => '',
        'use_random_numbers' => 0,
        'random_numbers_json' => '[]',
        'prefilled_message' => "Hello {site}\nLike to know more information about {title}, {url}",
        'call_to_action' => 'WhatsApp us',
        'desktop_style' => 'style-1',
        'mobile_style' => 'style-1',
        'desktop_position_type' => 'fixed',
        'mobile_position_type' => 'fixed',
        'desktop_vertical_position_type' => 'bottom',
        'desktop_vertical_position_value' => '25px',
        'desktop_horizontal_position_type' => 'right',
        'desktop_horizontal_position_value' => '25px',
        'mobile_vertical_position_type' => 'bottom',
        'mobile_vertical_position_value' => '25px',
        'mobile_horizontal_position_type' => 'right',
        'mobile_horizontal_position_value' => '25px',
        'same_mobile_desktop_settings' => 1,
        'desktop_open_link_type' => 'new_tab',
        'mobile_open_link_type' => 'new_tab',
        'desktop_url_structure' => 'wa_me',
        'mobile_url_structure' => 'wa_me',
        'custom_url' => '',
        'show_desktop' => 1,
        'show_mobile' => 1,
        'show_global' => 1,
        'business_hours_mode' => 'always_open',
        'business_hours_json' => json_encode(default_business_hours()),
        'offline_message' => 'We are currently offline. Please leave us a message later.',
        'greeting_enabled' => 1,
        'greeting_title' => 'Need help?',
        'greeting_message' => 'Chat with us on WhatsApp.',
        'greeting_delay_seconds' => 2,
        'custom_css' => '',
        'custom_script_head' => '',
        'custom_script_body' => '',
        'custom_script_footer' => '',
    ];
}

function generate_public_key(): string
{
    return bin2hex(random_bytes(24));
}

function css_unit_value(string $value, string $fallback): string
{
    $value = trim($value);
    if (preg_match('/^-?\d+(\.\d+)?(px|%|rem|em|vh|vw)$/', $value)) {
        return $value;
    }

    return $fallback;
}

function enum_value(string $value, array $allowed, string $fallback): string
{
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function post_checkbox(string $name): int
{
    return isset($_POST[$name]) ? 1 : 0;
}

function sanitize_widget_input(array $post): array
{
    $defaults = default_widget_data();
    $websiteDomain = normalize_domain((string) ($post['website_domain'] ?? ''));
    $countryCode = (string) ($post['whatsapp_country_code'] ?? '+60');
    if (!array_key_exists($countryCode, country_codes())) {
        $countryCode = '+60';
    }

    $randomRows = $post['random_numbers'] ?? [];
    $randomNumbers = [];
    if (is_array($randomRows)) {
        foreach ($randomRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowCountry = (string) ($row['country_code'] ?? $countryCode);
            if (!array_key_exists($rowCountry, country_codes())) {
                $rowCountry = $countryCode;
            }
            $rowNumber = clean_phone_number((string) ($row['number'] ?? ''));
            if ($rowNumber !== '' && validate_phone_number($rowNumber)) {
                $randomNumbers[] = [
                    'country_code' => $rowCountry,
                    'number' => $rowNumber,
                ];
            }
        }
    }

    $businessRows = $post['business_hours'] ?? [];
    $businessHours = default_business_hours();
    if (is_array($businessRows)) {
        foreach ($businessHours as $day => $settings) {
            $row = is_array($businessRows[$day] ?? null) ? $businessRows[$day] : [];
            $businessHours[$day] = [
                'enabled' => isset($row['enabled']),
                'open' => preg_match('/^\d{2}:\d{2}$/', (string) ($row['open'] ?? '')) ? (string) $row['open'] : $settings['open'],
                'close' => preg_match('/^\d{2}:\d{2}$/', (string) ($row['close'] ?? '')) ? (string) $row['close'] : $settings['close'],
            ];
        }
    }

    $sameMobile = post_checkbox('same_mobile_desktop_settings');

    $data = [
        'widget_name' => trim((string) ($post['widget_name'] ?? $defaults['widget_name'])),
        'website_domain' => $websiteDomain,
        'whatsapp_country_code' => $countryCode,
        'whatsapp_number' => clean_phone_number((string) ($post['whatsapp_number'] ?? '')),
        'use_random_numbers' => post_checkbox('use_random_numbers'),
        'random_numbers_json' => json_encode($randomNumbers),
        'prefilled_message' => trim((string) ($post['prefilled_message'] ?? $defaults['prefilled_message'])),
        'call_to_action' => trim((string) ($post['call_to_action'] ?? $defaults['call_to_action'])),
        'desktop_style' => enum_value((string) ($post['desktop_style'] ?? 'style-1'), array_keys(widget_styles()), 'style-1'),
        'mobile_style' => enum_value((string) ($post['mobile_style'] ?? 'style-1'), array_keys(widget_styles()), 'style-1'),
        'desktop_position_type' => enum_value((string) ($post['desktop_position_type'] ?? 'fixed'), ['fixed', 'absolute'], 'fixed'),
        'mobile_position_type' => enum_value((string) ($post['mobile_position_type'] ?? 'fixed'), ['fixed', 'absolute'], 'fixed'),
        'desktop_vertical_position_type' => enum_value((string) ($post['desktop_vertical_position_type'] ?? 'bottom'), ['top', 'bottom'], 'bottom'),
        'desktop_vertical_position_value' => css_unit_value((string) ($post['desktop_vertical_position_value'] ?? '25px'), '25px'),
        'desktop_horizontal_position_type' => enum_value((string) ($post['desktop_horizontal_position_type'] ?? 'right'), ['left', 'right'], 'right'),
        'desktop_horizontal_position_value' => css_unit_value((string) ($post['desktop_horizontal_position_value'] ?? '25px'), '25px'),
        'mobile_vertical_position_type' => enum_value((string) ($post['mobile_vertical_position_type'] ?? 'bottom'), ['top', 'bottom'], 'bottom'),
        'mobile_vertical_position_value' => css_unit_value((string) ($post['mobile_vertical_position_value'] ?? '25px'), '25px'),
        'mobile_horizontal_position_type' => enum_value((string) ($post['mobile_horizontal_position_type'] ?? 'right'), ['left', 'right'], 'right'),
        'mobile_horizontal_position_value' => css_unit_value((string) ($post['mobile_horizontal_position_value'] ?? '25px'), '25px'),
        'same_mobile_desktop_settings' => $sameMobile,
        'desktop_open_link_type' => enum_value((string) ($post['desktop_open_link_type'] ?? 'new_tab'), ['new_tab', 'same_tab'], 'new_tab'),
        'mobile_open_link_type' => enum_value((string) ($post['mobile_open_link_type'] ?? 'new_tab'), ['new_tab', 'same_tab'], 'new_tab'),
        'desktop_url_structure' => enum_value((string) ($post['desktop_url_structure'] ?? 'wa_me'), ['wa_me', 'web_whatsapp', 'whatsapp_app', 'custom'], 'wa_me'),
        'mobile_url_structure' => enum_value((string) ($post['mobile_url_structure'] ?? 'wa_me'), ['wa_me', 'whatsapp_app', 'custom'], 'wa_me'),
        'custom_url' => trim((string) ($post['custom_url'] ?? '')),
        'show_desktop' => post_checkbox('show_desktop'),
        'show_mobile' => post_checkbox('show_mobile'),
        'show_global' => post_checkbox('show_global'),
        'business_hours_mode' => enum_value((string) ($post['business_hours_mode'] ?? 'always_open'), ['always_open', 'always_closed', 'custom'], 'always_open'),
        'business_hours_json' => json_encode($businessHours),
        'offline_message' => trim((string) ($post['offline_message'] ?? $defaults['offline_message'])),
        'greeting_enabled' => post_checkbox('greeting_enabled'),
        'greeting_title' => trim((string) ($post['greeting_title'] ?? $defaults['greeting_title'])),
        'greeting_message' => trim((string) ($post['greeting_message'] ?? $defaults['greeting_message'])),
        'greeting_delay_seconds' => max(0, min(120, (int) ($post['greeting_delay_seconds'] ?? 2))),
        'custom_css' => strip_php_tags((string) ($post['custom_css'] ?? '')),
        'custom_script_head' => strip_php_tags((string) ($post['custom_script_head'] ?? '')),
        'custom_script_body' => strip_php_tags((string) ($post['custom_script_body'] ?? '')),
        'custom_script_footer' => strip_php_tags((string) ($post['custom_script_footer'] ?? '')),
    ];

    if (isset($post['reset_custom_code'])) {
        $data['custom_css'] = '';
        $data['custom_script_head'] = '';
        $data['custom_script_body'] = '';
        $data['custom_script_footer'] = '';
    }

    if ($data['widget_name'] === '') {
        $data['widget_name'] = $defaults['widget_name'];
    }
    if ($data['call_to_action'] === '') {
        $data['call_to_action'] = $defaults['call_to_action'];
    }
    if ($sameMobile) {
        $data['mobile_position_type'] = $data['desktop_position_type'];
        $data['mobile_vertical_position_type'] = $data['desktop_vertical_position_type'];
        $data['mobile_vertical_position_value'] = $data['desktop_vertical_position_value'];
        $data['mobile_horizontal_position_type'] = $data['desktop_horizontal_position_type'];
        $data['mobile_horizontal_position_value'] = $data['desktop_horizontal_position_value'];
    }

    return $data;
}

function strip_php_tags(string $code): string
{
    return str_ireplace(['<?php', '<?=', '<?', '?>'], '', $code);
}

function validate_widget_data(array $data): array
{
    $errors = [];

    if (!is_valid_domain($data['website_domain'])) {
        $errors[] = 'Please enter a valid website domain.';
    }
    if (!validate_phone_number($data['whatsapp_number'])) {
        $errors[] = 'Please enter a valid WhatsApp number.';
    }
    if ($data['custom_url'] !== '' && !filter_var($data['custom_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Custom URL must be a valid full URL.';
    }

    return $errors;
}

function insert_widget(int $userId, array $data): int
{
    $data['user_id'] = $userId;
    $data['public_key'] = generate_public_key();

    $columns = array_keys($data);
    $placeholders = array_map(static fn ($column) => ':' . $column, $columns);
    $sql = 'INSERT INTO widgets (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = db()->prepare($sql);
    $stmt->execute($data);

    return (int) db()->lastInsertId();
}

function update_widget(int $widgetId, int $userId, array $data): void
{
    $assignments = array_map(static fn ($column) => $column . ' = :' . $column, array_keys($data));
    $data['id'] = $widgetId;
    $data['user_id'] = $userId;

    $sql = 'UPDATE widgets SET ' . implode(', ', $assignments) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id';
    $stmt = db()->prepare($sql);
    $stmt->execute($data);
}

function find_user_widget(int $widgetId, int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM widgets WHERE id = :id AND user_id = :user_id LIMIT 1');
    $stmt->execute(['id' => $widgetId, 'user_id' => $userId]);
    $widget = $stmt->fetch();
    return $widget ?: null;
}

function find_public_widget(int $widgetId, string $publicKey): ?array
{
    $stmt = db()->prepare('SELECT * FROM widgets WHERE id = :id AND public_key = :public_key LIMIT 1');
    $stmt->execute(['id' => $widgetId, 'public_key' => $publicKey]);
    $widget = $stmt->fetch();
    return $widget ?: null;
}

function widget_status_label(array $widget): string
{
    if (empty($widget['show_global'])) {
        return 'Hidden globally';
    }
    if (($widget['business_hours_mode'] ?? '') === 'always_closed') {
        return 'Offline';
    }
    return 'Active';
}

function embed_code(array $widget): string
{
    $src = SYSTEM_BASE_URL . '/widget.php?id=' . rawurlencode((string) $widget['id']) . '&key=' . rawurlencode((string) $widget['public_key']);
    return '<iframe src="' . $src . '" style="border:0; position:fixed; bottom:0; right:0; width:100%; height:100%; pointer-events:none; z-index:999999;" allowtransparency="true"></iframe>';
}

function json_for_html($value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function is_widget_online(array $widget): bool
{
    $mode = $widget['business_hours_mode'] ?? 'always_open';
    if ($mode === 'always_open') {
        return true;
    }
    if ($mode === 'always_closed') {
        return false;
    }

    $hours = json_decode((string) ($widget['business_hours_json'] ?? ''), true);
    if (!is_array($hours)) {
        return true;
    }

    $day = strtolower(date('l'));
    $today = $hours[$day] ?? null;
    if (!is_array($today) || empty($today['enabled'])) {
        return false;
    }

    $now = date('H:i');
    return $now >= (string) ($today['open'] ?? '00:00') && $now <= (string) ($today['close'] ?? '23:59');
}

function whatsapp_icon_svg(): string
{
    return '<svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.04 3.2c-7.02 0-12.72 5.68-12.72 12.68 0 2.24.6 4.43 1.72 6.35L3.2 28.8l6.74-1.77a12.76 12.76 0 0 0 6.1 1.55h.01c7.01 0 12.72-5.68 12.72-12.68S23.06 3.2 16.04 3.2Zm0 23.24h-.01c-1.94 0-3.85-.52-5.52-1.5l-.4-.24-4 .96 1.07-3.9-.26-.4a10.48 10.48 0 0 1-1.6-5.48c0-5.82 4.8-10.55 10.72-10.55 2.86 0 5.56 1.1 7.58 3.1a10.45 10.45 0 0 1 3.15 7.47c0 5.82-4.8 10.54-10.73 10.54Zm5.88-7.9c-.32-.16-1.9-.94-2.2-1.05-.3-.11-.52-.16-.74.16-.22.32-.85 1.05-1.04 1.27-.19.22-.38.24-.7.08-.32-.16-1.36-.5-2.6-1.6-.96-.85-1.6-1.9-1.79-2.22-.19-.32-.02-.5.14-.66.15-.15.32-.38.48-.56.16-.19.22-.32.32-.54.11-.22.05-.4-.03-.56-.08-.16-.74-1.78-1.02-2.44-.27-.64-.54-.55-.74-.56h-.63c-.22 0-.56.08-.85.4-.3.32-1.12 1.1-1.12 2.68s1.15 3.1 1.31 3.31c.16.22 2.27 3.45 5.5 4.84.77.33 1.37.53 1.84.68.77.24 1.47.2 2.03.12.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.4.19-1.53-.08-.13-.3-.21-.62-.37Z"/></svg>';
}
