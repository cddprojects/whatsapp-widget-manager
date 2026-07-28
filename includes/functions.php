<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/urls.php';

const WIDGET_STATUS_SETUP_REQUIRED = 'setup_required';
const WIDGET_STATUS_ACTIVE = 'active';
const WIDGET_STATUS_PAUSED = 'paused';
const WIDGET_STATUS_DISABLED = 'disabled';
const WIDGET_CHANNEL_WHATSAPP = 'whatsapp';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . app_path($path));
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
        exit(t('csrf.invalid_token'));
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
        'style-6' => 'Style-6: Plain Text',
        'style-7' => 'Style-7: Icon with Padding',
        'style-7-extend' => 'Style-7 Extend: Icon on Hover Extend',
        'style-8' => 'Style-8: Button',
        'style-9-left-hover' => 'Style-9: Left Hover Text',
    ];
}

function normalize_widget_style(string $style): string
{
    return $style === 'style-5' ? 'style-8' : $style;
}

function sanitize_widget_style(string $style, string $default = 'style-1'): string
{
    $style = normalize_widget_style(trim($style));

    return enum_value($style, array_keys(widget_styles()), $default);
}

/**
 * Telegram-safe launcher styles. Style 9 inherits a WhatsApp-specific
 * left-hover layout that creates an unsuitable rectangular shell for Telegram.
 *
 * @return array<string, string>
 */
function telegram_widget_styles(): array
{
    $styles = widget_styles();
    unset($styles['style-9-left-hover']);

    return $styles;
}

function sanitize_telegram_widget_style(string $style, string $default = 'style-4'): string
{
    $style = normalize_widget_style(trim($style));
    if ($style === 'style-9-left-hover') {
        return $default;
    }

    return enum_value($style, array_keys(telegram_widget_styles()), $default);
}

