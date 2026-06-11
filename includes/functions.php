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

function normalize_host_for_match(string $host): string
{
    $host = trim(strtolower($host));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    return rtrim($host, '.');
}

function referrer_host(?string $referrer): string
{
    if ($referrer === null || trim($referrer) === '') {
        return '';
    }

    $host = parse_url($referrer, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return '';
    }

    return normalize_host_for_match($host);
}

function is_valid_domain(string $domain): bool
{
    if ($domain === '') {
        return false;
    }

    return (bool) preg_match('/^(?!-)([a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i', $domain)
        || $domain === 'localhost';
}

function domain_matches_referrer(array $widget, ?string $referrer): bool
{
    if (empty($widget['domain_lock_enabled'])) {
        return true;
    }

    $referrerHost = referrer_host($referrer);
    if ($referrerHost === '') {
        return empty($widget['strict_domain_check']);
    }

    $allowedDomain = normalize_domain((string) ($widget['website_domain'] ?? ''));
    if ($allowedDomain === '') {
        return false;
    }

    if ($referrerHost === $allowedDomain) {
        return true;
    }

    if ($referrerHost === 'www.' . $allowedDomain) {
        return !empty($widget['allow_www']);
    }

    if (!empty($widget['allow_subdomains']) && str_ends_with($referrerHost, '.' . $allowedDomain)) {
        return true;
    }

    return false;
}

