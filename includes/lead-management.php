<?php
declare(strict_types=1);

const LEAD_BULK_ACTION_MAX_IDS = 200;
const LEAD_RECYCLE_RETENTION_MIN_DAYS = 1;
const LEAD_RECYCLE_RETENTION_MAX_DAYS = 9999;
const LEAD_RECYCLE_DEFAULT_DAYS = 30;
const LEAD_LIST_PER_PAGE_DEFAULT = 30;
const LEAD_LIST_PER_PAGE_OPTIONS = [30, 50, 100, 150];

function lead_list_allowed_per_page_options(): array
{
    return LEAD_LIST_PER_PAGE_OPTIONS;
}

function normalize_lead_list_per_page(int|string|null $value): int
{
    $perPage = (int) $value;

    return in_array($perPage, LEAD_LIST_PER_PAGE_OPTIONS, true)
        ? $perPage
        : LEAD_LIST_PER_PAGE_DEFAULT;
}

function normalize_lead_list_page(int|string|null $page, int $totalPages): int
{
    $normalizedPage = max(1, (int) $page);

    return min($normalizedPage, max(1, $totalPages));
}

function lead_list_visible_range(int $page, int $perPage, int $total): array
{
    if ($total <= 0) {
        return ['from' => 0, 'to' => 0];
    }

    return [
        'from' => (($page - 1) * $perPage) + 1,
        'to' => min($total, $page * $perPage),
    ];
}

function build_lead_list_query_params(array $params): array
{
    $query = [];

    if (trim((string) ($params['q'] ?? '')) !== '') {
        $query['q'] = trim((string) $params['q']);
    }

    if (trim((string) ($params['date_from'] ?? '')) !== '') {
        $query['date_from'] = trim((string) $params['date_from']);
    }

    if (trim((string) ($params['date_to'] ?? '')) !== '') {
        $query['date_to'] = trim((string) $params['date_to']);
    }

    if ((int) ($params['client_id'] ?? 0) > 0) {
        $query['client_id'] = (int) $params['client_id'];
    }

    if ((int) ($params['widget_id'] ?? 0) > 0) {
        $query['widget_id'] = (int) $params['widget_id'];
    }

    $sort = trim((string) ($params['sort'] ?? ''));
    if ($sort !== '') {
        $query['sort'] = $sort;
    }

    $perPage = normalize_lead_list_per_page($params['per_page'] ?? null);
    $query['per_page'] = $perPage;

    $page = max(1, (int) ($params['page'] ?? 1));
    if ($page > 1) {
        $query['page'] = $page;
    }

    return $query;
}

function ensure_lead_recycle_schema(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (!database_table_exists('widget_leads')) {
        return;
    }

    $pdo = db();

    if (!table_has_column('widget_leads', 'client_id')) {
        $pdo->exec(
            'ALTER TABLE widget_leads
             ADD COLUMN client_id INT UNSIGNED NULL AFTER widget_id,
             ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL,
             ADD COLUMN deleted_by_user_id INT UNSIGNED NULL DEFAULT NULL,
             ADD COLUMN deleted_by_role VARCHAR(30) NULL DEFAULT NULL,
             ADD COLUMN restored_at DATETIME NULL DEFAULT NULL,
             ADD COLUMN restored_by_user_id INT UNSIGNED NULL DEFAULT NULL'
        );
        $pdo->exec(
            'UPDATE widget_leads AS wl
             INNER JOIN widgets AS w ON w.id = wl.widget_id
             SET wl.client_id = w.user_id
             WHERE wl.client_id IS NULL'
        );
        $pdo->exec(
            'CREATE INDEX idx_widget_leads_client_active_created
             ON widget_leads (client_id, deleted_at, created_at)'
        );
        $pdo->exec(
            'CREATE INDEX idx_widget_leads_client_widget
             ON widget_leads (client_id, widget_id)'
        );
        $pdo->exec(
            'CREATE INDEX idx_widget_leads_deleted_at
             ON widget_leads (deleted_at)'
        );
    }

    if (!database_table_exists('app_settings')) {
        $pdo->exec(
            'CREATE TABLE app_settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            "INSERT INTO app_settings (setting_key, setting_value) VALUES
             ('lead_recycle_bin_auto_purge_enabled', '1'),
             ('lead_recycle_bin_retention_days', '30')"
        );
    }
}