function default_telegram_widget_style(): string
{
    return 'style-4';
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

function normalize_dial_code(string $value): string
{
    $digits = preg_replace('/\D/', '', $value) ?? '';
    if ($digits === '') {
        return '';
    }

    return '+' . $digits;
}

function get_neutral_calling_code_label(string $dialCode, string $fallbackCountryName = ''): string
{
    $labels = [
        '+1' => 'North America (US / Canada)',
        '+7' => 'Russia / Kazakhstan',
        '+20' => 'Egypt',
        '+27' => 'South Africa',
        '+30' => 'Greece',
        '+31' => 'Netherlands',
        '+32' => 'Belgium',
        '+33' => 'France',
        '+34' => 'Spain',
        '+39' => 'Italy',
        '+40' => 'Romania',
        '+41' => 'Switzerland',
        '+43' => 'Austria',
        '+44' => 'United Kingdom',
        '+45' => 'Denmark',
        '+46' => 'Sweden',
        '+47' => 'Norway',
        '+48' => 'Poland',
        '+49' => 'Germany',
        '+51' => 'Peru',
        '+52' => 'Mexico',
        '+54' => 'Argentina',
        '+55' => 'Brazil',
        '+56' => 'Chile',
        '+57' => 'Colombia',
        '+58' => 'Venezuela',
        '+60' => 'Malaysia',
        '+61' => 'Australia',
        '+62' => 'Indonesia',
        '+63' => 'Philippines',
        '+64' => 'New Zealand',
        '+65' => 'Singapore',
        '+66' => 'Thailand',
        '+81' => 'Japan',
        '+82' => 'South Korea',
        '+84' => 'Vietnam',
        '+86' => 'China',
        '+91' => 'India',
        '+92' => 'Pakistan',
        '+93' => 'Afghanistan',
        '+94' => 'Sri Lanka',
        '+95' => 'Myanmar',
        '+98' => 'Iran',
        '+212' => 'Morocco',
        '+234' => 'Nigeria',
        '+254' => 'Kenya',
        '+351' => 'Portugal',
        '+352' => 'Luxembourg',
        '+353' => 'Ireland',
        '+358' => 'Finland',
        '+380' => 'Ukraine',
        '+420' => 'Czech Republic',
        '+852' => 'Hong Kong',
        '+853' => 'Macau',
        '+886' => 'Taiwan',
        '+960' => 'Maldives',
        '+971' => 'United Arab Emirates',
    ];

    if (isset($labels[$dialCode])) {
        return $labels[$dialCode];
    }

    if ($fallbackCountryName !== '') {
        return $fallbackCountryName;
    }

    return 'International calling code';
}

function calling_code_options(): array
{
    static $options = null;
    if ($options !== null) {
        return $options;
    }

    $uniqueCodes = [];
    foreach (country_code_options() as $country) {
        $dialCode = normalize_dial_code((string) $country['code']);
        if ($dialCode === '' || isset($uniqueCodes[$dialCode])) {
            continue;
        }

        $uniqueCodes[$dialCode] = [
            'dialCode' => $dialCode,
            'label' => get_neutral_calling_code_label($dialCode, (string) $country['name']),
        ];
    }

    $options = array_values($uniqueCodes);
    usort($options, static function (array $a, array $b): int {
        return (int) str_replace('+', '', $a['dialCode']) <=> (int) str_replace('+', '', $b['dialCode']);
    });

    return $options;
}

function country_codes(): array
{
    $codes = [];
    foreach (calling_code_options() as $option) {
        $codes[$option['dialCode']] = $option['dialCode'];
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

function country_code_search_label(string $code): string
{
    return normalize_dial_code($code) ?: $code;
}

function render_calling_code_picker(string $selectedCode = '+60', ?string $hiddenName = null): string
{
    $callingCode = normalize_dial_code($selectedCode) ?: '+60';
    $nameAttr = $hiddenName !== null ? ' name="' . e($hiddenName) . '"' : '';

    return '<div class="ctcw-calling-code-picker">'
        . '<input type="hidden" class="ctcw-calling-code-value" value="' . e($callingCode) . '"' . $nameAttr . '>'
        . '<button type="button" class="ctcw-calling-code-trigger" aria-haspopup="listbox" aria-expanded="false" aria-label="Calling code">'
        . '<span class="ctcw-calling-code-label">' . e($callingCode) . '</span>'
        . '<span class="ctcw-calling-code-caret" aria-hidden="true">▼</span>'
        . '</button>'
        . '<div class="ctcw-calling-code-menu" hidden>'
        . '<input type="search" class="ctcw-calling-code-search" placeholder="Search calling code or country" autocomplete="off" aria-label="Search calling code or country">'
        . '<div class="ctcw-calling-code-options" role="listbox"></div>'
        . '</div>'
        . '</div>';
}

function render_country_code_search_input(string $inputId, string $selectedCode = '+60', ?string $hiddenName = null): string
{
    return render_calling_code_picker($selectedCode, $hiddenName);
}

function country_code_prefixes_longest_first(): array
{
    static $prefixes = null;
    if ($prefixes !== null) {
        return $prefixes;
    }

    $prefixes = [];
    foreach (country_code_options() as $option) {
        $prefixes[] = clean_phone_number((string) $option['code']);
    }

    usort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
    $prefixes = array_values(array_unique(array_filter($prefixes)));

    return $prefixes;
}

function parse_international_phone_line(string $line): ?array
{
    $raw = trim($line);
    if ($raw === '') {
        return null;
    }

    $digits = clean_phone_number($raw);
    if ($digits === '') {
        return null;
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    foreach (country_code_prefixes_longest_first() as $prefix) {
        if ($prefix === '' || !str_starts_with($digits, $prefix) || strlen($digits) <= strlen($prefix)) {
            continue;
        }

        $localDigits = substr($digits, strlen($prefix));
        $fullNumber = $prefix . $localDigits;
        if (!validate_phone_number($fullNumber)) {
            continue;
        }

        return [
            'country_code' => '+' . $prefix,
            'number' => $localDigits,
            'full_number' => $fullNumber,
        ];
    }

    return null;
}

function strip_phone_number_entry(array $number): array
{
    return [
        'country_code' => (string) ($number['country_code'] ?? '+60'),
        'number' => (string) ($number['number'] ?? ''),
        'full_number' => (string) ($number['full_number'] ?? ''),
    ];
}

function client_active_phone_numbers(array $widget): array
{
    return widget_phone_list($widget);
}

function widget_phone_list(array $widget): array
{
    $numbers = [];

    foreach (decode_random_numbers($widget['random_numbers_json'] ?? '[]') as $row) {
        if (!is_array($row)) {
            continue;
        }

        $entry = strip_phone_number_entry($row);
        if ($entry['number'] === '') {
            continue;
        }

        if ($entry['full_number'] === '') {
            $entry['full_number'] = clean_phone_number($entry['country_code']) . clean_phone_number($entry['number']);
        }

        if (!validate_phone_number($entry['full_number'])) {
            continue;
        }

        $numbers[] = $entry;
    }

    $numbers = remove_duplicate_phone_numbers($numbers);
    if ($numbers !== []) {
        return $numbers;
    }

    $number = clean_phone_number((string) ($widget['whatsapp_number'] ?? ''));
    if ($number === '') {
        return [];
    }

    $countryCode = (string) ($widget['whatsapp_country_code'] ?? '+60');
    $countryDigits = clean_phone_number($countryCode);
    $localDigits = $number;
    if ($countryDigits !== '' && str_starts_with($number, $countryDigits) && strlen($number) > strlen($countryDigits)) {
        $localDigits = substr($number, strlen($countryDigits));
    }

    $fullNumber = $countryDigits . $localDigits;
    if (!validate_phone_number($fullNumber)) {
        return [];
    }

    return [[
        'country_code' => $countryCode,
        'number' => $localDigits,
        'full_number' => $fullNumber,
    ]];
}

function sanitize_phone_numbers_from_post(array $post, string $fieldKey = 'widget_numbers', ?array $existingWidget = null, bool $allowEmpty = false): ?array
{
    $rows = $post[$fieldKey] ?? [];
    if (!is_array($rows) || $rows === []) {
        return $allowEmpty ? build_empty_phone_widget_update($existingWidget ?? []) : null;
    }

    $numbers = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $countryCode = normalize_dial_code(trim((string) ($row['country_code'] ?? '+60'))) ?: '+60';
        if (!array_key_exists($countryCode, country_codes())) {
            $countryCode = '+60';
        }

        $normalized = normalize_phone_number($countryCode, (string) ($row['number'] ?? ''));
        if ($normalized === null) {
            continue;
        }

        $numbers[] = $normalized;
    }

    $numbers = remove_duplicate_phone_numbers($numbers);
    if ($numbers === []) {
        return $allowEmpty ? build_empty_phone_widget_update($existingWidget ?? []) : null;
    }

    return build_phone_widget_update($numbers, $existingWidget ?? []);
}

function get_widget_active_numbers(array $widget): array
{
    return client_active_phone_numbers($widget);
}

function phone_number_dedupe_key(array $number): string
{
    $entry = strip_phone_number_entry($number);
    if ($entry['full_number'] !== '') {
        return clean_phone_number($entry['full_number']);
    }

    if ($entry['number'] === '') {
        return '';
    }

    return clean_phone_number($entry['country_code']) . clean_phone_number($entry['number']);
}

function remove_duplicate_phone_numbers(array $numbers): array
{
    $unique = [];
    $seen = [];

    foreach ($numbers as $number) {
        if (!is_array($number)) {
            continue;
        }

        $entry = strip_phone_number_entry($number);
        if ($entry['number'] === '') {
            continue;
        }

        if ($entry['full_number'] === '') {
            $entry['full_number'] = clean_phone_number($entry['country_code']) . clean_phone_number($entry['number']);
        }

        $key = clean_phone_number($entry['full_number']);
        if ($key === '' || isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $unique[] = $entry;
    }

    return $unique;
}

function merge_phone_numbers_with_stats(array $existingNumbers, array $uploadedNumbers): array
{
    $merged = remove_duplicate_phone_numbers($existingNumbers);
    $existingKeys = [];
    foreach ($merged as $number) {
        $key = phone_number_dedupe_key($number);
        if ($key !== '') {
            $existingKeys[$key] = true;
        }
    }

    $added = 0;
    $duplicatesSkipped = 0;

    foreach ($uploadedNumbers as $number) {
        if (!is_array($number)) {
            continue;
        }

        $entry = strip_phone_number_entry($number);
        if ($entry['number'] === '') {
            continue;
        }

        if ($entry['full_number'] === '') {
            $entry['full_number'] = clean_phone_number($entry['country_code']) . clean_phone_number($entry['number']);
        }

        $key = clean_phone_number($entry['full_number']);
        if ($key === '') {
            continue;
        }

        if (isset($existingKeys[$key])) {
            $duplicatesSkipped++;
            continue;
        }

        $existingKeys[$key] = true;
        $merged[] = $entry;
        $added++;
    }

    return [
        'numbers' => remove_duplicate_phone_numbers($merged),
        'added' => $added,
        'duplicates_skipped' => $duplicatesSkipped,
    ];
}

function merge_phone_numbers(array $existingNumbers, array $uploadedNumbers): array
{
    return merge_phone_numbers_with_stats($existingNumbers, $uploadedNumbers)['numbers'];
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
        'website_name' => '',
        'allow_www' => 1,
        'allow_subdomains' => 0,
        'domain_lock_enabled' => 0,
        'strict_domain_check' => 0,
        'whatsapp_country_code' => '+60',
        'whatsapp_number' => '',
        'use_random_numbers' => 0,
        'random_numbers_json' => '[]',
        'destination_selection_method' => 'round_robin',
        'round_robin_next_index' => 0,
        'prefilled_message' => "Hello {site}\nLike to know more information about {title}, {url}",
        'call_to_action' => 'WhatsApp us',
        'desktop_style' => 'style-1',
        'mobile_style' => 'style-1',
        'telegram_desktop_style' => 'style-4',
        'telegram_mobile_style' => 'style-4',
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
        'widget_status' => WIDGET_STATUS_SETUP_REQUIRED,
        'business_hours_mode' => 'always_open',
        'business_hours_json' => json_encode(default_business_hours()),
        'offline_message' => 'We are currently offline. Please leave us a message later.',
        'greeting_enabled' => 1,
        'greeting_title' => 'Hi 👋',
        'greeting_message' => 'Need Help? Contact Us !',
        'greeting_delay_seconds' => 2,
        'greeting_open_behavior' => 'auto_delay',
        'greeting_capture_phone' => 0,
        'consent_notice_enabled' => 0,
        'consent_notice_text' => '',
        'greeting_phone_required' => 1,
        'greeting_allow_phone_plus' => 1,
        'greeting_force_phone_capture' => 0,
        'greeting_phone_placeholder' => 'Enter your phone number',
        'greeting_submit_text' => 'Continue to WhatsApp',
        'greeting_phone_submit_button_id' => '',
        'greeting_lead_success_message' => 'Redirecting to WhatsApp...',
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

function sanitize_widget_input(array $post, ?array $existingWidget = null): array
{
    $defaults = default_widget_data();
    $websiteDomain = normalize_domain((string) ($post['website_domain'] ?? ''));
    $phoneUpdate = sanitize_phone_numbers_from_post($post, 'widget_numbers', $existingWidget, true);
    if ($phoneUpdate === null) {
        $phoneUpdate = build_empty_phone_widget_update($existingWidget ?? []);
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
        'website_name' => trim((string) ($post['website_name'] ?? ($existingWidget['website_name'] ?? ''))),
        'allow_www' => post_checkbox('allow_www'),
        'allow_subdomains' => post_checkbox('allow_subdomains'),
        'domain_lock_enabled' => post_checkbox('domain_lock_enabled'),
        'strict_domain_check' => post_checkbox('strict_domain_check'),
        'whatsapp_country_code' => (string) ($phoneUpdate['whatsapp_country_code'] ?? '+60'),
        'whatsapp_number' => (string) ($phoneUpdate['whatsapp_number'] ?? ''),
        'use_random_numbers' => (int) ($phoneUpdate['use_random_numbers'] ?? 0),
        'random_numbers_json' => (string) ($phoneUpdate['random_numbers_json'] ?? '[]'),
        'prefilled_message' => trim((string) ($post['prefilled_message'] ?? $defaults['prefilled_message'])),
        'call_to_action' => trim((string) ($post['call_to_action'] ?? $defaults['call_to_action'])),
        'desktop_style' => sanitize_widget_style((string) ($post['desktop_style'] ?? 'style-1')),
        'mobile_style' => sanitize_widget_style((string) ($post['mobile_style'] ?? 'style-1')),
        'telegram_desktop_style' => sanitize_telegram_widget_style(
            (string) ($post['telegram_desktop_style'] ?? default_telegram_widget_style()),
            default_telegram_widget_style()
        ),
        'telegram_mobile_style' => sanitize_telegram_widget_style(
            (string) ($post['telegram_mobile_style'] ?? default_telegram_widget_style()),
            default_telegram_widget_style()
        ),
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
        'greeting_open_behavior' => 'auto_delay',
        'greeting_capture_phone' => 0,
        'consent_notice_enabled' => 0,
        'consent_notice_text' => '',
        'greeting_phone_required' => 0,
        'greeting_allow_phone_plus' => 1,
        'greeting_force_phone_capture' => 0,
        'greeting_phone_placeholder' => trim((string) ($post['greeting_phone_placeholder'] ?? $defaults['greeting_phone_placeholder'])),
        'greeting_submit_text' => trim((string) ($post['greeting_submit_text'] ?? $defaults['greeting_submit_text'])),
        'greeting_phone_submit_button_id' => '',
        'greeting_lead_success_message' => trim((string) ($post['greeting_lead_success_message'] ?? $defaults['greeting_lead_success_message'])),
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
    if ($data['greeting_submit_text'] === '') {
        $data['greeting_submit_text'] = $defaults['greeting_submit_text'];
    }
    if ($data['greeting_phone_placeholder'] === '') {
        $data['greeting_phone_placeholder'] = $defaults['greeting_phone_placeholder'];
    }
    if ($data['greeting_lead_success_message'] === '') {
        $data['greeting_lead_success_message'] = $defaults['greeting_lead_success_message'];
    }

    $greetingEnabled = post_checkbox('greeting_enabled');
    $data['greeting_enabled'] = $greetingEnabled;
    $existing = $existingWidget ?? [];

    if ($greetingEnabled) {
        $greetingCapturePhone = post_checkbox('greeting_capture_phone');
        $data['greeting_open_behavior'] = normalize_greeting_open_behavior(
            (string) ($post['greeting_open_behavior'] ?? ($existing['greeting_open_behavior'] ?? $defaults['greeting_open_behavior']))
        );
        $data['consent_notice_enabled'] = post_checkbox('consent_notice_enabled');
        $consentText = trim((string) ($post['consent_notice_text'] ?? ''));
        if (mb_strlen($consentText) > 500) {
            $consentText = mb_substr($consentText, 0, 500);
        }
        $data['consent_notice_text'] = $consentText !== '' ? $consentText : null;

        if ($greetingCapturePhone) {
            $greetingForcePhoneCapture = post_checkbox('greeting_force_phone_capture');
            $greetingPhoneRequired = post_checkbox('greeting_phone_required');

            if ($greetingForcePhoneCapture) {
                $greetingPhoneRequired = 1;
            }

            $data['greeting_capture_phone'] = 1;
            $data['greeting_force_phone_capture'] = $greetingForcePhoneCapture;
            $data['greeting_phone_required'] = $greetingPhoneRequired;
            $data['greeting_allow_phone_plus'] = post_checkbox('greeting_allow_phone_plus');
            $data['greeting_phone_submit_button_id'] = trim((string) ($post['greeting_phone_submit_button_id'] ?? ''));
        } else {
            $data['greeting_capture_phone'] = 0;
            $data['greeting_force_phone_capture'] = (int) ($existing['greeting_force_phone_capture'] ?? 0);
            $data['greeting_phone_required'] = (int) ($existing['greeting_phone_required'] ?? 1);
            $data['greeting_allow_phone_plus'] = (int) ($existing['greeting_allow_phone_plus'] ?? 1);
            $data['greeting_phone_submit_button_id'] = (string) ($existing['greeting_phone_submit_button_id'] ?? '');
        }
    } else {
        $data['consent_notice_enabled'] = (int) ($existing['consent_notice_enabled'] ?? 0);
        $data['consent_notice_text'] = $existing['consent_notice_text'] ?? null;
        $data['greeting_capture_phone'] = (int) ($existing['greeting_capture_phone'] ?? 0);
        $data['greeting_force_phone_capture'] = (int) ($existing['greeting_force_phone_capture'] ?? 0);
        $data['greeting_phone_required'] = (int) ($existing['greeting_phone_required'] ?? 1);
        $data['greeting_allow_phone_plus'] = (int) ($existing['greeting_allow_phone_plus'] ?? 1);
        $data['greeting_open_behavior'] = normalize_greeting_open_behavior(
            (string) ($existing['greeting_open_behavior'] ?? $defaults['greeting_open_behavior'])
        );
        $data['greeting_phone_submit_button_id'] = (string) ($existing['greeting_phone_submit_button_id'] ?? '');
    }

    if ($sameMobile) {
        $data['mobile_position_type'] = $data['desktop_position_type'];
        $data['mobile_vertical_position_type'] = $data['desktop_vertical_position_type'];
        $data['mobile_vertical_position_value'] = $data['desktop_vertical_position_value'];
        $data['mobile_horizontal_position_type'] = $data['desktop_horizontal_position_type'];
        $data['mobile_horizontal_position_value'] = $data['desktop_horizontal_position_value'];
    }

    $phoneNumbers = widget_phone_list($data);
    $phoneCount = count($phoneNumbers);
    $requestedMethod = enum_value(
        (string) ($post['destination_selection_method'] ?? ''),
        ['random', 'round_robin'],
        effective_destination_selection_method($data, $phoneCount)
    );
    $destinationSync = sync_destination_selection_for_phone_count($data, $phoneCount, $phoneCount > 1 ? $requestedMethod : null);
    $data['destination_selection_method'] = $destinationSync['destination_selection_method'];
    $data['round_robin_next_index'] = $destinationSync['round_robin_next_index'];
    if ($phoneCount > 1) {
        $data['use_random_numbers'] = 1;
    }

    $channelMode = enum_value(
        (string) ($post['channel_mode'] ?? 'whatsapp_only'),
        ['whatsapp_only', 'telegram_only', 'both'],
        'whatsapp_only'
    );
    $data['channel_mode'] = $channelMode;

    return $data;
}

function strip_php_tags(string $code): string
{
    return str_ireplace(['<?php', '<?=', '<?', '?>'], '', $code);
}

function validate_widget_data(array $data, ?int $widgetId = null): array
{
    $errors = [];

    if (!is_valid_domain($data['website_domain'])) {
        $errors[] = t('validation.domain_required');
    }

    if ($data['custom_url'] !== '' && !filter_var($data['custom_url'], FILTER_VALIDATE_URL)) {
        $errors[] = t('validation.custom_url_invalid');
    }

    if (!empty($data['greeting_capture_phone'])) {
        $submitButtonId = trim((string) ($data['greeting_phone_submit_button_id'] ?? ''));
        if ($submitButtonId !== '' && !is_valid_phone_submit_button_id($submitButtonId)) {
            $errors[] = t('validation.phone_submit_button_id_invalid');
        }
    }

    $channelMode = (string) ($data['channel_mode'] ?? 'whatsapp_only');
    if (!in_array($channelMode, ['whatsapp_only', 'telegram_only', 'both'], true)) {
        $errors[] = t('channel.error.invalid_mode');
    }

    // Widgets may be saved before destinations exist. Readiness is shown in the UI
    // and unready channels are not published publicly.

    return $errors;
}

function insert_widget(int $userId, array $data): int
{
    ensure_greeting_open_behavior_schema();
    ensure_greeting_allow_phone_plus_schema();
    ensure_greeting_phone_submit_button_id_schema();
    ensure_consent_notice_and_telegram_styles_schema();
    $channelMode = (string) ($data['channel_mode'] ?? 'whatsapp_only');
    unset($data['channel_mode'], $data['widget_status']);
    $data['user_id'] = $userId;
    $data['public_key'] = generate_public_key();
    $data = filter_widget_data_for_existing_columns($data);

    $columns = array_keys($data);
    $placeholders = array_map(static fn ($column) => ':' . $column, $columns);
    $sql = 'INSERT INTO widgets (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = db()->prepare($sql);
    $stmt->execute($data);

    $widgetId = (int) db()->lastInsertId();
    ensure_widget_channel_rows($widgetId);
    if (channel_schema_ready()) {
        sync_whatsapp_destinations_from_legacy($widgetId);
        // New widgets start WhatsApp-only unless explicitly configured after destinations exist.
        if (in_array($channelMode, ['whatsapp_only', 'telegram_only', 'both'], true)) {
            $result = save_widget_channel_config($widgetId, ['mode' => $channelMode]);
            if (!$result['ok'] && $channelMode !== 'whatsapp_only') {
                save_widget_channel_config($widgetId, ['mode' => 'whatsapp_only']);
            }
        }
    }
    refresh_widget_destination_status($widgetId);

    return $widgetId;
}

function update_widget(int $widgetId, int $userId, array $data): void
{
    ensure_greeting_open_behavior_schema();
    ensure_greeting_allow_phone_plus_schema();
    ensure_greeting_phone_submit_button_id_schema();
    ensure_consent_notice_and_telegram_styles_schema();
    $data = filter_widget_data_for_existing_columns($data);
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

function build_empty_phone_widget_update(?array $existingWidget = null): array
{
    $existingWidget = $existingWidget ?? [];

    return [
        'whatsapp_country_code' => (string) ($existingWidget['whatsapp_country_code'] ?? '+60'),
        'whatsapp_number' => '',
        'use_random_numbers' => 0,
        'random_numbers_json' => '[]',
        'destination_selection_method' => 'single',
        'round_robin_next_index' => (int) ($existingWidget['round_robin_next_index'] ?? 0),
    ];
}

function widget_channel_types(): array
{
    return supported_widget_channels();
}

function widget_has_valid_destinations(array $widget, ?string $channel = null): bool
{
    $widgetId = (int) ($widget['id'] ?? 0);

    if ($channel !== null) {
        $normalized = normalize_widget_channel($channel);
        if ($normalized === null) {
            return false;
        }
        if ($normalized === WIDGET_CHANNEL_WHATSAPP) {
            return count(widget_phone_list($widget)) >= 1;
        }
        if ($normalized === WIDGET_CHANNEL_TELEGRAM) {
            return $widgetId > 0 && count_active_channel_destinations($widgetId, WIDGET_CHANNEL_TELEGRAM) >= 1;
        }

        return false;
    }

    // No channel argument: widget is usable if at least one enabled channel is ready.
    if ($widgetId > 0 && channel_schema_ready()) {
        return publicly_ready_widget_channels($widgetId, $widget) !== [];
    }

    return count(widget_phone_list($widget)) >= 1;
}

function widget_is_admin_disabled(array $widget): bool
{
    return empty($widget['show_global']);
}

function widget_activation_statuses(): array
{
    return [
        WIDGET_STATUS_SETUP_REQUIRED,
        WIDGET_STATUS_ACTIVE,
        WIDGET_STATUS_PAUSED,
        WIDGET_STATUS_DISABLED,
    ];
}

function normalize_widget_activation_status(string $status): string
{
    return in_array($status, widget_activation_statuses(), true)
        ? $status
        : WIDGET_STATUS_SETUP_REQUIRED;
}

function persist_widget_activation_status(int $widgetId, string $status): void
{
    if (!table_has_column('widgets', 'widget_status')) {
        return;
    }

    $status = normalize_widget_activation_status($status);
    $stmt = db()->prepare(
        'UPDATE widgets SET widget_status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
    );
    $stmt->execute([
        'status' => $status,
        'id' => $widgetId,
    ]);
}

function refresh_widget_destination_status(int $widgetId, ?array $widget = null): string
{
    $widget = $widget ?? find_widget_by_id($widgetId);
    if ($widget === null) {
        return WIDGET_STATUS_SETUP_REQUIRED;
    }

    if (widget_is_admin_disabled($widget)) {
        persist_widget_activation_status($widgetId, WIDGET_STATUS_DISABLED);
        return WIDGET_STATUS_DISABLED;
    }

    $hasDestinations = widget_has_valid_destinations($widget);
    $current = normalize_widget_activation_status((string) ($widget['widget_status'] ?? WIDGET_STATUS_SETUP_REQUIRED));

    if (!$hasDestinations) {
        $nextStatus = in_array($current, [WIDGET_STATUS_ACTIVE, WIDGET_STATUS_PAUSED], true)
            ? WIDGET_STATUS_PAUSED
            : WIDGET_STATUS_SETUP_REQUIRED;
    } else {
        $nextStatus = WIDGET_STATUS_ACTIVE;
    }

    if ($nextStatus !== $current) {
        persist_widget_activation_status($widgetId, $nextStatus);
    }

    return $nextStatus;
}

function widget_is_publicly_active(array $widget): bool
{
    if (widget_is_admin_disabled($widget)) {
        return false;
    }

    $status = normalize_widget_activation_status((string) ($widget['widget_status'] ?? WIDGET_STATUS_SETUP_REQUIRED));
    if ($status !== WIDGET_STATUS_ACTIVE) {
        return false;
    }

    return widget_has_valid_destinations($widget);
}

function translate_widget_activation_status(string $status): string
{
    $status = normalize_widget_activation_status($status);
    $key = 'widget_status.' . str_replace('-', '_', $status);
    $translated = t($key);

    return $translated !== $key ? $translated : $status;
}

function widget_activation_status_badge_class(string $status): string
{
    return match (normalize_widget_activation_status($status)) {
        WIDGET_STATUS_ACTIVE => 'status-pill status-active',
        WIDGET_STATUS_PAUSED => 'status-pill status-paused',
        WIDGET_STATUS_DISABLED => 'status-pill status-disabled',
        default => 'status-pill status-setup',
    };
}

function render_widget_activation_status(array $widget, bool $includeHint = false): void
{
    $status = normalize_widget_activation_status((string) ($widget['widget_status'] ?? WIDGET_STATUS_SETUP_REQUIRED));
    if (widget_is_admin_disabled($widget)) {
        $status = WIDGET_STATUS_DISABLED;
    } elseif (!widget_has_valid_destinations($widget) && $status === WIDGET_STATUS_ACTIVE) {
        $status = refresh_widget_destination_status((int) $widget['id'], $widget);
    }

    echo '<span class="' . e(widget_activation_status_badge_class($status)) . '">'
        . e(translate_widget_activation_status($status))
        . '</span>';

    if ($includeHint) {
        $hintKey = 'widget_status.hint.' . str_replace('-', '_', $status);
        $hint = t($hintKey);
        if ($hint !== $hintKey && trim($hint) !== '') {
            echo '<small class="widget-status-hint">' . e($hint) . '</small>';
        }
    }
}

function widget_status_label(array $widget): string
{
    if (widget_is_admin_disabled($widget)) {
        return translate_widget_activation_status(WIDGET_STATUS_DISABLED);
    }

    $status = normalize_widget_activation_status((string) ($widget['widget_status'] ?? WIDGET_STATUS_SETUP_REQUIRED));
    if ($status === WIDGET_STATUS_ACTIVE && ($widget['business_hours_mode'] ?? '') === 'always_closed') {
        return t('business_hours.always_offline');
    }

    return translate_widget_activation_status($status);
}

function enabled_label($value, string $enabled = 'Enabled', string $disabled = 'Disabled'): string
{
    return !empty($value) ? $enabled : $disabled;
}

function widget_style_frame_size(string $style): array
{
    $style = normalize_widget_style($style);

    return match ($style) {
        'style-1' => ['width' => 280, 'height' => 110],
        'style-2', 'style-3' => ['width' => 120, 'height' => 120],
        'style-3-large', 'style-3-extend' => ['width' => 150, 'height' => 150],
        'style-4', 'style-8' => ['width' => 300, 'height' => 110],
        'style-6' => ['width' => 260, 'height' => 90],
        'style-7' => ['width' => 130, 'height' => 130],
        'style-7-extend', 'style-9-left-hover' => ['width' => 420, 'height' => 160],
        default => ['width' => 120, 'height' => 120],
    };
}

function widget_style_expanded_frame_size(string $style, bool $greetingEnabled): array
{
    if ($greetingEnabled) {
        return ['width' => 380, 'height' => 280];
    }

    $style = normalize_widget_style($style);

    return match ($style) {
        'style-1', 'style-4', 'style-8' => ['width' => 320, 'height' => 130],
        'style-2', 'style-3' => ['width' => 130, 'height' => 130],
        'style-3-large', 'style-3-extend' => ['width' => 160, 'height' => 160],
        'style-6' => ['width' => 280, 'height' => 100],
        'style-7' => ['width' => 140, 'height' => 140],
        'style-7-extend', 'style-9-left-hover' => ['width' => 420, 'height' => 160],
        default => widget_style_frame_size($style),
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
    $settings = iframe_position_settings($widget, $device);

    return 'position:' . $settings['position'] . '; top:auto; bottom:auto; left:auto; right:auto; '
        . $settings['verticalSide'] . ':' . $settings['verticalValue'] . '; '
        . $settings['horizontalSide'] . ':' . $settings['horizontalValue'] . ';';
}

function iframe_position_settings(array $widget, string $device): array
{
    if ($device === 'mobile' && !empty($widget['same_mobile_desktop_settings'])) {
        $device = 'desktop';
    }

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

function telegram_icon_svg(): string
{
    // Official-style Telegram paper-plane mark (currentColor for theme reuse).
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M9.04 15.3 8.85 18.9c.27 0 .39-.12.53-.26l2.55-2.45 5.29 3.88c.97.53 1.66.25 1.92-.9L21.9 4.72c.3-1.28-.46-1.78-1.4-1.47L2.72 9.52c-1.24.48-1.22 1.17-.21 1.48l4.66 1.45L18.3 6.2c.53-.35 1.02-.16.62.2L9.04 15.3Z"/></svg>';
}

/**
 * Channels that are enabled and have at least one active destination.
 *
 * @return list<string>
 */
function publicly_ready_widget_channels(int $widgetId, ?array $widget = null): array
{
    $widget = $widget ?? find_widget_by_id($widgetId);
    if ($widget === null) {
        return [];
    }

    $ready = [];
    foreach (enabled_widget_channels($widgetId, $widget) as $channel) {
        if (widget_has_valid_destinations($widget, $channel)) {
            $ready[] = $channel;
        }
    }

    return $ready;
}

/**
 * @return array{status: string, whatsapp: string, telegram: string, label: string}
 */
function widget_channel_readiness(int $widgetId, ?array $widget = null): array
{
    $widget = $widget ?? find_widget_by_id($widgetId);
    $config = get_widget_channel_config($widgetId, $widget);
    $waReady = !empty($config['whatsapp']) && widget_has_valid_destinations($widget ?? [], WIDGET_CHANNEL_WHATSAPP);
    $tgReady = !empty($config['telegram']) && widget_has_valid_destinations($widget ?? [], WIDGET_CHANNEL_TELEGRAM);

    $whatsappState = empty($config['whatsapp'])
        ? 'disabled'
        : ($waReady ? 'ready' : 'missing');
    $telegramState = empty($config['telegram'])
        ? 'disabled'
        : ($tgReady ? 'ready' : 'missing');

    $enabledCount = (int) !empty($config['whatsapp']) + (int) !empty($config['telegram']);
    $readyCount = (int) $waReady + (int) $tgReady;

    if ($readyCount === 0) {
        $status = 'setup_incomplete';
        $label = t('channel.readiness.setup_incomplete');
    } elseif ($readyCount < $enabledCount) {
        $status = 'partially_ready';
        $label = t('channel.readiness.partially_ready');
    } else {
        $status = 'ready';
        $label = t('channel.readiness.ready');
    }

    return [
        'status' => $status,
        'whatsapp' => $whatsappState,
        'telegram' => $telegramState,
        'label' => $label,
    ];
}

function channel_launcher_label(string $channel, bool $online = true, ?array $widget = null): string
{
    if (!$online && is_array($widget)) {
        return (string) ($widget['offline_message'] ?? t('widget.unavailable_generic'));
    }

    if ($channel === WIDGET_CHANNEL_TELEGRAM) {
        return t('channel.launcher.telegram');
    }

    if (is_array($widget) && trim((string) ($widget['call_to_action'] ?? '')) !== '') {
        return (string) $widget['call_to_action'];
    }

    return t('channel.launcher.whatsapp');
}

function channel_continue_label(string $channel): string
{
    return $channel === WIDGET_CHANNEL_TELEGRAM
        ? t('widget.continue_telegram')
        : t('widget.continue_whatsapp');
}

function channel_success_label(string $channel): string
{
    return $channel === WIDGET_CHANNEL_TELEGRAM
        ? t('widget.redirecting_telegram')
        : t('widget.redirecting_whatsapp');
}

function channel_force_phone_label(string $channel): string
{
    return $channel === WIDGET_CHANNEL_TELEGRAM
        ? t('preview.phone_required_telegram')
        : t('preview.phone_required');
}

function normalize_phone_number(string $countryCode, string $phone): ?array
{
    $countryDigits = clean_phone_number($countryCode);
    $phoneDigits = clean_phone_number($phone);

    if ($countryDigits === '' || $phoneDigits === '') {
        return null;
    }

    if (str_starts_with($phoneDigits, $countryDigits) && strlen($phoneDigits) > strlen($countryDigits)) {
        $localDigits = substr($phoneDigits, strlen($countryDigits));
    } else {
        $localDigits = $phoneDigits;
    }

    $fullNumber = $countryDigits . $localDigits;
    if (!validate_phone_number($fullNumber)) {
        return null;
    }

    return [
        'country_code' => '+' . $countryDigits,
        'number' => $localDigits,
        'full_number' => $fullNumber,
    ];
}

function parse_phone_upload(string $filePath): array
{
    $stats = [
        'total_rows' => 0,
        'imported' => 0,
        'skipped_invalid' => 0,
        'duplicates' => 0,
    ];
    $numbers = [];
    $seen = [];

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return ['numbers' => [], 'stats' => $stats];
    }

    if ($extension === 'csv') {
        $rows = array_map(static fn ($line) => str_getcsv($line), $lines);
        $stats['total_rows'] = count($rows);
        $hasHeader = false;
        if ($rows !== []) {
            $first = array_map(static fn ($value) => strtolower(trim((string) $value)), $rows[0]);
            $hasHeader = in_array('phone_number', $first, true) || in_array('country_code', $first, true);
        }

        $start = $hasHeader ? 1 : 0;
        for ($i = $start; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!is_array($row) || $row === []) {
                continue;
            }

            if ($hasHeader) {
                $map = [];
                foreach ($rows[0] as $index => $header) {
                    $map[strtolower(trim((string) $header))] = $row[$index] ?? '';
                }
                $country = trim((string) ($map['country_code'] ?? ''));
                $phone = trim((string) ($map['phone_number'] ?? $map['number'] ?? ''));
            } elseif (count($row) >= 2) {
                $country = trim((string) ($row[0] ?? ''));
                $phone = trim((string) ($row[1] ?? ''));
            } else {
                $stats['skipped_invalid']++;
                continue;
            }

            if ($country === '' || $phone === '') {
                $stats['skipped_invalid']++;
                continue;
            }

            $normalized = normalize_phone_number($country, $phone);
            if ($normalized === null) {
                $stats['skipped_invalid']++;
                continue;
            }

            if (isset($seen[$normalized['full_number']])) {
                $stats['duplicates']++;
                continue;
            }

            $seen[$normalized['full_number']] = true;
            $numbers[] = $normalized;
            $stats['imported']++;
        }

        if ($hasHeader) {
            $stats['total_rows'] = max(0, count($rows) - 1);
        }

        return ['numbers' => $numbers, 'stats' => $stats];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $stats['total_rows']++;
        $normalized = parse_international_phone_line($line);
        if ($normalized === null) {
            $stats['skipped_invalid']++;
            continue;
        }

        if (isset($seen[$normalized['full_number']])) {
            $stats['duplicates']++;
            continue;
        }

        $seen[$normalized['full_number']] = true;
        $numbers[] = $normalized;
        $stats['imported']++;
    }

    return ['numbers' => $numbers, 'stats' => $stats];
}

function build_phone_widget_update(array $numbers, ?array $existingWidget = null, ?string $requestedMethod = null): ?array
{
    if ($numbers === []) {
        return null;
    }

    $destinationSync = sync_destination_selection_for_phone_count(
        $existingWidget ?? [],
        count($numbers),
        $requestedMethod
    );

    if (count($numbers) === 1) {
        $number = $numbers[0];

        return [
            'whatsapp_country_code' => $number['country_code'],
            'whatsapp_number' => $number['number'],
            'use_random_numbers' => 0,
            'random_numbers_json' => '[]',
            'destination_selection_method' => 'single',
            'round_robin_next_index' => 0,
        ];
    }

    $payload = array_map(static function (array $number): array {
        return strip_phone_number_entry($number);
    }, $numbers);

    return [
        'whatsapp_country_code' => $numbers[0]['country_code'],
        'whatsapp_number' => $numbers[0]['number'],
        'use_random_numbers' => 1,
        'random_numbers_json' => json_encode(array_values($payload)),
        'destination_selection_method' => $destinationSync['destination_selection_method'],
        'round_robin_next_index' => $destinationSync['round_robin_next_index'],
    ];
}

function destination_selection_method_options(): array
{
    return ['single', 'random', 'round_robin'];
}

function effective_destination_selection_method(array $widget, ?int $destinationCount = null): string
{
    $count = $destinationCount ?? count(widget_phone_list($widget));
    if ($count <= 0) {
        return 'single';
    }
    if ($count === 1) {
        return 'single';
    }

    $method = (string) ($widget['destination_selection_method'] ?? '');
    if (in_array($method, ['random', 'round_robin'], true)) {
        return $method;
    }

    if (!empty($widget['use_random_numbers'])) {
        return 'random';
    }

    return 'round_robin';
}

function sync_destination_selection_for_phone_count(array $widget, int $destinationCount, ?string $requestedMethod = null): array
{
    if ($destinationCount <= 1) {
        return [
            'destination_selection_method' => 'single',
            'round_robin_next_index' => 0,
        ];
    }

    $method = null;
    if ($requestedMethod !== null && in_array($requestedMethod, ['random', 'round_robin'], true)) {
        $method = $requestedMethod;
    } else {
        $existingMethod = (string) ($widget['destination_selection_method'] ?? '');
        if (in_array($existingMethod, ['random', 'round_robin'], true)) {
            $method = $existingMethod;
        } elseif (!empty($widget['use_random_numbers'])) {
            $method = 'random';
        } else {
            $method = 'round_robin';
        }
    }

    $nextIndex = (int) ($widget['round_robin_next_index'] ?? 0);
    if ($destinationCount > 0) {
        $nextIndex = $nextIndex % $destinationCount;
    } else {
        $nextIndex = 0;
    }

    return [
        'destination_selection_method' => $method,
        'round_robin_next_index' => $nextIndex,
    ];
}

function find_widget_by_id(int $widgetId): ?array
{
    $stmt = db()->prepare('SELECT * FROM widgets WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $widgetId]);
    $widget = $stmt->fetch();

    return $widget ?: null;
}

function normalize_widget_destination_state(int $widgetId, ?string $requestedMethod = null): void
{
    $widget = find_widget_by_id($widgetId);
    if (!$widget) {
        return;
    }

    $numbers = widget_phone_list($widget);
    $sync = sync_destination_selection_for_phone_count($widget, count($numbers), $requestedMethod);
    $phoneUpdate = build_phone_widget_update($numbers, $widget, $requestedMethod);
    if ($phoneUpdate === null) {
        return;
    }

    $update = array_merge($phoneUpdate, $sync);
    if (count($numbers) > 1) {
        $update['use_random_numbers'] = 1;
    }

    $allowed = [
        'whatsapp_country_code' => true,
        'whatsapp_number' => true,
        'use_random_numbers' => true,
        'random_numbers_json' => true,
        'destination_selection_method' => true,
        'round_robin_next_index' => true,
    ];
    $filtered = array_intersect_key($update, $allowed);
    if ($filtered === []) {
        return;
    }

    $assignments = [];
    foreach (array_keys($filtered) as $column) {
        $assignments[] = $column . ' = :' . $column;
    }

    $filtered['id'] = $widgetId;
    $sql = 'UPDATE widgets SET ' . implode(', ', $assignments) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
    $stmt = db()->prepare($sql);
    $stmt->execute($filtered);
}

function resolve_widget_destination(
    int $widgetId,
    string $publicKey,
    ?string $referrer = null,
    string $channel = WIDGET_CHANNEL_WHATSAPP
): array {
    $normalizedChannel = normalize_widget_channel($channel) ?? WIDGET_CHANNEL_WHATSAPP;

    if ($normalizedChannel === WIDGET_CHANNEL_TELEGRAM) {
        return resolve_telegram_destination($widgetId, $publicKey, $referrer);
    }

    $pdo = db();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT w.*, u.status AS owner_status
             FROM widgets w
             INNER JOIN users u ON u.id = w.user_id
             WHERE w.id = :id AND w.public_key = :public_key
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([
            'id' => $widgetId,
            'public_key' => $publicKey,
        ]);
        $widget = $stmt->fetch();

        if (!$widget) {
            $pdo->rollBack();

            return ['success' => false, 'message' => 'Widget not found'];
        }

        if (($widget['owner_status'] ?? '') !== USER_STATUS_ACTIVE) {
            $pdo->rollBack();

            return ['success' => false, 'message' => 'Widget not available'];
        }

        if (!domain_matches_referrer($widget, $referrer !== '' ? $referrer : null)) {
            $pdo->rollBack();

            return ['success' => false, 'message' => 'Domain not allowed'];
        }

        if (empty($widget['show_global'])) {
            $pdo->rollBack();

            return ['success' => false, 'message' => 'Widget not available'];
        }

        if (!widget_is_publicly_active($widget)) {
            $pdo->rollBack();

            return ['success' => false, 'message' => 'Widget not available'];
        }

        if (channel_schema_ready() && !widget_channel_is_enabled($widgetId, WIDGET_CHANNEL_WHATSAPP, $widget)) {
            $pdo->rollBack();

            return [
                'success' => false,
                'message' => 'WhatsApp is currently unavailable',
                'error' => 'channel_disabled',
            ];
        }

        if (!is_widget_online($widget)) {
            $pdo->rollBack();

            return ['success' => false, 'message' => 'Widget offline', 'error' => 'channel_unavailable'];
        }

        $numbers = widget_phone_list($widget);
        if ($numbers === []) {
            $pdo->rollBack();

            return [
                'success' => false,
                'message' => 'No active WhatsApp destination is configured',
                'error' => 'no_active_destination',
            ];
        }

        $method = effective_destination_selection_method($widget, count($numbers));

        if ($method === 'single' || count($numbers) === 1) {
            $selected = $numbers[0];
        } elseif ($method === 'round_robin') {
            $count = count($numbers);
            $currentIndex = (int) ($widget['round_robin_next_index'] ?? 0);
            $safeIndex = $count > 0 ? ($currentIndex % $count) : 0;
            $selected = $numbers[$safeIndex];
            $nextIndex = ($safeIndex + 1) % $count;

            $updateStmt = $pdo->prepare(
                'UPDATE widgets SET round_robin_next_index = :round_robin_next_index, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $updateStmt->execute([
                'round_robin_next_index' => $nextIndex,
                'id' => $widgetId,
            ]);

            if (channel_schema_ready()) {
                update_widget_channel_selection_method($widgetId, WIDGET_CHANNEL_WHATSAPP, 'round_robin', $nextIndex);
            }
        } else {
            $selected = $numbers[array_rand($numbers)];
        }

        $fullNumber = clean_phone_number((string) ($selected['full_number'] ?? ''));
        if ($fullNumber === '') {
            $fullNumber = clean_phone_number((string) ($selected['country_code'] ?? ''))
                . clean_phone_number((string) ($selected['number'] ?? ''));
        }

        if ($fullNumber === '' || !validate_phone_number($fullNumber)) {
            $pdo->rollBack();

            return ['success' => false, 'message' => 'No active destination'];
        }

        $pdo->commit();

        return [
            'success' => true,
            'channel' => WIDGET_CHANNEL_WHATSAPP,
            'country_code' => (string) ($selected['country_code'] ?? ''),
            'number' => (string) ($selected['number'] ?? ''),
            'full_number' => $fullNumber,
            'selection_method' => $method,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['success' => false, 'message' => 'Unable to resolve destination'];
    }
}

function destination_distribution_label(array $widget, ?int $destinationCount = null): string
{
    $count = $destinationCount ?? count(widget_phone_list($widget));
    if ($count === 0) {
        return t('distribution.none');
    }
    if ($count === 1) {
        return t('distribution.one_number');
    }

    $method = effective_destination_selection_method($widget, $count);
    if ($method === 'round_robin') {
        return t('distribution.summary_round_robin', ['count' => (string) $count]);
    }
    if ($method === 'random') {
        return t('distribution.summary_random', ['count' => (string) $count]);
    }

    return t('distribution.summary_multiple', ['count' => (string) $count]);
}

function client_destination_status_label(array $widget): string
{
    $count = count(widget_phone_list($widget));
    if ($count <= 1) {
        return '';
    }

    $method = effective_destination_selection_method($widget, $count);
    if ($method === 'round_robin') {
        return t('distribution.client_round_robin', ['count' => (string) $count]);
    }

    return t('distribution.client_random', ['count' => (string) $count]);
}

function client_active_numbers_label(array $widget): string
{
    $numbers = widget_phone_list($widget);
    if ($numbers === []) {
        return t('widget_status.no_destination');
    }

    $count = count($numbers);
    if ($count === 1) {
        return t('distribution.one_number');
    }

    return t('distribution.summary_multiple', ['count' => (string) $count]);
}

function save_widget_phone_numbers(int $widgetId, array $numbers): bool
{
    $widget = find_widget_by_id($widgetId);
    $update = $numbers === []
        ? build_empty_phone_widget_update($widget ?? [])
        : build_phone_widget_update($numbers, $widget ?? []);
    if ($update === null) {
        return false;
    }

    update_widget_phone_fields($widgetId, $update);

    return true;
}

function sanitize_client_phone_manual_input(array $post, ?array $existingWidget = null, bool $allowEmpty = false): ?array
{
    return sanitize_phone_numbers_from_post($post, 'manual_numbers', $existingWidget, $allowEmpty);
}

function update_widget_phone_fields(int $widgetId, array $data): void
{
    $allowed = [
        'whatsapp_country_code' => true,
        'whatsapp_number' => true,
        'use_random_numbers' => true,
        'random_numbers_json' => true,
        'destination_selection_method' => true,
        'round_robin_next_index' => true,
    ];
    $filtered = array_intersect_key($data, $allowed);
    if ($filtered === []) {
        return;
    }

    $assignments = [];
    foreach (array_keys($filtered) as $column) {
        $assignments[] = $column . ' = :' . $column;
    }

    $filtered['id'] = $widgetId;
    $sql = 'UPDATE widgets SET ' . implode(', ', $assignments) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
    $stmt = db()->prepare($sql);
    $stmt->execute($filtered);

    if (channel_schema_ready()) {
        sync_whatsapp_destinations_from_legacy($widgetId);
    }

    refresh_widget_destination_status($widgetId);
}

function find_widget_with_owner(int $widgetId): ?array
{
    $stmt = db()->prepare(
        'SELECT w.*, u.name AS owner_name, u.email AS owner_email
         FROM widgets w
         LEFT JOIN users u ON u.id = w.user_id
         WHERE w.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $widgetId]);
    $widget = $stmt->fetch();

    return $widget ?: null;
}

function format_widget_owner_display(array $widget): string
{
    $ownerName = trim((string) ($widget['owner_name'] ?? ''));
    $ownerEmail = trim((string) ($widget['owner_email'] ?? ''));

    if ($ownerName !== '' && $ownerEmail !== '') {
        return $ownerName . ' · ' . $ownerEmail;
    }

    if ($ownerName !== '') {
        return $ownerName;
    }

    return t('meta.no_client_assigned');
}

function ensure_website_name_schema(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (!database_table_exists('widgets')) {
        return;
    }

    if (!table_has_column('widgets', 'website_name')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN website_name VARCHAR(120) NULL DEFAULT NULL AFTER website_domain"
        );
    }
}

function ensure_greeting_open_behavior_schema(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (!database_table_exists('widgets')) {
        return;
    }

    if (!table_has_column('widgets', 'greeting_open_behavior')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN greeting_open_behavior VARCHAR(30) NOT NULL DEFAULT 'auto_delay' AFTER greeting_delay_seconds"
        );
    }
}

function normalize_greeting_open_behavior(string $value): string
{
    return in_array($value, ['auto_delay', 'click_only'], true) ? $value : 'auto_delay';
}

function ensure_greeting_allow_phone_plus_schema(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (!database_table_exists('widgets')) {
        return;
    }

    if (!table_has_column('widgets', 'greeting_allow_phone_plus')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN greeting_allow_phone_plus TINYINT(1) NOT NULL DEFAULT 1 AFTER greeting_phone_required"
        );
    }
}

function ensure_greeting_phone_submit_button_id_schema(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (!database_table_exists('widgets')) {
        return;
    }

    if (!table_has_column('widgets', 'greeting_phone_submit_button_id')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN greeting_phone_submit_button_id VARCHAR(80) NULL DEFAULT NULL AFTER greeting_submit_text"
        );
    }
}

function ensure_consent_notice_and_telegram_styles_schema(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (!database_table_exists('widgets')) {
        return;
    }

    if (!table_has_column('widgets', 'consent_notice_enabled')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN consent_notice_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER greeting_capture_phone"
        );
    }

    if (!table_has_column('widgets', 'consent_notice_text')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN consent_notice_text VARCHAR(500) NULL DEFAULT NULL AFTER consent_notice_enabled"
        );
    }

    if (!table_has_column('widgets', 'telegram_desktop_style')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN telegram_desktop_style VARCHAR(40) NOT NULL DEFAULT 'style-4' AFTER mobile_style"
        );
    }

    if (!table_has_column('widgets', 'telegram_mobile_style')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN telegram_mobile_style VARCHAR(40) NOT NULL DEFAULT 'style-4' AFTER telegram_desktop_style"
        );
    }
}

