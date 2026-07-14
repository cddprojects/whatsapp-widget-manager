<?php
declare(strict_types=1);

const API_CREDENTIAL_OWNER_CLIENT = 'client';
const API_CREDENTIAL_OWNER_WIDGET = 'widget';
const API_CREDENTIAL_TYPE_CLIENT = 'client_api';
const API_CREDENTIAL_TYPE_WIDGET = 'widget_api';
const API_KEY_PREFIX_CLIENT = 'ctc_client_live_';
const API_KEY_PREFIX_WIDGET = 'ctc_widget_live_';
const API_RATE_LIMIT_PER_MINUTE = 60;
const API_SUMMARY_MAX_RANGE_DAYS = 366;

function ensure_api_credentials_schema(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (!database_table_exists('api_credentials')) {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS api_credentials (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                owner_type ENUM('client', 'widget') NOT NULL,
                owner_id INT UNSIGNED NOT NULL,
                credential_type ENUM('client_api', 'widget_api') NOT NULL,
                key_prefix VARCHAR(64) NOT NULL,
                key_last_four VARCHAR(8) NOT NULL,
                key_hash CHAR(64) NOT NULL,
                key_ciphertext TEXT NOT NULL,
                key_nonce VARCHAR(64) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_used_at DATETIME NULL DEFAULT NULL,
                revoked_at DATETIME NULL DEFAULT NULL,
                created_by INT UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_api_credentials_owner_type (owner_type, owner_id, credential_type),
                UNIQUE KEY uq_api_credentials_hash (key_hash),
                KEY idx_api_credentials_owner (owner_type, owner_id),
                KEY idx_api_credentials_active (is_active, revoked_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    if (!database_table_exists('api_request_logs')) {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS api_request_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                client_credential_id BIGINT UNSIGNED NULL DEFAULT NULL,
                widget_credential_id BIGINT UNSIGNED NULL DEFAULT NULL,
                client_id INT UNSIGNED NULL DEFAULT NULL,
                widget_id INT UNSIGNED NULL DEFAULT NULL,
                endpoint VARCHAR(191) NOT NULL,
                http_method VARCHAR(16) NOT NULL,
                response_status SMALLINT UNSIGNED NOT NULL,
                period_type VARCHAR(64) NULL DEFAULT NULL,
                requester_ip VARCHAR(45) NULL DEFAULT NULL,
                user_agent VARCHAR(512) NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_api_request_logs_created (created_at),
                KEY idx_api_request_logs_client (client_id),
                KEY idx_api_request_logs_widget (widget_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    if (!database_table_exists('api_rate_limits')) {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS api_rate_limits (
                bucket_key CHAR(64) NOT NULL,
                window_started_at DATETIME NOT NULL,
                request_count INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (bucket_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}

function api_key_pepper(): string
{
    $pepper = trim((string) (getenv('API_KEY_PEPPER') ?: ''));
    if ($pepper === '' || strlen($pepper) < 32) {
        throw new RuntimeException('API_KEY_PEPPER is not configured.');
    }

    return $pepper;
}

function api_key_encryption_key_bytes(): string
{
    $encoded = trim((string) (getenv('API_KEY_ENCRYPTION_KEY') ?: ''));
    $decoded = base64_decode($encoded, true);
    if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RuntimeException('API_KEY_ENCRYPTION_KEY is not configured.');
    }

    return $decoded;
}

function api_credentials_crypto_ready(): bool
{
    try {
        api_key_pepper();
        api_key_encryption_key_bytes();

        return extension_loaded('sodium');
    } catch (Throwable $exception) {
        return false;
    }
}

function hash_api_key(string $rawKey): string
{
    return hash_hmac('sha256', $rawKey, api_key_pepper());
}

function encrypt_api_key(string $rawKey): array
{
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = sodium_crypto_secretbox($rawKey, $nonce, api_key_encryption_key_bytes());

    return [
        'ciphertext' => base64_encode($ciphertext),
        'nonce' => base64_encode($nonce),
    ];
}

function decrypt_api_key(string $ciphertextBase64, string $nonceBase64): string
{
    $ciphertext = base64_decode($ciphertextBase64, true);
    $nonce = base64_decode($nonceBase64, true);
    if ($ciphertext === false || $nonce === false) {
        throw new RuntimeException('Unable to decrypt API key.');
    }

    $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, api_key_encryption_key_bytes());
    if ($plain === false) {
        throw new RuntimeException('Unable to decrypt API key.');
    }

    return $plain;
}

function generate_raw_api_key(string $credentialType): string
{
    $prefix = $credentialType === API_CREDENTIAL_TYPE_WIDGET
        ? API_KEY_PREFIX_WIDGET
        : API_KEY_PREFIX_CLIENT;

    return $prefix . bin2hex(random_bytes(24));
}

function mask_api_key(string $prefix, string $lastFour): string
{
    return $prefix . str_repeat('•', 12) . $lastFour;
}

function api_key_display_prefix(string $rawKey): string
{
    return substr($rawKey, 0, 20);
}

function find_api_credential(string $ownerType, int $ownerId, string $credentialType): ?array
{
    ensure_api_credentials_schema();

    $stmt = db()->prepare(
        'SELECT * FROM api_credentials
         WHERE owner_type = :owner_type
           AND owner_id = :owner_id
           AND credential_type = :credential_type
         LIMIT 1'
    );
    $stmt->execute([
        'owner_type' => $ownerType,
        'owner_id' => $ownerId,
        'credential_type' => $credentialType,
    ]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function find_api_credential_by_hash(string $keyHash): ?array
{
    ensure_api_credentials_schema();

    $stmt = db()->prepare(
        'SELECT * FROM api_credentials
         WHERE key_hash = :key_hash
           AND revoked_at IS NULL
         LIMIT 1'
    );
    $stmt->execute(['key_hash' => $keyHash]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function api_credential_is_usable(?array $credential): bool
{
    if ($credential === null) {
        return false;
    }

    if ((int) ($credential['is_active'] ?? 0) !== 1) {
        return false;
    }

    if (!empty($credential['revoked_at'])) {
        return false;
    }

    return true;
}

function api_credential_public_view(?array $credential): array
{
    if ($credential === null) {
        return [
            'exists' => false,
            'status' => 'missing',
            'masked_key' => '',
            'created_at' => null,
            'last_used_at' => null,
            'is_active' => false,
        ];
    }

    $isActive = api_credential_is_usable($credential);

    return [
        'exists' => true,
        'status' => $isActive ? 'active' : 'disabled',
        'masked_key' => mask_api_key(
            (string) $credential['key_prefix'],
            (string) $credential['key_last_four']
        ),
        'created_at' => $credential['created_at'] ?? null,
        'last_used_at' => $credential['last_used_at'] ?? null,
        'is_active' => $isActive,
    ];
}

function create_or_replace_api_credential(
    string $ownerType,
    int $ownerId,
    string $credentialType,
    ?int $createdBy
): array {
    ensure_api_credentials_schema();

    if (!api_credentials_crypto_ready()) {
        throw new RuntimeException('API key encryption is not configured on this server.');
    }

    $rawKey = generate_raw_api_key($credentialType);
    $encrypted = encrypt_api_key($rawKey);
    $hash = hash_api_key($rawKey);
    $now = gmdate('Y-m-d H:i:s');

    $stmt = db()->prepare(
        'INSERT INTO api_credentials (
            owner_type, owner_id, credential_type, key_prefix, key_last_four,
            key_hash, key_ciphertext, key_nonce, is_active, created_at, updated_at,
            last_used_at, revoked_at, created_by
         ) VALUES (
            :owner_type, :owner_id, :credential_type, :key_prefix, :key_last_four,
            :key_hash, :key_ciphertext, :key_nonce, 1, :created_at, :updated_at,
            NULL, NULL, :created_by
         )
         ON DUPLICATE KEY UPDATE
            key_prefix = VALUES(key_prefix),
            key_last_four = VALUES(key_last_four),
            key_hash = VALUES(key_hash),
            key_ciphertext = VALUES(key_ciphertext),
            key_nonce = VALUES(key_nonce),
            is_active = 1,
            updated_at = VALUES(updated_at),
            last_used_at = NULL,
            revoked_at = NULL,
            created_by = VALUES(created_by),
            created_at = VALUES(created_at)'
    );
    $stmt->execute([
        'owner_type' => $ownerType,
        'owner_id' => $ownerId,
        'credential_type' => $credentialType,
        'key_prefix' => api_key_display_prefix($rawKey),
        'key_last_four' => substr($rawKey, -4),
        'key_hash' => $hash,
        'key_ciphertext' => $encrypted['ciphertext'],
        'key_nonce' => $encrypted['nonce'],
        'created_at' => $now,
        'updated_at' => $now,
        'created_by' => $createdBy,
    ]);

    $credential = find_api_credential($ownerType, $ownerId, $credentialType);
    if ($credential === null) {
        throw new RuntimeException('Failed to store API credential.');
    }

    return [
        'credential' => $credential,
        'raw_key' => $rawKey,
        'view' => api_credential_public_view($credential),
    ];
}

function set_api_credential_active(string $ownerType, int $ownerId, string $credentialType, bool $active): ?array
{
    $credential = find_api_credential($ownerType, $ownerId, $credentialType);
    if ($credential === null) {
        return null;
    }

    $now = gmdate('Y-m-d H:i:s');
    if ($active) {
        $stmt = db()->prepare(
            'UPDATE api_credentials
             SET is_active = 1, revoked_at = NULL, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'updated_at' => $now,
            'id' => (int) $credential['id'],
        ]);
    } else {
        $stmt = db()->prepare(
            'UPDATE api_credentials
             SET is_active = 0, revoked_at = :revoked_at, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'revoked_at' => $now,
            'updated_at' => $now,
            'id' => (int) $credential['id'],
        ]);
    }

    return find_api_credential($ownerType, $ownerId, $credentialType);
}

function reveal_api_credential_key(string $ownerType, int $ownerId, string $credentialType): string
{
    $credential = find_api_credential($ownerType, $ownerId, $credentialType);
    if ($credential === null) {
        throw new RuntimeException('API credential not found.');
    }

    return decrypt_api_key(
        (string) $credential['key_ciphertext'],
        (string) $credential['key_nonce']
    );
}

function touch_api_credential_last_used(int $credentialId): void
{
    $stmt = db()->prepare(
        'UPDATE api_credentials
         SET last_used_at = :last_used_at, updated_at = updated_at
         WHERE id = :id'
    );
    $stmt->execute([
        'last_used_at' => gmdate('Y-m-d H:i:s'),
        'id' => $credentialId,
    ]);
}

function request_authorization_header(): string
{
    $header = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    if ($header === '') {
        $header = trim((string) ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
    }

    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp((string) $name, 'Authorization') === 0) {
                    return trim((string) $value);
                }
            }
        }
    }

    return $header;
}

function extract_bearer_token(?string $header): string
{
    $header = trim((string) $header);
    if ($header === '') {
        return '';
    }

    if (preg_match('/^Bearer\s+(\S+)$/i', $header, $matches) === 1) {
        return trim($matches[1]);
    }

    return '';
}

function api_json_error(string $code, string $message, int $status, array $extra = []): void
{
    $payload = array_merge([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message,
        ],
    ], $extra);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function api_json_success(array $payload, int $status = 200): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function request_ip_address(): string
{
    $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($forwarded !== '') {
        $parts = explode(',', $forwarded);

        return trim($parts[0]);
    }

    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function log_api_request(
    ?int $clientCredentialId,
    ?int $widgetCredentialId,
    ?int $clientId,
    ?int $widgetId,
    string $endpoint,
    string $method,
    int $status,
    ?string $periodType
): void {
    try {
        ensure_api_credentials_schema();
        $stmt = db()->prepare(
            'INSERT INTO api_request_logs (
                client_credential_id, widget_credential_id, client_id, widget_id,
                endpoint, http_method, response_status, period_type, requester_ip, user_agent, created_at
             ) VALUES (
                :client_credential_id, :widget_credential_id, :client_id, :widget_id,
                :endpoint, :http_method, :response_status, :period_type, :requester_ip, :user_agent, :created_at
             )'
        );
        $stmt->execute([
            'client_credential_id' => $clientCredentialId,
            'widget_credential_id' => $widgetCredentialId,
            'client_id' => $clientId,
            'widget_id' => $widgetId,
            'endpoint' => substr($endpoint, 0, 191),
            'http_method' => substr($method, 0, 16),
            'response_status' => $status,
            'period_type' => $periodType,
            'requester_ip' => substr(request_ip_address(), 0, 45),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $exception) {
        // Never block API responses on audit failures, and never log secrets.
    }
}

function enforce_api_rate_limit(int $clientCredentialId, int $widgetCredentialId): void
{
    ensure_api_credentials_schema();
    $bucketSource = $clientCredentialId . ':' . $widgetCredentialId . ':' . gmdate('Y-m-d H:i');
    $bucketKey = hash('sha256', $bucketSource);
    $now = gmdate('Y-m-d H:i:s');

    $stmt = db()->prepare('SELECT window_started_at, request_count FROM api_rate_limits WHERE bucket_key = :bucket_key LIMIT 1');
    $stmt->execute(['bucket_key' => $bucketKey]);
    $row = $stmt->fetch();

    if (!$row) {
        $insert = db()->prepare(
            'INSERT INTO api_rate_limits (bucket_key, window_started_at, request_count)
             VALUES (:bucket_key, :window_started_at, 1)'
        );
        $insert->execute([
            'bucket_key' => $bucketKey,
            'window_started_at' => $now,
        ]);

        return;
    }

    $count = (int) $row['request_count'];
    if ($count >= API_RATE_LIMIT_PER_MINUTE) {
        header('Retry-After: 60');
        api_json_error('rate_limited', 'Too many requests. Please retry later.', 429);
    }

    $update = db()->prepare(
        'UPDATE api_rate_limits
         SET request_count = request_count + 1
         WHERE bucket_key = :bucket_key'
    );
    $update->execute(['bucket_key' => $bucketKey]);
}

function authenticate_api_key_pair(): array
{
    if (!empty($_GET['client_key']) || !empty($_GET['widget_key']) || !empty($_GET['api_key'])) {
        api_json_error('invalid_credentials', 'Valid API credentials are required.', 401);
    }

    $clientKey = extract_bearer_token(request_authorization_header());
    $widgetKey = trim((string) ($_SERVER['HTTP_X_WIDGET_API_KEY'] ?? ''));

    if ($widgetKey === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp((string) $name, 'X-Widget-API-Key') === 0) {
                    $widgetKey = trim((string) $value);
                    break;
                }
            }
        }
    }
    if ($clientKey === '' || $widgetKey === '') {
        api_json_error('invalid_credentials', 'Valid API credentials are required.', 401);
    }

    try {
        $clientHash = hash_api_key($clientKey);
        $widgetHash = hash_api_key($widgetKey);
    } catch (Throwable $exception) {
        api_json_error('invalid_credentials', 'Valid API credentials are required.', 401);
    }

    $clientCredential = find_api_credential_by_hash($clientHash);
    $widgetCredential = find_api_credential_by_hash($widgetHash);

    if (
        !api_credential_is_usable($clientCredential)
        || !api_credential_is_usable($widgetCredential)
        || ($clientCredential['credential_type'] ?? '') !== API_CREDENTIAL_TYPE_CLIENT
        || ($widgetCredential['credential_type'] ?? '') !== API_CREDENTIAL_TYPE_WIDGET
        || ($clientCredential['owner_type'] ?? '') !== API_CREDENTIAL_OWNER_CLIENT
        || ($widgetCredential['owner_type'] ?? '') !== API_CREDENTIAL_OWNER_WIDGET
    ) {
        api_json_error('invalid_credentials', 'Valid API credentials are required.', 401);
    }

    $clientId = (int) $clientCredential['owner_id'];
    $widgetId = (int) $widgetCredential['owner_id'];
    $client = find_client_user($clientId);
    $widget = find_widget_by_id($widgetId);

    if ($client === null || $widget === null) {
        api_json_error('invalid_credentials', 'Valid API credentials are required.', 401);
    }

    if ((int) $widget['user_id'] !== $clientId) {
        log_api_request(
            (int) $clientCredential['id'],
            (int) $widgetCredential['id'],
            $clientId,
            $widgetId,
            '/api/v1/leads/summary',
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            403,
            null
        );
        api_json_error('access_denied', 'The supplied credentials cannot access this resource.', 403);
    }

    return [
        'client' => $client,
        'widget' => $widget,
        'client_credential' => $clientCredential,
        'widget_credential' => $widgetCredential,
    ];
}

function parse_api_summary_period(): array
{
    $period = trim((string) ($_GET['period'] ?? ''));
    $fromDate = trim((string) ($_GET['from_date'] ?? ''));
    $toDate = trim((string) ($_GET['to_date'] ?? ''));

    $hasPeriod = $period !== '';
    $hasRange = $fromDate !== '' || $toDate !== '';

    if ($hasPeriod && $hasRange) {
        api_json_error('invalid_period', 'Use period=today, period=yesterday, or a valid date range.', 400);
    }

    $tz = app_lead_timezone();

    if ($hasPeriod) {
        if ($period === 'today') {
            $bounds = app_lead_today_bounds_utc();
            $startLocal = new DateTimeImmutable('today', $tz);
            $endLocal = $startLocal->modify('+1 day');
        } elseif ($period === 'yesterday') {
            $bounds = app_lead_yesterday_bounds_utc();
            $endLocal = new DateTimeImmutable('today', $tz);
            $startLocal = $endLocal->modify('-1 day');
        } else {
            api_json_error('invalid_period', 'Use period=today, period=yesterday, or a valid date range.', 400);
        }

        return [
            'type' => $period,
            'start_local' => $startLocal,
            'end_local' => $endLocal,
            'start_utc' => $bounds['start'],
            'end_utc' => $bounds['end'],
        ];
    }

    if (!$hasRange) {
        api_json_error('invalid_period', 'Use period=today, period=yesterday, or a valid date range.', 400);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        api_json_error('invalid_period', 'Use period=today, period=yesterday, or a valid date range.', 400);
    }

    $startLocal = DateTimeImmutable::createFromFormat('!Y-m-d', $fromDate, $tz);
    $endDayLocal = DateTimeImmutable::createFromFormat('!Y-m-d', $toDate, $tz);
    if (
        !($startLocal instanceof DateTimeImmutable)
        || !($endDayLocal instanceof DateTimeImmutable)
        || $startLocal->format('Y-m-d') !== $fromDate
        || $endDayLocal->format('Y-m-d') !== $toDate
    ) {
        api_json_error('invalid_period', 'Use period=today, period=yesterday, or a valid date range.', 400);
    }

    if ($endDayLocal < $startLocal) {
        api_json_error('invalid_period', 'Use period=today, period=yesterday, or a valid date range.', 400);
    }

    $daySpan = (int) $startLocal->diff($endDayLocal)->days + 1;
    if ($daySpan > API_SUMMARY_MAX_RANGE_DAYS) {
        api_json_error('invalid_period', 'Use period=today, period=yesterday, or a valid date range.', 400);
    }

    $endLocal = $endDayLocal->modify('+1 day');
    $utc = app_utc_timezone();

    return [
        'type' => 'range',
        'start_local' => $startLocal,
        'end_local' => $endLocal,
        'start_utc' => $startLocal->setTimezone($utc)->format('Y-m-d H:i:s'),
        'end_utc' => $endLocal->setTimezone($utc)->format('Y-m-d H:i:s'),
    ];
}

function count_widget_leads_for_api(int $clientId, int $widgetId, string $startUtc, string $endUtc): int
{
    if (!database_table_exists('widget_leads')) {
        return 0;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM widget_leads
         WHERE client_id = :client_id
           AND widget_id = :widget_id
           AND deleted_at IS NULL
           AND created_at >= :start_utc
           AND created_at < :end_utc'
    );
    $stmt->execute([
        'client_id' => $clientId,
        'widget_id' => $widgetId,
        'start_utc' => $startUtc,
        'end_utc' => $endUtc,
    ]);

    return (int) $stmt->fetchColumn();
}

function format_api_datetime_local(DateTimeImmutable $value): string
{
    return $value->format('Y-m-d\TH:i:sP');
}

function format_api_credential_datetime(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return t('api_key.never');
    }

    try {
        $dt = new DateTimeImmutable($value, app_utc_timezone());

        return $dt->setTimezone(app_lead_timezone())->format('M j, Y');
    } catch (Throwable $exception) {
        return t('api_key.never');
    }
}