function app_setting(string $key, ?string $default = null): ?string
{
    if (!database_table_exists('app_settings')) {
        return $default;
    }

    $stmt = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $value = $stmt->fetchColumn();

    return $value === false ? $default : (string) $value;
}

function save_app_setting(string $key, string $value): void
{
    ensure_lead_recycle_schema();
    $stmt = db()->prepare(
        'INSERT INTO app_settings (setting_key, setting_value)
         VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute(['key' => $key, 'value' => $value]);
}

function lead_recycle_auto_purge_enabled(): bool
{
    return app_setting('lead_recycle_bin_auto_purge_enabled', '1') === '1';
}

function lead_recycle_retention_days(): int
{
    $days = (int) app_setting('lead_recycle_bin_retention_days', (string) LEAD_RECYCLE_DEFAULT_DAYS);

    return max(LEAD_RECYCLE_RETENTION_MIN_DAYS, min(LEAD_RECYCLE_RETENTION_MAX_DAYS, $days));
}

function app_lead_timezone(): DateTimeZone
{
    return new DateTimeZone('Asia/Kuala_Lumpur');
}

function app_utc_timezone(): DateTimeZone
{
    return new DateTimeZone('UTC');
}

function app_lead_today_date_local(): string
{
    return (new DateTimeImmutable('today', app_lead_timezone()))->format('Y-m-d');
}

function app_lead_calendar_day_start_local(string $dateYmd): DateTimeImmutable
{
    return new DateTimeImmutable($dateYmd . ' 00:00:00', app_lead_timezone());
}

function app_lead_malaysia_day_bounds_utc(string $dateYmd): array
{
    $startLocal = app_lead_calendar_day_start_local($dateYmd);
    $endLocal = $startLocal->modify('+1 day');

    return [
        'start' => $startLocal->setTimezone(app_utc_timezone())->format('Y-m-d H:i:s'),
        'end' => $endLocal->setTimezone(app_utc_timezone())->format('Y-m-d H:i:s'),
    ];
}

function app_lead_today_bounds_utc(): array
{
    $appTz = app_lead_timezone();
    $todayStartLocal = new DateTimeImmutable('today', $appTz);
    $tomorrowStartLocal = $todayStartLocal->modify('+1 day');
    $utcTz = app_utc_timezone();

    return [
        'start' => $todayStartLocal->setTimezone($utcTz)->format('Y-m-d H:i:s'),
        'end' => $tomorrowStartLocal->setTimezone($utcTz)->format('Y-m-d H:i:s'),
    ];
}

function app_lead_yesterday_bounds_utc(): array
{
    $appTz = app_lead_timezone();
    $todayStartLocal = new DateTimeImmutable('today', $appTz);
    $yesterdayStartLocal = $todayStartLocal->modify('-1 day');
    $utcTz = app_utc_timezone();

    return [
        'start' => $yesterdayStartLocal->setTimezone($utcTz)->format('Y-m-d H:i:s'),
        'end' => $todayStartLocal->setTimezone($utcTz)->format('Y-m-d H:i:s'),
    ];
}

function parse_lead_db_timestamp(?string $value): ?DateTimeImmutable
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $raw = trim($value);
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw, app_utc_timezone());
    if ($parsed instanceof DateTimeImmutable) {
        return $parsed;
    }

    $parsedWithMicro = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $raw, app_utc_timezone());

    return $parsedWithMicro instanceof DateTimeImmutable ? $parsedWithMicro : null;
}

function format_lead_datetime_date_for_display(?string $value): string
{
    $parsed = parse_lead_db_timestamp($value);
    if ($parsed === null) {
        return '';
    }

    return $parsed->setTimezone(app_lead_timezone())->format('M j, Y');
}

function format_lead_datetime_time_for_display(?string $value): string
{
    $parsed = parse_lead_db_timestamp($value);
    if ($parsed === null) {
        return '';
    }

    return $parsed->setTimezone(app_lead_timezone())->format('g:i A');
}

function format_lead_datetime_for_export(?string $value): string
{
    $parsed = parse_lead_db_timestamp($value);
    if ($parsed === null) {
        return trim((string) $value);
    }

    $local = $parsed->setTimezone(app_lead_timezone());

    return $local->format('Y-m-d H:i:s');
}

function render_lead_timezone_note(): void
{
    ?>
    <p class="lead-timezone-note"><?= e(t('lead.times_timezone_note')) ?></p>
    <?php
}