/**
 * Resolve consent notice copy for a widget. Empty when the notice is disabled.
 */
function widget_consent_notice_text(array $widget, array $readyChannels = []): string
{
    if (empty($widget['consent_notice_enabled'])) {
        return '';
    }

    $custom = trim((string) ($widget['consent_notice_text'] ?? ''));
    if ($custom !== '') {
        return $custom;
    }

    return t('widget.consent.channel_neutral');
}

function filter_widget_data_for_existing_columns(array $data): array
{
    if (!database_table_exists('widgets')) {
        return $data;
    }

    foreach (array_keys($data) as $column) {
        if (!table_has_column('widgets', $column)) {
            unset($data[$column]);
        }
    }

    return $data;
}

function update_widget_admin(int $widgetId, array $data): void
{
    ensure_greeting_open_behavior_schema();
    ensure_greeting_allow_phone_plus_schema();
    ensure_greeting_phone_submit_button_id_schema();
    ensure_consent_notice_and_telegram_styles_schema();
    $channelMode = (string) ($data['channel_mode'] ?? '');
    unset($data['channel_mode'], $data['widget_status']);
    $data = filter_widget_data_for_existing_columns($data);
    $assignments = array_map(static fn ($column) => $column . ' = :' . $column, array_keys($data));
    $data['id'] = $widgetId;
    $sql = 'UPDATE widgets SET ' . implode(', ', $assignments) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
    $stmt = db()->prepare($sql);
    $stmt->execute($data);

    if (channel_schema_ready()) {
        sync_whatsapp_destinations_from_legacy($widgetId);
        if (in_array($channelMode, ['whatsapp_only', 'telegram_only', 'both'], true)) {
            $result = save_widget_channel_config($widgetId, ['mode' => $channelMode]);
            if (!$result['ok']) {
                throw new InvalidArgumentException(implode(' ', $result['errors']));
            }
        }
    }

    refresh_widget_destination_status($widgetId);
}