function csp_frame_ancestors(array $widget): string
{
    if (empty($widget['domain_lock_enabled'])) {
        return "frame-ancestors *";
    }

    $allowedDomain = normalize_domain((string) ($widget['website_domain'] ?? ''));
    if ($allowedDomain === '') {
        return "frame-ancestors 'self'";
    }

    $sources = ["'self'", 'https://' . $allowedDomain, 'http://' . $allowedDomain];

    if (!empty($widget['allow_www'])) {
        $sources[] = 'https://www.' . $allowedDomain;
        $sources[] = 'http://www.' . $allowedDomain;
    }

    if (!empty($widget['allow_subdomains'])) {
        $sources[] = 'https://*.' . $allowedDomain;
        $sources[] = 'http://*.' . $allowedDomain;
    }

    return 'frame-ancestors ' . implode(' ', array_values(array_unique($sources)));
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

function country_code_options(): array
{
    $rows = [
        ['🇲🇾', '+60', 'Malaysia'],
        ['🇦🇫', '+93', 'Afghanistan'],
        ['🇦🇱', '+355', 'Albania'],
        ['🇩🇿', '+213', 'Algeria'],
        ['🇦🇸', '+1684', 'American Samoa'],
        ['🇦🇩', '+376', 'Andorra'],
        ['🇦🇴', '+244', 'Angola'],
        ['🇦🇮', '+1264', 'Anguilla'],
        ['🇦🇬', '+1268', 'Antigua and Barbuda'],
        ['🇦🇷', '+54', 'Argentina'],
        ['🇦🇲', '+374', 'Armenia'],
        ['🇦🇼', '+297', 'Aruba'],
        ['🇦🇺', '+61', 'Australia'],
        ['🇦🇹', '+43', 'Austria'],
        ['🇦🇿', '+994', 'Azerbaijan'],
        ['🇧🇸', '+1242', 'Bahamas'],
        ['🇧🇭', '+973', 'Bahrain'],
        ['🇧🇩', '+880', 'Bangladesh'],
        ['🇧🇧', '+1246', 'Barbados'],
        ['🇧🇾', '+375', 'Belarus'],
        ['🇧🇪', '+32', 'Belgium'],
        ['🇧🇿', '+501', 'Belize'],
        ['🇧🇯', '+229', 'Benin'],
        ['🇧🇲', '+1441', 'Bermuda'],
        ['🇧🇹', '+975', 'Bhutan'],
        ['🇧🇴', '+591', 'Bolivia'],
        ['🇧🇦', '+387', 'Bosnia and Herzegovina'],
        ['🇧🇼', '+267', 'Botswana'],
        ['🇧🇷', '+55', 'Brazil'],
        ['🇻🇬', '+1284', 'British Virgin Islands'],
        ['🇧🇳', '+673', 'Brunei'],
        ['🇧🇬', '+359', 'Bulgaria'],
        ['🇧🇫', '+226', 'Burkina Faso'],
        ['🇧🇮', '+257', 'Burundi'],
        ['🇰🇭', '+855', 'Cambodia'],
        ['🇨🇲', '+237', 'Cameroon'],
        ['🇨🇦', '+1', 'Canada'],
        ['🇨🇻', '+238', 'Cape Verde'],
        ['🇰🇾', '+1345', 'Cayman Islands'],
        ['🇨🇫', '+236', 'Central African Republic'],
        ['🇹🇩', '+235', 'Chad'],
        ['🇨🇱', '+56', 'Chile'],
        ['🇨🇳', '+86', 'China'],
        ['🇨🇴', '+57', 'Colombia'],
        ['🇰🇲', '+269', 'Comoros'],
        ['🇨🇬', '+242', 'Congo'],
        ['🇨🇩', '+243', 'Congo, Democratic Republic'],
        ['🇨🇰', '+682', 'Cook Islands'],
        ['🇨🇷', '+506', 'Costa Rica'],
        ['🇨🇮', '+225', 'Cote d’Ivoire'],
        ['🇭🇷', '+385', 'Croatia'],
        ['🇨🇺', '+53', 'Cuba'],
        ['🇨🇼', '+599', 'Curacao'],
        ['🇨🇾', '+357', 'Cyprus'],
        ['🇨🇿', '+420', 'Czech Republic'],
        ['🇩🇰', '+45', 'Denmark'],
        ['🇩🇯', '+253', 'Djibouti'],
        ['🇩🇲', '+1767', 'Dominica'],
        ['🇩🇴', '+1809', 'Dominican Republic'],
        ['🇩🇴', '+1829', 'Dominican Republic'],
        ['🇩🇴', '+1849', 'Dominican Republic'],
        ['🇪🇨', '+593', 'Ecuador'],
        ['🇪🇬', '+20', 'Egypt'],
        ['🇸🇻', '+503', 'El Salvador'],
        ['🇬🇶', '+240', 'Equatorial Guinea'],
        ['🇪🇷', '+291', 'Eritrea'],
        ['🇪🇪', '+372', 'Estonia'],
        ['🇸🇿', '+268', 'Eswatini'],
        ['🇪🇹', '+251', 'Ethiopia'],
        ['🇫🇰', '+500', 'Falkland Islands'],
        ['🇫🇴', '+298', 'Faroe Islands'],
        ['🇫🇯', '+679', 'Fiji'],
        ['🇫🇮', '+358', 'Finland'],
        ['🇫🇷', '+33', 'France'],
        ['🇬🇫', '+594', 'French Guiana'],
        ['🇵🇫', '+689', 'French Polynesia'],
        ['🇬🇦', '+241', 'Gabon'],
        ['🇬🇲', '+220', 'Gambia'],
        ['🇬🇪', '+995', 'Georgia'],
        ['🇩🇪', '+49', 'Germany'],
        ['🇬🇭', '+233', 'Ghana'],
        ['🇬🇮', '+350', 'Gibraltar'],
        ['🇬🇷', '+30', 'Greece'],
        ['🇬🇱', '+299', 'Greenland'],
        ['🇬🇩', '+1473', 'Grenada'],
        ['🇬🇵', '+590', 'Guadeloupe'],
        ['🇬🇺', '+1671', 'Guam'],
        ['🇬🇹', '+502', 'Guatemala'],
        ['🇬🇬', '+44', 'Guernsey'],
        ['🇬🇳', '+224', 'Guinea'],
        ['🇬🇼', '+245', 'Guinea-Bissau'],
        ['🇬🇾', '+592', 'Guyana'],
        ['🇭🇹', '+509', 'Haiti'],
        ['🇭🇳', '+504', 'Honduras'],
        ['🇭🇰', '+852', 'Hong Kong'],
        ['🇭🇺', '+36', 'Hungary'],
        ['🇮🇸', '+354', 'Iceland'],
        ['🇮🇳', '+91', 'India'],
        ['🇮🇩', '+62', 'Indonesia'],
        ['🇮🇷', '+98', 'Iran'],
        ['🇮🇶', '+964', 'Iraq'],
        ['🇮🇪', '+353', 'Ireland'],
        ['🇮🇲', '+44', 'Isle of Man'],
        ['🇮🇱', '+972', 'Israel'],
        ['🇮🇹', '+39', 'Italy'],
        ['🇯🇲', '+1876', 'Jamaica'],
        ['🇯🇵', '+81', 'Japan'],
        ['🇯🇪', '+44', 'Jersey'],
        ['🇯🇴', '+962', 'Jordan'],
        ['🇰🇿', '+7', 'Kazakhstan'],
        ['🇰🇪', '+254', 'Kenya'],
        ['🇰🇮', '+686', 'Kiribati'],
        ['🇽🇰', '+383', 'Kosovo'],
        ['🇰🇼', '+965', 'Kuwait'],
        ['🇰🇬', '+996', 'Kyrgyzstan'],
        ['🇱🇦', '+856', 'Laos'],
        ['🇱🇻', '+371', 'Latvia'],
        ['🇱🇧', '+961', 'Lebanon'],
        ['🇱🇸', '+266', 'Lesotho'],
        ['🇱🇷', '+231', 'Liberia'],
        ['🇱🇾', '+218', 'Libya'],
        ['🇱🇮', '+423', 'Liechtenstein'],
        ['🇱🇹', '+370', 'Lithuania'],
        ['🇱🇺', '+352', 'Luxembourg'],
        ['🇲🇴', '+853', 'Macau'],
        ['🇲🇬', '+261', 'Madagascar'],
        ['🇲🇼', '+265', 'Malawi'],
        ['🇲🇻', '+960', 'Maldives'],
        ['🇲🇱', '+223', 'Mali'],
        ['🇲🇹', '+356', 'Malta'],
        ['🇲🇭', '+692', 'Marshall Islands'],
        ['🇲🇶', '+596', 'Martinique'],
        ['🇲🇷', '+222', 'Mauritania'],
        ['🇲🇺', '+230', 'Mauritius'],
        ['🇾🇹', '+262', 'Mayotte'],
        ['🇲🇽', '+52', 'Mexico'],
        ['🇫🇲', '+691', 'Micronesia'],
        ['🇲🇩', '+373', 'Moldova'],
        ['🇲🇨', '+377', 'Monaco'],
        ['🇲🇳', '+976', 'Mongolia'],
        ['🇲🇪', '+382', 'Montenegro'],
        ['🇲🇸', '+1664', 'Montserrat'],
        ['🇲🇦', '+212', 'Morocco'],
        ['🇲🇿', '+258', 'Mozambique'],
        ['🇲🇲', '+95', 'Myanmar'],
        ['🇳🇦', '+264', 'Namibia'],
        ['🇳🇷', '+674', 'Nauru'],
        ['🇳🇵', '+977', 'Nepal'],
        ['🇳🇱', '+31', 'Netherlands'],
        ['🇳🇨', '+687', 'New Caledonia'],
        ['🇳🇿', '+64', 'New Zealand'],
        ['🇳🇮', '+505', 'Nicaragua'],
        ['🇳🇪', '+227', 'Niger'],
        ['🇳🇬', '+234', 'Nigeria'],
        ['🇳🇺', '+683', 'Niue'],
        ['🇰🇵', '+850', 'North Korea'],
        ['🇲🇰', '+389', 'North Macedonia'],
        ['🇲🇵', '+1670', 'Northern Mariana Islands'],
        ['🇳🇴', '+47', 'Norway'],
        ['🇴🇲', '+968', 'Oman'],
        ['🇵🇰', '+92', 'Pakistan'],
        ['🇵🇼', '+680', 'Palau'],
        ['🇵🇸', '+970', 'Palestine'],
        ['🇵🇦', '+507', 'Panama'],
        ['🇵🇬', '+675', 'Papua New Guinea'],
        ['🇵🇾', '+595', 'Paraguay'],
        ['🇵🇪', '+51', 'Peru'],
        ['🇵🇭', '+63', 'Philippines'],
        ['🇵🇱', '+48', 'Poland'],
        ['🇵🇹', '+351', 'Portugal'],
        ['🇵🇷', '+1787', 'Puerto Rico'],
        ['🇵🇷', '+1939', 'Puerto Rico'],
        ['🇶🇦', '+974', 'Qatar'],
        ['🇷🇪', '+262', 'Reunion'],
        ['🇷🇴', '+40', 'Romania'],
        ['🇷🇺', '+7', 'Russia'],
        ['🇷🇼', '+250', 'Rwanda'],
        ['🇧🇱', '+590', 'Saint Barthelemy'],
        ['🇸🇭', '+290', 'Saint Helena'],
        ['🇰🇳', '+1869', 'Saint Kitts and Nevis'],
        ['🇱🇨', '+1758', 'Saint Lucia'],
        ['🇲🇫', '+590', 'Saint Martin'],
        ['🇵🇲', '+508', 'Saint Pierre and Miquelon'],
        ['🇻🇨', '+1784', 'Saint Vincent and the Grenadines'],
        ['🇼🇸', '+685', 'Samoa'],
        ['🇸🇲', '+378', 'San Marino'],
        ['🇸🇹', '+239', 'Sao Tome and Principe'],
        ['🇸🇦', '+966', 'Saudi Arabia'],
        ['🇸🇳', '+221', 'Senegal'],
        ['🇷🇸', '+381', 'Serbia'],
        ['🇸🇨', '+248', 'Seychelles'],
        ['🇸🇱', '+232', 'Sierra Leone'],
        ['🇸🇬', '+65', 'Singapore'],
        ['🇸🇽', '+1721', 'Sint Maarten'],
        ['🇸🇰', '+421', 'Slovakia'],
        ['🇸🇮', '+386', 'Slovenia'],
        ['🇸🇧', '+677', 'Solomon Islands'],
        ['🇸🇴', '+252', 'Somalia'],
        ['🇿🇦', '+27', 'South Africa'],
        ['🇰🇷', '+82', 'South Korea'],
        ['🇸🇸', '+211', 'South Sudan'],
        ['🇪🇸', '+34', 'Spain'],
        ['🇱🇰', '+94', 'Sri Lanka'],
        ['🇸🇩', '+249', 'Sudan'],
        ['🇸🇷', '+597', 'Suriname'],
        ['🇸🇪', '+46', 'Sweden'],
        ['🇨🇭', '+41', 'Switzerland'],
        ['🇸🇾', '+963', 'Syria'],
        ['🇹🇼', '+886', 'Taiwan'],
        ['🇹🇯', '+992', 'Tajikistan'],
        ['🇹🇿', '+255', 'Tanzania'],
        ['🇹🇭', '+66', 'Thailand'],
        ['🇹🇱', '+670', 'Timor-Leste'],
        ['🇹🇬', '+228', 'Togo'],
        ['🇹🇰', '+690', 'Tokelau'],
        ['🇹🇴', '+676', 'Tonga'],
        ['🇹🇹', '+1868', 'Trinidad and Tobago'],
        ['🇹🇳', '+216', 'Tunisia'],
        ['🇹🇷', '+90', 'Turkey'],
        ['🇹🇲', '+993', 'Turkmenistan'],
        ['🇹🇨', '+1649', 'Turks and Caicos Islands'],
        ['🇹🇻', '+688', 'Tuvalu'],
        ['🇺🇬', '+256', 'Uganda'],
        ['🇺🇦', '+380', 'Ukraine'],
        ['🇦🇪', '+971', 'United Arab Emirates'],
        ['🇬🇧', '+44', 'United Kingdom'],
        ['🇺🇸', '+1', 'United States'],
        ['🇺🇾', '+598', 'Uruguay'],
        ['🇺🇿', '+998', 'Uzbekistan'],
        ['🇻🇺', '+678', 'Vanuatu'],
        ['🇻🇦', '+379', 'Vatican City'],
        ['🇻🇪', '+58', 'Venezuela'],
        ['🇻🇳', '+84', 'Vietnam'],
        ['🇻🇮', '+1340', 'Virgin Islands, U.S.'],
        ['🇼🇫', '+681', 'Wallis and Futuna'],
        ['🇾🇪', '+967', 'Yemen'],
        ['🇿🇲', '+260', 'Zambia'],
        ['🇿🇼', '+263', 'Zimbabwe'],
    ];

    return array_map(static function (array $row): array {
        return [
            'flag' => $row[0],
            'code' => $row[1],
            'name' => $row[2],
            'label' => $row[0] . ' ' . $row[1] . ' ' . $row[2],
        ];
    }, $rows);
}

function country_codes(): array
{
    $codes = [];
    foreach (country_code_options() as $option) {
        $codes[$option['code']] = $option['label'];
    }

    return $codes;
}

function render_country_options(string $selectedCode): string
{
    $html = '';
    $hasSelected = false;

    foreach (country_code_options() as $option) {
        $isSelected = !$hasSelected && $option['code'] === $selectedCode;
        if ($isSelected) {
            $hasSelected = true;
        }

        $html .= '<option value="' . e($option['code']) . '"' . ($isSelected ? ' selected' : '') . '>' . e($option['label']) . '</option>';
    }

    return $html;
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
        'allow_www' => 1,
        'allow_subdomains' => 0,
        'domain_lock_enabled' => 1,
        'strict_domain_check' => 1,
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
        'allow_www' => post_checkbox('allow_www'),
        'allow_subdomains' => post_checkbox('allow_subdomains'),
        'domain_lock_enabled' => post_checkbox('domain_lock_enabled'),
        'strict_domain_check' => post_checkbox('strict_domain_check'),
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

function enabled_label($value, string $enabled = 'Enabled', string $disabled = 'Disabled'): string
{
    return !empty($value) ? $enabled : $disabled;
}

function widget_style_frame_size(string $style): array
{
    return match ($style) {
        'style-1' => ['width' => 240, 'height' => 90],
        'style-2', 'style-3' => ['width' => 96, 'height' => 96],
        'style-3-large' => ['width' => 130, 'height' => 130],
        'style-4', 'style-8' => ['width' => 260, 'height' => 90],
        'style-5' => ['width' => 120, 'height' => 120],
        'style-6' => ['width' => 220, 'height' => 80],
        'style-7', 'style-7-extend' => ['width' => 110, 'height' => 110],
        default => ['width' => 120, 'height' => 120],
    };
}

function widget_frame_size(array $widget): array
{
    $desktop = widget_style_frame_size((string) ($widget['desktop_style'] ?? 'style-1'));
    $mobile = widget_style_frame_size((string) ($widget['mobile_style'] ?? 'style-1'));
    $width = max($desktop['width'], $mobile['width']);
    $height = max($desktop['height'], $mobile['height']);

    return ['width' => $width, 'height' => $height];
}

function iframe_position_css(array $widget, string $device): string
{
    $positionType = enum_value((string) ($widget[$device . '_position_type'] ?? 'fixed'), ['fixed', 'absolute'], 'fixed');
    $verticalSide = enum_value((string) ($widget[$device . '_vertical_position_type'] ?? 'bottom'), ['top', 'bottom'], 'bottom');
    $verticalValue = css_unit_value((string) ($widget[$device . '_vertical_position_value'] ?? '25px'), '25px');
    $horizontalSide = enum_value((string) ($widget[$device . '_horizontal_position_type'] ?? 'right'), ['left', 'right'], 'right');
    $horizontalValue = css_unit_value((string) ($widget[$device . '_horizontal_position_value'] ?? '25px'), '25px');

    return 'position:' . $positionType . '; top:auto; bottom:auto; left:auto; right:auto; '
        . $verticalSide . ':' . $verticalValue . '; '
        . $horizontalSide . ':' . $horizontalValue . ';';
}

function iframe_position_settings(array $widget, string $device): array
{
    return [
        'position' => enum_value((string) ($widget[$device . '_position_type'] ?? 'fixed'), ['fixed', 'absolute'], 'fixed'),
        'verticalSide' => enum_value((string) ($widget[$device . '_vertical_position_type'] ?? 'bottom'), ['top', 'bottom'], 'bottom'),
        'verticalValue' => css_unit_value((string) ($widget[$device . '_vertical_position_value'] ?? '25px'), '25px'),
        'horizontalSide' => enum_value((string) ($widget[$device . '_horizontal_position_type'] ?? 'right'), ['left', 'right'], 'right'),
        'horizontalValue' => css_unit_value((string) ($widget[$device . '_horizontal_position_value'] ?? '25px'), '25px'),
    ];
}

function embed_code(array $widget): string
{
    $src = SYSTEM_BASE_URL . '/embed.js.php?id=' . rawurlencode((string) $widget['id']) . '&key=' . rawurlencode((string) $widget['public_key']);
    return '<script src="' . $src . '"></script>';
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