function normalize_lead_ids(array $leadIds): array
{
    $normalized = [];
    foreach ($leadIds as $leadId) {
        $id = (int) $leadId;
        if ($id > 0) {
            $normalized[$id] = $id;
        }
    }

    return array_values($normalized);
}

function find_lead_by_id(int $leadId): ?array
{
    if ($leadId <= 0 || !database_table_exists('widget_leads')) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT wl.*, w.widget_name, u.name AS owner_name, u.email AS owner_email
         FROM widget_leads wl
         INNER JOIN widgets w ON w.id = wl.widget_id
         LEFT JOIN users u ON u.id = wl.client_id
         WHERE wl.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $leadId]);
    $lead = $stmt->fetch();

    return $lead ?: null;
}

function lead_belongs_to_client(array $lead, int $clientId): bool
{
    return (int) ($lead['client_id'] ?? 0) === $clientId;
}

function lead_is_active(array $lead): bool
{
    return empty($lead['deleted_at']);
}

function mask_lead_phone(string $phone): string
{
    $raw = trim($phone);
    if ($raw === '') {
        return '••••';
    }

    $hasPlus = str_starts_with($raw, '+');
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if ($digits === '') {
        return '••••';
    }

    if (strlen($digits) <= 4) {
        return ($hasPlus ? '+' : '') . str_repeat('•', strlen($digits));
    }

    $prefixLength = min(2, strlen($digits) - 4);
    $prefix = substr($digits, 0, $prefixLength);
    $suffix = substr($digits, -4);
    $maskedLength = max(4, strlen($digits) - $prefixLength - 4);

    return ($hasPlus ? '+' : '') . $prefix . str_repeat('•', $maskedLength) . $suffix;
}

function build_client_leads_where(array $options, array &$params): string
{
    $where = [];
    $recycleBin = !empty($options['recycle_bin']);

    if ($recycleBin) {
        $where[] = 'wl.deleted_at IS NOT NULL';
    } else {
        $where[] = 'wl.deleted_at IS NULL';
    }

    $clientId = (int) ($options['client_id'] ?? 0);
    if ($clientId > 0) {
        $where[] = 'wl.client_id = :client_id';
        $params['client_id'] = $clientId;
    }

    $widgetId = (int) ($options['widget_id'] ?? 0);
    if ($widgetId > 0) {
        $where[] = 'wl.widget_id = :widget_id';
        $params['widget_id'] = $widgetId;
    }

    $query = trim((string) ($options['q'] ?? ''));
    if ($query !== '') {
        $where[] = '(wl.visitor_phone LIKE :q OR wl.visitor_full_phone LIKE :q OR wl.source_domain LIKE :q OR wl.source_url LIKE :q OR wl.page_title LIKE :q OR w.widget_name LIKE :q OR u.name LIKE :q OR u.email LIKE :q)';
        $params['q'] = '%' . $query . '%';
    }

    $dateColumn = $recycleBin ? 'wl.deleted_at' : 'wl.created_at';

    $dateFrom = trim((string) ($options['date_from'] ?? ''));
    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $fromBounds = app_lead_malaysia_day_bounds_utc($dateFrom);
        $where[] = $dateColumn . ' >= :date_from_start_utc';
        $params['date_from_start_utc'] = $fromBounds['start'];
    }

    $dateTo = trim((string) ($options['date_to'] ?? ''));
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $toBounds = app_lead_malaysia_day_bounds_utc($dateTo);
        $where[] = $dateColumn . ' < :date_to_end_utc';
        $params['date_to_end_utc'] = $toBounds['end'];
    }

    $deletedByRole = trim((string) ($options['deleted_by_role'] ?? ''));
    if ($recycleBin && in_array($deletedByRole, ['client', 'superadmin'], true)) {
        $where[] = 'wl.deleted_by_role = :deleted_by_role';
        $params['deleted_by_role'] = $deletedByRole;
    }

    return implode(' AND ', $where);
}