function reassign_widget_owner(int $widgetId, int $userId): void
{
    $stmt = db()->prepare('UPDATE widgets SET user_id = :user_id, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute(['id' => $widgetId, 'user_id' => $userId]);
}

function decode_random_numbers(?string $json): array
{
    $rows = json_decode((string) $json, true);
    return is_array($rows) ? $rows : [];
}

function format_whatsapp_display(array $widget): string
{
    $numbers = widget_phone_list($widget);
    if ($numbers === []) {
        return t('widget_status.no_destination');
    }

    return destination_distribution_label($widget, count($numbers));
}

function widget_destination_summary(array $widget): array
{
    $numbers = widget_phone_list($widget);
    $destinationCount = count($numbers);
    $method = effective_destination_selection_method($widget, $destinationCount);

    if ($destinationCount === 0) {
        return [
            'summary' => t('widget_destinations.none'),
            'state' => 'setup_required',
            'tooltip' => '',
        ];
    }

    if ($destinationCount === 1) {
        $number = $numbers[0];
        $tooltip = trim((string) ($number['country_code'] ?? '') . ' ' . (string) ($number['number'] ?? ''));

        return [
            'summary' => t('widget_destinations.one'),
            'state' => 'active',
            'tooltip' => $tooltip,
        ];
    }

    if ($method === 'round_robin') {
        return [
            'summary' => t('widget_destinations.round_robin', ['count' => (string) $destinationCount]),
            'state' => 'active',
            'tooltip' => t('widget_destinations.round_robin_tooltip', ['count' => (string) $destinationCount]),
        ];
    }

    return [
        'summary' => t('widget_destinations.random', ['count' => (string) $destinationCount]),
        'state' => 'active',
        'tooltip' => t('widget_destinations.random_tooltip', ['count' => (string) $destinationCount]),
    ];
}

function render_widget_destination_summary(array $widget): void
{
    $info = widget_destination_summary($widget);
    $titleAttr = $info['tooltip'] !== '' ? ' title="' . e($info['tooltip']) . '"' : '';

    if ($info['state'] === 'setup_required') {
        echo '<div class="widget-destination-summary widget-destination-summary--setup">';
        echo '<span class="widget-destination-summary-text">' . e($info['summary']) . '</span>';
        echo '<span class="widget-destination-setup-required">' . e(t('widget_destinations.setup_required')) . '</span>';
        echo '</div>';

        return;
    }

    echo '<span class="widget-destination-summary-text"' . $titleAttr . '>' . e($info['summary']) . '</span>';
}

function generate_temporary_password(int $length = 14): string
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%^&*';
    $max = strlen($alphabet) - 1;
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $max)];
    }

    return $password;
}