function client_leads_order_sql(array $options): string
{
    if (!empty($options['recycle_bin'])) {
        return 'wl.deleted_at DESC';
    }

    $sort = trim((string) ($options['sort'] ?? 'newest'));

    switch ($sort) {
        case 'oldest':
            return 'wl.created_at ASC';
        case 'phone_az':
            return 'COALESCE(NULLIF(wl.visitor_full_phone, \'\'), wl.visitor_phone) ASC, wl.created_at DESC';
        case 'phone_za':
            return 'COALESCE(NULLIF(wl.visitor_full_phone, \'\'), wl.visitor_phone) DESC, wl.created_at DESC';
        case 'client_az':
            return 'u.name ASC, wl.created_at DESC';
        case 'client_za':
            return 'u.name DESC, wl.created_at DESC';
        default:
            return 'wl.created_at DESC';
    }
}

function search_client_leads(array $options): array
{
    $perPage = normalize_lead_list_per_page($options['per_page'] ?? null);
    $params = [];
    $whereSql = build_client_leads_where($options, $params);
    $retentionDays = lead_recycle_retention_days();

    $countStmt = db()->prepare(
        'SELECT COUNT(*)
         FROM widget_leads wl
         INNER JOIN widgets w ON w.id = wl.widget_id
         LEFT JOIN users u ON u.id = wl.client_id
         WHERE ' . $whereSql
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = (int) max(1, (int) ceil($total / $perPage));
    $page = normalize_lead_list_page($options['page'] ?? 1, $pages);
    $offset = ($page - 1) * $perPage;

    $sql = 'SELECT wl.*, w.widget_name, u.name AS owner_name, u.email AS owner_email,
                   deleted_user.name AS deleted_by_name,
                   CASE
                       WHEN wl.deleted_at IS NULL THEN NULL
                       ELSE GREATEST(0, ' . (int) $retentionDays . ' - TIMESTAMPDIFF(DAY, wl.deleted_at, UTC_TIMESTAMP()))
                   END AS days_remaining
            FROM widget_leads wl
            INNER JOIN widgets w ON w.id = wl.widget_id
            LEFT JOIN users u ON u.id = wl.client_id
            LEFT JOIN users deleted_user ON deleted_user.id = wl.deleted_by_user_id
            WHERE ' . $whereSql . '
            ORDER BY ' . client_leads_order_sql($options) . '
            LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return [
        'rows' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => $pages,
    ];
}

function client_leads_for_export(array $options): array
{
    // Export must use the same filters as the table, but never page/per_page LIMIT/OFFSET.
    unset($options['page'], $options['per_page']);

    $params = [];
    $whereSql = build_client_leads_where($options, $params);
    $retentionDays = lead_recycle_retention_days();

    $sql = 'SELECT wl.*, w.widget_name, u.name AS owner_name, u.email AS owner_email,
                   deleted_user.name AS deleted_by_name,
                   CASE
                       WHEN wl.deleted_at IS NULL THEN NULL
                       ELSE GREATEST(0, ' . (int) $retentionDays . ' - TIMESTAMPDIFF(DAY, wl.deleted_at, UTC_TIMESTAMP()))
                   END AS days_remaining
            FROM widget_leads wl
            INNER JOIN widgets w ON w.id = wl.widget_id
            LEFT JOIN users u ON u.id = wl.client_id
            LEFT JOIN users deleted_user ON deleted_user.id = wl.deleted_by_user_id
            WHERE ' . $whereSql . '
            ORDER BY ' . client_leads_order_sql($options);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

function count_active_leads(?int $clientId = null, bool $todayOnly = false): int
{
    if (!database_table_exists('widget_leads')) {
        return 0;
    }

    $where = ['deleted_at IS NULL'];
    $params = [];

    if ($clientId !== null && $clientId > 0) {
        $where[] = 'client_id = :client_id';
        $params['client_id'] = $clientId;
    }

    if ($todayOnly) {
        $bounds = app_lead_today_bounds_utc();
        $where[] = 'created_at >= :today_start_utc';
        $where[] = 'created_at < :today_end_utc';
        $params['today_start_utc'] = $bounds['start'];
        $params['today_end_utc'] = $bounds['end'];
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM widget_leads WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function count_yesterday_active_leads(?int $clientId = null): int
{
    if (!database_table_exists('widget_leads')) {
        return 0;
    }

    $bounds = app_lead_yesterday_bounds_utc();
    $where = [
        'deleted_at IS NULL',
        'created_at >= :yesterday_start_utc',
        'created_at < :today_start_utc',
    ];
    $params = [
        'yesterday_start_utc' => $bounds['start'],
        'today_start_utc' => $bounds['end'],
    ];

    if ($clientId !== null && $clientId > 0) {
        $where[] = 'client_id = :client_id';
        $params['client_id'] = $clientId;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM widget_leads WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function log_lead_management_action(string $action, int $actorId, array $leadIds, ?int $clientId = null): void
{
    error_log(sprintf(
        '[CTC] %s actor_id=%d client_id=%s lead_count=%d lead_ids=%s',
        $action,
        $actorId,
        $clientId === null ? 'null' : (string) $clientId,
        count($leadIds),
        implode(',', $leadIds)
    ));
}

function soft_delete_lead(int $leadId, array $actor): array
{
    $lead = find_lead_by_id($leadId);
    if ($lead === null || !lead_is_active($lead)) {
        return ['success' => false, 'message' => t('lead.delete_not_found'), 'http_status' => 404];
    }

    $role = (string) ($actor['role'] ?? '');
    $actorId = (int) ($actor['id'] ?? 0);

    if ($role === ROLE_CLIENT && !lead_belongs_to_client($lead, $actorId)) {
        return ['success' => false, 'message' => t('error.access_denied'), 'http_status' => 403];
    }

    $stmt = db()->prepare(
        'UPDATE widget_leads
         SET deleted_at = UTC_TIMESTAMP(),
             deleted_by_user_id = :deleted_by_user_id,
             deleted_by_role = :deleted_by_role,
             restored_at = NULL,
             restored_by_user_id = NULL
         WHERE id = :id AND deleted_at IS NULL'
    );
    $stmt->execute([
        'id' => $leadId,
        'deleted_by_user_id' => $actorId,
        'deleted_by_role' => $role === ROLE_SUPERADMIN ? ROLE_SUPERADMIN : ROLE_CLIENT,
    ]);

    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => t('lead.delete_failed'), 'http_status' => 500];
    }

    log_lead_management_action('lead_soft_deleted', $actorId, [$leadId], (int) ($lead['client_id'] ?? 0) ?: null);

    $message = $role === ROLE_SUPERADMIN
        ? t('lead.moved_to_recycle_bin_one')
        : t('lead.deleted_one');

    return [
        'success' => true,
        'deleted' => 1,
        'lead_id' => $leadId,
        'message' => $message,
    ];
}

function bulk_soft_delete_leads(array $leadIds, array $actor): array
{
    $leadIds = normalize_lead_ids($leadIds);
    if ($leadIds === []) {
        return ['success' => false, 'message' => t('lead.delete_none_selected'), 'http_status' => 422, 'deleted' => 0];
    }
    if (count($leadIds) > LEAD_BULK_ACTION_MAX_IDS) {
        return ['success' => false, 'message' => t('lead.delete_too_many'), 'http_status' => 422, 'deleted' => 0];
    }

    $deleted = 0;
    $skipped = 0;
    $deletedIds = [];

    foreach ($leadIds as $leadId) {
        $result = soft_delete_lead($leadId, $actor);
        if (!empty($result['success'])) {
            $deleted++;
            $deletedIds[] = $leadId;
            continue;
        }
        $skipped++;
    }

    if ($deleted === 0) {
        return ['success' => false, 'message' => t('lead.delete_failed'), 'http_status' => 404, 'deleted' => 0, 'skipped' => $skipped];
    }

    log_lead_management_action('leads_soft_deleted', (int) $actor['id'], $deletedIds);

    $role = (string) ($actor['role'] ?? '');
    if ($role === ROLE_SUPERADMIN) {
        $message = $deleted === 1
            ? t('lead.moved_to_recycle_bin_one')
            : t('lead.moved_to_recycle_bin_other', ['count' => (string) $deleted]);
    } else {
        $message = $deleted === 1 ? t('lead.deleted_bulk_one') : t('lead.deleted_other', ['count' => (string) $deleted]);
    }

    return [
        'success' => true,
        'deleted' => $deleted,
        'skipped' => $skipped,
        'deleted_ids' => $deletedIds,
        'message' => $message,
        'partial' => $skipped > 0,
        'partial_message' => $skipped > 0 ? t('lead.delete_partial', ['deleted' => (string) $deleted, 'skipped' => (string) $skipped]) : null,
    ];
}

function restore_lead(int $leadId, int $superadminId): array
{
    $lead = find_lead_by_id($leadId);
    if ($lead === null || lead_is_active($lead)) {
        return ['success' => false, 'message' => t('lead.restore_not_found'), 'http_status' => 404];
    }

    $stmt = db()->prepare(
        'UPDATE widget_leads
         SET deleted_at = NULL,
             deleted_by_user_id = NULL,
             deleted_by_role = NULL,
             restored_at = UTC_TIMESTAMP(),
             restored_by_user_id = :restored_by_user_id
         WHERE id = :id AND deleted_at IS NOT NULL'
    );
    $stmt->execute([
        'id' => $leadId,
        'restored_by_user_id' => $superadminId,
    ]);

    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => t('lead.restore_failed'), 'http_status' => 500];
    }

    log_lead_management_action('lead_restored', $superadminId, [$leadId], (int) ($lead['client_id'] ?? 0) ?: null);

    return ['success' => true, 'lead_id' => $leadId, 'message' => t('lead.restored_one')];
}

function bulk_restore_leads(array $leadIds, int $superadminId): array
{
    $leadIds = normalize_lead_ids($leadIds);
    if ($leadIds === []) {
        return ['success' => false, 'message' => t('lead.restore_none_selected'), 'http_status' => 422];
    }
    if (count($leadIds) > LEAD_BULK_ACTION_MAX_IDS) {
        return ['success' => false, 'message' => t('lead.delete_too_many'), 'http_status' => 422];
    }

    $restored = 0;
    $skipped = 0;
    $restoredIds = [];

    foreach ($leadIds as $leadId) {
        $result = restore_lead($leadId, $superadminId);
        if (!empty($result['success'])) {
            $restored++;
            $restoredIds[] = $leadId;
            continue;
        }
        $skipped++;
    }

    if ($restored === 0) {
        return ['success' => false, 'message' => t('lead.restore_failed'), 'http_status' => 404, 'restored' => 0, 'skipped' => $skipped];
    }

    log_lead_management_action('leads_restored', $superadminId, $restoredIds);

    return [
        'success' => true,
        'restored' => $restored,
        'skipped' => $skipped,
        'restored_ids' => $restoredIds,
        'message' => $restored === 1 ? t('lead.restored_one') : t('lead.restored_other', ['count' => (string) $restored]),
    ];
}

function permanently_delete_lead(int $leadId, int $superadminId): array
{
    $lead = find_lead_by_id($leadId);
    if ($lead === null || lead_is_active($lead)) {
        return ['success' => false, 'message' => t('lead.permanent_delete_not_found'), 'http_status' => 404];
    }

    $stmt = db()->prepare('DELETE FROM widget_leads WHERE id = :id AND deleted_at IS NOT NULL');
    $stmt->execute(['id' => $leadId]);

    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => t('lead.permanent_delete_failed'), 'http_status' => 500];
    }

    log_lead_management_action('lead_permanently_deleted', $superadminId, [$leadId], (int) ($lead['client_id'] ?? 0) ?: null);

    return ['success' => true, 'lead_id' => $leadId, 'message' => t('lead.permanently_deleted_one')];
}

function bulk_permanently_delete_leads(array $leadIds, int $superadminId): array
{
    $leadIds = normalize_lead_ids($leadIds);
    if ($leadIds === []) {
        return ['success' => false, 'message' => t('lead.permanent_delete_none_selected'), 'http_status' => 422];
    }
    if (count($leadIds) > LEAD_BULK_ACTION_MAX_IDS) {
        return ['success' => false, 'message' => t('lead.delete_too_many'), 'http_status' => 422];
    }

    $deleted = 0;
    $skipped = 0;
    $deletedIds = [];

    foreach ($leadIds as $leadId) {
        $result = permanently_delete_lead($leadId, $superadminId);
        if (!empty($result['success'])) {
            $deleted++;
            $deletedIds[] = $leadId;
            continue;
        }
        $skipped++;
    }

    if ($deleted === 0) {
        return ['success' => false, 'message' => t('lead.permanent_delete_failed'), 'http_status' => 404, 'deleted' => 0, 'skipped' => $skipped];
    }

    log_lead_management_action('leads_permanently_deleted', $superadminId, $deletedIds);

    return [
        'success' => true,
        'deleted' => $deleted,
        'skipped' => $skipped,
        'deleted_ids' => $deletedIds,
        'message' => $deleted === 1 ? t('lead.permanently_deleted_one') : t('lead.permanently_deleted_other', ['count' => (string) $deleted]),
    ];
}