function validate_client_password(string $password, string $confirmPassword): array
{
    $errors = [];

    if ($password === '') {
        $errors[] = t('validation.password_required');
    }
    if ($confirmPassword === '') {
        $errors[] = t('validation.confirm_password_required');
    }
    if ($password !== '' && $confirmPassword !== '' && $password !== $confirmPassword) {
        $errors[] = t('validation.password_mismatch');
    }
    if ($password !== '' && strlen($password) < 8) {
        $errors[] = t('validation.password_min_length');
    }

    return $errors;
}

function database_table_exists(string $table): bool
{
    static $cache = [];

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :table'
    );
    $stmt->execute(['table' => $table]);
    $cache[$table] = (int) $stmt->fetchColumn() > 0;

    return $cache[$table];
}

function table_has_column(string $table, string $column): bool
{
    static $cache = [];

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column'
    );
    $stmt->execute([
        'table' => $table,
        'column' => $column,
    ]);
    $cache[$key] = ((int) $stmt->fetchColumn()) > 0;

    return $cache[$key];
}

function client_widget_count(int $clientId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM widgets WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $clientId]);

    return (int) $stmt->fetchColumn();
}

function client_lead_count(int $clientId): int
{
    return count_active_leads($clientId, false);
}

function delete_client_account(int $clientId, string $widgetMode, int $superadminId): array
{
    if (!in_array($widgetMode, ['delete_all', 'reassign'], true)) {
        return ['success' => false, 'message' => t('validation.invalid_delete_option')];
    }

    $client = find_client_user($clientId);
    if (!$client) {
        return ['success' => false, 'message' => t('validation.client_not_found')];
    }

    if ($clientId === $superadminId) {
        return ['success' => false, 'message' => t('validation.cannot_delete_self')];
    }

    $pdo = db();

    try {
        $pdo->beginTransaction();

        if ($widgetMode === 'reassign') {
            $stmt = $pdo->prepare('UPDATE widgets SET user_id = :superadmin_id WHERE user_id = :client_id');
            $stmt->execute([
                'superadmin_id' => $superadminId,
                'client_id' => $clientId,
            ]);

            if (database_table_exists('widget_leads')) {
                $stmt = $pdo->prepare('UPDATE widget_leads SET user_id = :superadmin_id WHERE user_id = :client_id');
                $stmt->execute([
                    'superadmin_id' => $superadminId,
                    'client_id' => $clientId,
                ]);
            }
        } else {
            if (database_table_exists('widget_leads')) {
                $stmt = $pdo->prepare('DELETE FROM widget_leads WHERE user_id = :client_id');
                $stmt->execute(['client_id' => $clientId]);
            }

            $stmt = $pdo->prepare('DELETE FROM widgets WHERE user_id = :client_id');
            $stmt->execute(['client_id' => $clientId]);
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id AND role = :role');
        $stmt->execute([
            'id' => $clientId,
            'role' => ROLE_CLIENT,
        ]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();

            return ['success' => false, 'message' => t('validation.client_delete_failed')];
        }

        $pdo->commit();

        return [
            'success' => true,
            'mode' => $widgetMode,
            'message' => $widgetMode === 'reassign'
                ? t('flash.client_deleted_reassign')
                : t('flash.client_deleted_all'),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['success' => false, 'message' => t('validation.client_delete_retry')];
    }
}

function dashboard_summary_stats(): array
{
    $clients = db()->query("SELECT COUNT(*) FROM users WHERE role = '" . ROLE_CLIENT . "'")->fetchColumn();
    $activeClients = db()->query("SELECT COUNT(*) FROM users WHERE role = '" . ROLE_CLIENT . "' AND status = '" . USER_STATUS_ACTIVE . "'")->fetchColumn();
    $disabledClients = db()->query("SELECT COUNT(*) FROM users WHERE role = '" . ROLE_CLIENT . "' AND status = '" . USER_STATUS_DISABLED . "'")->fetchColumn();
    $widgets = db()->query('SELECT COUNT(*) FROM widgets')->fetchColumn();

    return [
        'total_clients' => (int) $clients,
        'active_clients' => (int) $activeClients,
        'disabled_clients' => (int) $disabledClients,
        'total_widgets' => (int) $widgets,
        'today_leads' => count_active_leads(null, true),
        'yesterday_leads' => count_yesterday_active_leads(null),
        'total_active_leads' => count_active_leads(null, false),
    ];
}

function search_clients(array $options): array
{
    $page = max(1, (int) ($options['page'] ?? 1));
    $perPage = max(1, min(50, (int) ($options['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    $query = trim((string) ($options['q'] ?? ''));
    $status = (string) ($options['status'] ?? 'all');
    $sort = (string) ($options['sort'] ?? 'newest');

    $where = ["u.role = :role"];
    $params = ['role' => ROLE_CLIENT];

    if ($status === USER_STATUS_ACTIVE || $status === USER_STATUS_DISABLED) {
        $where[] = 'u.status = :status';
        $params['status'] = $status;
    }

    $join = '';
    if ($query !== '') {
        $join = ' LEFT JOIN widgets w ON w.user_id = u.id';
        $where[] = '(u.name LIKE :q_name OR u.email LIKE :q_email OR w.website_domain LIKE :q_domain OR w.widget_name LIKE :q_widget)';
        $like = '%' . $query . '%';
        $params['q_name'] = $like;
        $params['q_email'] = $like;
        $params['q_domain'] = $like;
        $params['q_widget'] = $like;
    }

    $whereSql = implode(' AND ', $where);
    $orderBy = match ($sort) {
        'oldest' => 'u.created_at ASC',
        'name_az' => 'u.name ASC',
        'most_widgets' => 'widget_count DESC, u.name ASC',
        default => 'u.created_at DESC',
    };

    $countSql = 'SELECT COUNT(DISTINCT u.id) FROM users u' . $join . ' WHERE ' . $whereSql;
    $countStmt = db()->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = 'SELECT u.id, u.name, u.email, u.status, u.created_at, u.last_login_at, COUNT(w.id) AS widget_count
            FROM users u
            LEFT JOIN widgets w ON w.user_id = u.id
            WHERE ' . $whereSql . '
            GROUP BY u.id
            ORDER BY ' . $orderBy . '
            LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => (int) max(1, ceil($total / $perPage)),
    ];
}

function widgets_for_user(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM widgets WHERE user_id = :user_id ORDER BY updated_at DESC');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function recent_clients(int $limit = 5): array
{
    $stmt = db()->prepare(
        'SELECT u.id, u.name, u.email, u.status, u.created_at, COUNT(w.id) AS widget_count
         FROM users u
         LEFT JOIN widgets w ON w.user_id = u.id
         WHERE u.role = :role
         GROUP BY u.id
         ORDER BY u.created_at DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute(['role' => ROLE_CLIENT]);
    return $stmt->fetchAll();
}

function recent_widgets(int $limit = 5): array
{
    $stmt = db()->prepare(
        'SELECT w.*, u.name AS owner_name, u.email AS owner_email
         FROM widgets w
         INNER JOIN users u ON u.id = w.user_id
         ORDER BY w.updated_at DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function search_all_widgets(array $options): array
{
    $page = max(1, (int) ($options['page'] ?? 1));
    $perPage = max(1, min(50, (int) ($options['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    $query = trim((string) ($options['q'] ?? ''));

    $where = ['1=1'];
    $params = [];
    if ($query !== '') {

            $where[] = '(w.widget_name LIKE :q_widget OR w.website_domain LIKE :q_domain OR u.name LIKE :q_name OR u.email LIKE :q_email)';
            $like = '%' . $query . '%';
            $params['q_widget'] = $like;
            $params['q_domain'] = $like;
            $params['q_name'] = $like;
            $params['q_email'] = $like;

    }

    $whereSql = implode(' AND ', $where);
    $countStmt = db()->prepare('SELECT COUNT(*) FROM widgets w INNER JOIN users u ON u.id = w.user_id WHERE ' . $whereSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = 'SELECT w.*, u.name AS owner_name, u.email AS owner_email
            FROM widgets w
            INNER JOIN users u ON u.id = w.user_id
            WHERE ' . $whereSql . '
            ORDER BY w.updated_at DESC
            LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return [
        'rows' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => (int) max(1, ceil($total / $perPage)),
    ];
}

function user_status_badge_class(string $status): string
{
    return $status === USER_STATUS_ACTIVE ? 'status-pill status-active' : 'status-pill status-disabled';
}

function feature_status_pill($value): string
{
    $enabled = !empty($value);

    return '<span class="status-pill ' . ($enabled ? 'status-active' : 'status-disabled') . '">'
        . e(translate_feature_status($enabled)) . '</span>';
}

function nav_is_active(string $page): bool
{
    return current_app_page() === $page;
}

function nav_link_class(string $page, array $relatedPages = []): string
{
    $current = current_app_page();
    $active = $current === $page || in_array($current, $relatedPages, true);

    return $active ? 'topnav-link is-active' : 'topnav-link';
}

function render_widget_action_menu(array $widget, array $options = []): void
{
    $widgetId = (int) $widget['id'];
    $showDelete = !empty($options['show_delete']);
    $deleteClientId = (int) ($options['delete_client_id'] ?? 0);
    $showApiKey = !empty($options['show_api_key']);
    $clientName = (string) ($options['client_name'] ?? '');
    ?>
    <div class="row-actions">
        <a class="btn btn-small btn-primary" href="<?= e(app_url('edit-widget.php', ['id' => $widgetId])) ?>"><?= e(t('button.manage')) ?></a>
        <a class="btn btn-small btn-light" href="<?= e(app_url('widget-preview.php', ['id' => $widgetId])) ?>"><?= e(t('button.preview')) ?></a>
        <div class="action-menu" data-action-menu>
            <button type="button" class="btn btn-small btn-light action-menu-toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?= e(t('action.more_actions')) ?>"><?= e('⋯') ?></button>
            <div class="action-menu-panel" role="menu">
                <a role="menuitem" href="<?= e(app_url('edit-widget-phone.php', ['id' => $widgetId])) ?>"><?= e(t('action.phone_number')) ?></a>
                <a role="menuitem" href="<?= e(app_url('admin-client-leads.php', ['client_id' => (int) $widget['user_id'], 'widget_id' => $widgetId])) ?>"><?= e(t('action.leads')) ?></a>
                <a role="menuitem" href="<?= e(app_url('embed-code.php', ['id' => $widgetId])) ?>"><?= e(t('action.embed_code')) ?></a>
                <?php if ($showApiKey): ?>
                    <button
                        type="button"
                        role="menuitem"
                        class="action-menu-button"
                        data-open-api-key-modal
                        data-owner-type="widget"
                        data-owner-id="<?= $widgetId ?>"
                        data-owner-label="<?= e((string) ($widget['widget_name'] ?? '')) ?>"
                        data-client-label="<?= e($clientName) ?>"
                    ><?= e(t('action.widget_api_key')) ?></button>
                <?php endif; ?>
                <?php if ($showDelete): ?>
                    <form method="post" data-confirm="<?= e(t('widget.delete_confirm')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_widget">
                        <input type="hidden" name="widget_id" value="<?= $widgetId ?>">
                        <?php if ($deleteClientId > 0): ?>
                            <input type="hidden" name="client_id" value="<?= $deleteClientId ?>">
                        <?php endif; ?>
                        <button type="submit" class="action-menu-danger" role="menuitem"><?= e(t('button.delete')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function format_datetime(?string $value, ?string $fallback = null): string
{
    if ($value === null || trim($value) === '') {
        return $fallback ?? t('datetime.never');
    }

    return date('M j, Y g:i A', strtotime($value));
}

function validate_uploaded_phone_file(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [t('validation.upload_failed')];
    }

    if (($file['size'] ?? 0) > 1048576) {
        return [t('validation.upload_file_size')];
    }

    $name = strtolower((string) ($file['name'] ?? ''));
    if (!preg_match('/\.(csv|txt)$/', $name)) {
        return [t('validation.upload_file_type')];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, (string) $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'];
    if ($mime !== '' && !in_array($mime, $allowed, true)) {
        return [t('validation.upload_invalid_mime')];
    }

    return [];
}

function normalize_visitor_phone(string $phone): ?array
{
    $result = validate_captured_visitor_phone($phone);

    return !empty($result['valid']) ? $result['normalized'] : null;
}

function widget_allows_phone_plus(array $widget): bool
{
    return !empty($widget['greeting_allow_phone_plus']);
}

function captured_phone_has_invalid_plus_placement(string $phone): bool
{
    $plusCount = substr_count($phone, '+');
    if ($plusCount === 0) {
        return false;
    }

    if ($plusCount > 1) {
        return true;
    }

    return !str_starts_with($phone, '+');
}

function validate_captured_visitor_phone(string $phone, bool $allowPhonePlus = true): array
{
    $raw = trim(strip_tags($phone));
    if ($raw === '') {
        return ['valid' => false, 'message' => t('widget.phone_validation.empty')];
    }

    if (!$allowPhonePlus && str_contains($raw, '+')) {
        return ['valid' => false, 'message' => t('widget.phone_validation.without_plus')];
    }

    if ($allowPhonePlus && captured_phone_has_invalid_plus_placement($raw)) {
        return ['valid' => false, 'message' => t('widget.phone_validation.invalid')];
    }

    $pattern = $allowPhonePlus ? '/^\+?[0-9\s().-]+$/' : '/^[0-9\s().-]+$/';
    if (!preg_match($pattern, $raw)) {
        return ['valid' => false, 'message' => t('widget.phone_validation.invalid')];
    }

    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if (strlen($digits) < 8) {
        return ['valid' => false, 'message' => t('widget.phone_validation.invalid')];
    }

    if (strlen($digits) > 15) {
        return ['valid' => false, 'message' => t('widget.phone_validation.invalid')];
    }

    $display = $allowPhonePlus ? '+' . $digits : $digits;

    return [
        'valid' => true,
        'normalized' => [
            'visitor_phone' => $display,
            'visitor_country_code' => null,
            'visitor_full_phone' => $digits,
        ],
    ];
}

function is_valid_phone_submit_button_id(string $id): bool
{
    return $id === '' || (bool) preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,79}$/', $id);
}

function resolve_greeting_phone_submit_button_id(array $widget): string
{
    $custom = trim((string) ($widget['greeting_phone_submit_button_id'] ?? ''));
    if ($custom !== '' && is_valid_phone_submit_button_id($custom)) {
        return $custom;
    }

    return 'ctcw-phone-submit-' . (int) ($widget['id'] ?? 0);
}

function find_recent_widget_lead_id(int $widgetId, string $fullPhone): ?int
{
    $sql = 'SELECT id FROM widget_leads
            WHERE widget_id = :widget_id
              AND visitor_full_phone = :phone
              AND created_at >= (UTC_TIMESTAMP() - INTERVAL 1 MINUTE)';
    if (table_has_column('widget_leads', 'deleted_at')) {
        $sql .= ' AND deleted_at IS NULL';
    }
    $sql .= ' ORDER BY id DESC LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute(['widget_id' => $widgetId, 'phone' => $fullPhone]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int) $id : null;
}

function lead_recently_saved(int $widgetId, string $fullPhone): bool
{
    $stmt = db()->prepare(
        'SELECT id FROM widget_leads
         WHERE widget_id = :widget_id AND visitor_full_phone = :phone
           AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE)
         LIMIT 1'
    );
    $stmt->execute(['widget_id' => $widgetId, 'phone' => $fullPhone]);
    return (bool) $stmt->fetchColumn();
}

function insert_widget_lead(array $widget, array $lead): int
{
    $clientId = (int) ($widget['user_id'] ?? 0);
    $columns = [
        'widget_id', 'user_id', 'visitor_phone', 'visitor_country_code', 'visitor_full_phone',
        'source_domain', 'source_url', 'page_title', 'whatsapp_redirect_url', 'ip_address', 'user_agent',
    ];
    $values = [
        'widget_id' => (int) $widget['id'],
        'user_id' => $clientId,
        'visitor_phone' => $lead['visitor_phone'],
        'visitor_country_code' => $lead['visitor_country_code'],
        'visitor_full_phone' => $lead['visitor_full_phone'],
        'source_domain' => $lead['source_domain'],
        'source_url' => $lead['source_url'],
        'page_title' => $lead['page_title'],
        'whatsapp_redirect_url' => $lead['whatsapp_redirect_url'],
        'ip_address' => $lead['ip_address'],
        'user_agent' => $lead['user_agent'],
    ];

    if (table_has_column('widget_leads', 'client_id')) {
        $columns[] = 'client_id';
        $values['client_id'] = $clientId;
    }

    $channelFields = [
        'channel',
        'channel_destination_id',
        'destination_type',
        'destination_name',
        'destination_snapshot',
        'channel_selected_at',
        'destination_resolved_at',
        'redirect_attempted_at',
        'fallback_type',
    ];
    foreach ($channelFields as $field) {
        if (!table_has_column('widget_leads', $field) || !array_key_exists($field, $lead)) {
            continue;
        }
        $columns[] = $field;
        $values[$field] = $lead[$field];
    }

    $placeholders = array_map(static fn ($column) => ':' . $column, $columns);
    $sql = 'INSERT INTO widget_leads (' . implode(', ', $columns) . ', created_at) VALUES (' . implode(', ', $placeholders) . ', UTC_TIMESTAMP())';
    $stmt = db()->prepare($sql);
    $stmt->execute($values);

    return (int) db()->lastInsertId();
}

/**
 * Update channel/destination metadata on an existing lead (multi-channel flow).
 *
 * @param array<string, mixed> $fields
 */
function update_widget_lead_channel_events(int $leadId, int $widgetId, array $fields): bool
{
    if ($leadId <= 0 || $widgetId <= 0 || !table_has_column('widget_leads', 'channel')) {
        return false;
    }

    $allowed = [
        'channel' => true,
        'channel_destination_id' => true,
        'destination_type' => true,
        'destination_name' => true,
        'destination_snapshot' => true,
        'channel_selected_at' => true,
        'destination_resolved_at' => true,
        'redirect_attempted_at' => true,
        'fallback_type' => true,
        'whatsapp_redirect_url' => true,
    ];
    $filtered = array_intersect_key($fields, $allowed);
    if ($filtered === []) {
        return false;
    }

    $assignments = [];
    foreach (array_keys($filtered) as $column) {
        if ($column === 'channel_selected_at' && $filtered[$column] === 'now') {
            $assignments[] = 'channel_selected_at = UTC_TIMESTAMP()';
            unset($filtered[$column]);
            continue;
        }
        if ($column === 'destination_resolved_at' && $filtered[$column] === 'now') {
            $assignments[] = 'destination_resolved_at = UTC_TIMESTAMP()';
            unset($filtered[$column]);
            continue;
        }
        if ($column === 'redirect_attempted_at' && $filtered[$column] === 'now') {
            $assignments[] = 'redirect_attempted_at = UTC_TIMESTAMP()';
            unset($filtered[$column]);
            continue;
        }
        $assignments[] = $column . ' = :' . $column;
    }

    if ($assignments === []) {
        return false;
    }

    $filtered['id'] = $leadId;
    $filtered['widget_id'] = $widgetId;
    $sql = 'UPDATE widget_leads SET ' . implode(', ', $assignments)
        . ' WHERE id = :id AND widget_id = :widget_id AND deleted_at IS NULL';
    $stmt = db()->prepare($sql);
    $stmt->execute($filtered);

    return $stmt->rowCount() > 0;
}

function search_widget_leads(int $widgetId, array $options): array
{
    $clientId = (int) ($options['client_id'] ?? 0);

    return search_client_leads(array_merge($options, [
        'widget_id' => $widgetId,
        'client_id' => $clientId > 0 ? $clientId : 0,
        'recycle_bin' => false,
    ]));
}

function widget_leads_for_export(int $widgetId, array $options): array
{
    $clientId = (int) ($options['client_id'] ?? 0);

    return client_leads_for_export(array_merge($options, [
        'widget_id' => $widgetId,
        'client_id' => $clientId > 0 ? $clientId : 0,
        'recycle_bin' => false,
    ]));
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

require_once __DIR__ . '/channels.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/channel-destinations.php';
require_once __DIR__ . '/lead-management.php';
require_once __DIR__ . '/lead-pagination.php';
require_once __DIR__ . '/api-credentials.php';

function ensure_widget_activation_schema(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (!database_table_exists('widgets')) {
        return;
    }

    if (!table_has_column('widgets', 'widget_status')) {
        db()->exec(
            "ALTER TABLE widgets
             ADD COLUMN widget_status VARCHAR(30) NOT NULL DEFAULT 'setup_required' AFTER show_global"
        );

        db()->exec("UPDATE widgets SET widget_status = 'disabled' WHERE show_global = 0");
        db()->exec(
            "UPDATE widgets
             SET widget_status = 'active'
             WHERE show_global = 1
               AND (
                 (use_random_numbers = 1 AND random_numbers_json IS NOT NULL AND TRIM(random_numbers_json) NOT IN ('', '[]'))
                 OR (whatsapp_number IS NOT NULL AND TRIM(whatsapp_number) <> '')
               )"
        );
        db()->exec(
            "UPDATE widgets
             SET widget_status = 'setup_required'
             WHERE show_global = 1
               AND widget_status NOT IN ('active', 'disabled', 'paused')"
        );
    }
}

try {
    ensure_widget_activation_schema();
    ensure_website_name_schema();
    ensure_greeting_open_behavior_schema();
    ensure_greeting_allow_phone_plus_schema();
    ensure_greeting_phone_submit_button_id_schema();
    ensure_consent_notice_and_telegram_styles_schema();
    ensure_lead_recycle_schema();
} catch (Throwable $exception) {
    // Leave connection errors to the calling page; schema ensure runs when DB is available.
}

maybe_redirect_legacy_php_request();