function purge_expired_recycled_leads(): array
{
    if (!lead_recycle_auto_purge_enabled()) {
        return ['success' => true, 'deleted' => 0, 'message' => 'Auto-purge disabled'];
    }

    $retentionDays = lead_recycle_retention_days();
    $batchSize = 200;
    $totalDeleted = 0;

    do {
        $stmt = db()->prepare(
            'SELECT id FROM widget_leads
             WHERE deleted_at IS NOT NULL
               AND deleted_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL :retention_days DAY)
             ORDER BY deleted_at ASC
             LIMIT ' . (int) $batchSize
        );
        $stmt->execute(['retention_days' => $retentionDays]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if ($ids === []) {
            break;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $deleteStmt = db()->prepare(
            'DELETE FROM widget_leads WHERE id IN (' . $placeholders . ') AND deleted_at IS NOT NULL'
        );
        $deleteStmt->execute($ids);
        $totalDeleted += $deleteStmt->rowCount();
    } while (count($ids) === $batchSize);

    return ['success' => true, 'deleted' => $totalDeleted];
}

function require_actor_json(): array
{
    $user = current_user();
    if ($user === null) {
        json_response(['success' => false, 'message' => t('error.access_denied')], 401);
    }

    if ((string) $user['status'] === USER_STATUS_DISABLED) {
        json_response(['success' => false, 'message' => t('error.access_denied')], 403);
    }

    return $user;
}

function require_superadmin_json(): array
{
    $user = require_actor_json();
    if (!is_superadmin()) {
        json_response(['success' => false, 'message' => t('error.access_denied')], 403);
    }

    return $user;
}

function require_client_json(): array
{
    $user = require_actor_json();
    if (!is_client()) {
        json_response(['success' => false, 'message' => t('error.access_denied')], 403);
    }

    return $user;
}

function verify_lead_post_csrf(): void
{
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals(csrf_token(), $postedToken)) {
        json_response(['success' => false, 'message' => t('csrf.invalid_token')], 419);
    }
}

function widgets_for_client_filter(?int $clientId): array
{
    if ($clientId === null || $clientId <= 0) {
        return [];
    }

    return widgets_for_user($clientId);
}

function widgets_for_admin_filter(?int $clientId = null): array
{
    if ($clientId !== null && $clientId > 0) {
        return widgets_for_client_filter($clientId);
    }

    $stmt = db()->query(
        'SELECT w.id, w.widget_name, u.name AS owner_name
         FROM widgets w
         INNER JOIN users u ON u.id = w.user_id
         ORDER BY u.name ASC, w.widget_name ASC'
    );

    return $stmt->fetchAll() ?: [];
}

function count_recycled_leads_beyond_retention(int $retentionDays): int
{
    if (!database_table_exists('widget_leads')) {
        return 0;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM widget_leads
         WHERE deleted_at IS NOT NULL
           AND deleted_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL :retention_days DAY)'
    );
    $stmt->execute(['retention_days' => max(1, $retentionDays)]);

    return (int) $stmt->fetchColumn();
}

function translate_deleted_by_role(?string $role): string
{
    return match ((string) $role) {
        ROLE_CLIENT => t('lead.deleted_by_client'),
        ROLE_SUPERADMIN => t('lead.deleted_by_superadmin'),
        default => t('lead.deleted_by_unknown'),
    };
}

function format_recycle_bin_deleted_by_label(array $lead): string
{
    $role = (string) ($lead['deleted_by_role'] ?? '');

    if ($role === ROLE_SUPERADMIN) {
        return t('lead.deleted_by_superadmin_label');
    }

    if ($role === ROLE_CLIENT) {
        $clientName = trim((string) ($lead['owner_name'] ?? ''));
        if ($clientName !== '') {
            return t('lead.deleted_by_client_named', ['name' => $clientName]);
        }

        return t('lead.deleted_by_client_user');
    }

    return t('lead.deleted_by_unknown');
}

function format_recycle_bin_expires_label(int $days): string
{
    if ($days <= 0) {
        return t('lead.expires_today');
    }

    if ($days === 1) {
        return t('lead.expires_in_one');
    }

    return t('lead.expires_in_other', ['count' => (string) $days]);
}

function format_retention_days_label(int $days): string
{
    return $days === 1
        ? t('lead.retention_day_one', ['count' => '1'])
        : t('lead.retention_day_other', ['count' => (string) $days]);
}

function format_lead_export_phone(array $lead): string
{
    $fullPhone = trim((string) ($lead['visitor_full_phone'] ?? ''));
    if ($fullPhone !== '') {
        return $fullPhone;
    }

    return trim((string) ($lead['visitor_phone'] ?? ''));
}

function format_lead_display_phone(array $lead): string
{
    $phone = trim((string) ($lead['visitor_phone'] ?? ''));
    if ($phone !== '') {
        return $phone;
    }

    $full = trim((string) ($lead['visitor_full_phone'] ?? ''));
    if ($full === '') {
        return '';
    }

    return str_starts_with($full, '+') ? $full : '+' . $full;
}

function lead_source_link_url(array $lead): string
{
    $url = trim((string) ($lead['source_url'] ?? ''));
    if ($url !== '' && preg_match('#^https?://#i', $url)) {
        return $url;
    }

    $domain = trim((string) ($lead['source_domain'] ?? ''));
    if ($domain === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $domain)) {
        return $domain;
    }

    return 'https://' . ltrim($domain, '/');
}

function format_lead_source_compact_path(array $lead): string
{
    $url = trim((string) ($lead['source_url'] ?? ''));
    $domain = trim((string) ($lead['source_domain'] ?? ''));

    if ($url !== '') {
        $parts = parse_url($url);
        if (!empty($parts['host'])) {
            $host = preg_replace('/^www\./i', '', (string) $parts['host']);
            $path = (string) ($parts['path'] ?? '');
            $path = $path === '' ? '/' : $path;
            $query = !empty($parts['query']) ? '?' . $parts['query'] : '';

            return $host . $path . $query;
        }

        $compact = preg_replace('#^https?://#i', '', $url);
        $compact = preg_replace('#^www\.#i', '', $compact);

        return rtrim($compact, '/') . (str_contains($compact, '/') ? '' : '/');
    }

    if ($domain !== '') {
        $compact = preg_replace('#^https?://#i', '', $domain);
        $compact = preg_replace('#^www\.#i', '', $compact);

        return rtrim($compact, '/') . '/';
    }

    return '';
}

function format_lead_source_title(array $lead): string
{
    $title = trim((string) ($lead['page_title'] ?? ''));
    if ($title !== '') {
        return $title;
    }

    $compactPath = format_lead_source_compact_path($lead);
    if ($compactPath !== '') {
        return $compactPath;
    }

    return t('lead.source_unknown');
}

function render_lead_source_cell(array $lead): void
{
    $fullUrl = lead_source_link_url($lead);
    $title = format_lead_source_title($lead);
    $compactPath = format_lead_source_compact_path($lead);

    if ($fullUrl === '' && $compactPath === '' && $title === t('lead.source_unknown')) {
        echo '<span class="lead-source-unavailable">' . e(t('lead.source_unavailable')) . '</span>';
        return;
    }

    $tooltip = $fullUrl !== '' ? t('lead.open_source_page') . ': ' . $fullUrl : $compactPath;

    if ($fullUrl !== '') {
        ?>
        <a
            class="lead-source-cell"
            href="<?= e($fullUrl) ?>"
            target="_blank"
            rel="noopener noreferrer"
            title="<?= e($tooltip) ?>"
        >
            <span class="lead-source-title"><?= e($title) ?></span>
            <?php if ($compactPath !== ''): ?>
                <span class="lead-source-url"><?= e($compactPath) ?></span>
            <?php endif; ?>
        </a>
        <?php
        return;
    }

    ?>
    <span class="lead-source-cell is-static" title="<?= e($tooltip) ?>">
        <span class="lead-source-title"><?= e($title) ?></span>
        <?php if ($compactPath !== '' && $compactPath !== $title): ?>
            <span class="lead-source-url"><?= e($compactPath) ?></span>
        <?php endif; ?>
    </span>
    <?php
}

function render_lead_captured_cell(?string $value): void
{
    if ($value === null || trim($value) === '') {
        echo '—';
        return;
    }

    $dateLabel = format_lead_datetime_date_for_display($value);
    $timeLabel = format_lead_datetime_time_for_display($value);
    if ($dateLabel === '' || $timeLabel === '') {
        echo e((string) $value);
        return;
    }
    ?>
    <span class="lead-captured-cell">
        <span class="lead-captured-date"><?= e($dateLabel) ?></span>
        <span class="lead-captured-time"><?= e($timeLabel) ?></span>
    </span>
    <?php
}
