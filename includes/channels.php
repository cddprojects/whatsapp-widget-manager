<?php
declare(strict_types=1);

const WIDGET_CHANNEL_TELEGRAM = 'telegram';

/**
 * @return list<string>
 */
function supported_widget_channels(): array
{
    return [WIDGET_CHANNEL_WHATSAPP, WIDGET_CHANNEL_TELEGRAM];
}

function normalize_widget_channel(string $channel): ?string
{
    $channel = strtolower(trim($channel));
    return in_array($channel, supported_widget_channels(), true) ? $channel : null;
}

function channel_schema_ready(): bool
{
    return database_table_exists('widget_channels') && database_table_exists('channel_destinations');
}

/**
 * @return array{whatsapp: bool, telegram: bool, default: string, order: list<string>, modes: string}
 */
function widget_channel_mode_defaults(): array
{
    return [
        'whatsapp' => true,
        'telegram' => false,
        'default' => WIDGET_CHANNEL_WHATSAPP,
        'order' => [WIDGET_CHANNEL_WHATSAPP, WIDGET_CHANNEL_TELEGRAM],
        'modes' => 'whatsapp_only',
    ];
}

/**
 * @return 'whatsapp_only'|'telegram_only'|'both'
 */
function derive_channel_mode(bool $whatsappEnabled, bool $telegramEnabled): string
{
    if ($whatsappEnabled && $telegramEnabled) {
        return 'both';
    }
    if ($telegramEnabled) {
        return 'telegram_only';
    }

    return 'whatsapp_only';
}

/**
 * @return array{whatsapp: bool, telegram: bool, default: string, order: list<string>, modes: string, rows: list<array>}
 */
function get_widget_channel_config(int $widgetId, ?array $widget = null): array
{
    $defaults = widget_channel_mode_defaults();
    if ($widgetId <= 0 || !channel_schema_ready()) {
        return $defaults + ['rows' => []];
    }

    $stmt = db()->prepare(
        'SELECT * FROM widget_channels WHERE widget_id = :widget_id ORDER BY display_order ASC, id ASC'
    );
    $stmt->execute(['widget_id' => $widgetId]);
    $rows = $stmt->fetchAll() ?: [];

    if ($rows === []) {
        ensure_widget_channel_rows($widgetId, $widget);
        $stmt->execute(['widget_id' => $widgetId]);
        $rows = $stmt->fetchAll() ?: [];
    }

    $whatsappEnabled = false;
    $telegramEnabled = false;
    $default = WIDGET_CHANNEL_WHATSAPP;
    $order = [];

    foreach ($rows as $row) {
        $channel = normalize_widget_channel((string) ($row['channel'] ?? ''));
        if ($channel === null) {
            continue;
        }
        $order[] = $channel;
        if (!empty($row['is_enabled'])) {
            if ($channel === WIDGET_CHANNEL_WHATSAPP) {
                $whatsappEnabled = true;
            }
            if ($channel === WIDGET_CHANNEL_TELEGRAM) {
                $telegramEnabled = true;
            }
        }
        if (!empty($row['is_default'])) {
            $default = $channel;
        }
    }

    if (!$whatsappEnabled && !$telegramEnabled) {
        $whatsappEnabled = true;
        $default = WIDGET_CHANNEL_WHATSAPP;
    }

    if (!$whatsappEnabled && $default === WIDGET_CHANNEL_WHATSAPP) {
        $default = WIDGET_CHANNEL_TELEGRAM;
    }
    if (!$telegramEnabled && $default === WIDGET_CHANNEL_TELEGRAM) {
        $default = WIDGET_CHANNEL_WHATSAPP;
    }

    if ($order === []) {
        $order = $defaults['order'];
    }

    return [
        'whatsapp' => $whatsappEnabled,
        'telegram' => $telegramEnabled,
        'default' => $default,
        'order' => $order,
        'modes' => derive_channel_mode($whatsappEnabled, $telegramEnabled),
        'rows' => $rows,
    ];
}

function ensure_widget_channel_rows(int $widgetId, ?array $widget = null): void
{
    if ($widgetId <= 0 || !channel_schema_ready()) {
        return;
    }

    $widget = $widget ?? find_widget_by_id($widgetId);
    $method = 'round_robin';
    $rrIndex = 0;
    if (is_array($widget)) {
        $method = (string) ($widget['destination_selection_method'] ?? 'round_robin');
        if (!in_array($method, ['single', 'random', 'round_robin'], true)) {
            $method = !empty($widget['use_random_numbers']) ? 'random' : 'single';
        }
        $rrIndex = (int) ($widget['round_robin_next_index'] ?? 0);
    }

    $pdo = db();
    $insert = $pdo->prepare(
        'INSERT INTO widget_channels (
            widget_id, channel, is_enabled, is_default, display_order,
            destination_selection_method, round_robin_next_index, created_at, updated_at
         ) VALUES (
            :widget_id, :channel, :is_enabled, :is_default, :display_order,
            :destination_selection_method, :round_robin_next_index, UTC_TIMESTAMP(), UTC_TIMESTAMP()
         )
         ON DUPLICATE KEY UPDATE updated_at = updated_at'
    );

    $insert->execute([
        'widget_id' => $widgetId,
        'channel' => WIDGET_CHANNEL_WHATSAPP,
        'is_enabled' => 1,
        'is_default' => 1,
        'display_order' => 1,
        'destination_selection_method' => $method,
        'round_robin_next_index' => $rrIndex,
    ]);

    $insert->execute([
        'widget_id' => $widgetId,
        'channel' => WIDGET_CHANNEL_TELEGRAM,
        'is_enabled' => 0,
        'is_default' => 0,
        'display_order' => 2,
        'destination_selection_method' => 'round_robin',
        'round_robin_next_index' => 0,
    ]);
}

/**
 * @param array{whatsapp?: bool, telegram?: bool, default?: string, mode?: string} $config
 * @return array{ok: bool, errors: list<string>}
 */
function save_widget_channel_config(int $widgetId, array $config, ?array $widget = null): array
{
    if ($widgetId <= 0) {
        return ['ok' => false, 'errors' => ['Invalid widget.']];
    }
    if (!channel_schema_ready()) {
        return ['ok' => false, 'errors' => ['Channel schema is not ready. Run migrations.']];
    }

    ensure_widget_channel_rows($widgetId, $widget);

    $mode = (string) ($config['mode'] ?? '');
    if ($mode === 'whatsapp_only') {
        $whatsapp = true;
        $telegram = false;
    } elseif ($mode === 'telegram_only') {
        $whatsapp = false;
        $telegram = true;
    } elseif ($mode === 'both') {
        $whatsapp = true;
        $telegram = true;
    } else {
        $whatsapp = !empty($config['whatsapp']);
        $telegram = !empty($config['telegram']);
    }

    if (!$whatsapp && !$telegram) {
        return ['ok' => false, 'errors' => [t('channel.error.at_least_one')]];
    }

    if ($telegram && count_active_channel_destinations($widgetId, WIDGET_CHANNEL_TELEGRAM) < 1) {
        // Allow saving Telegram as enabled/intent before destinations exist.
        // Public launchers only appear for channels that are ready.
    }

    if ($whatsapp && count(widget_phone_list($widget ?? find_widget_by_id($widgetId) ?? [])) < 1 && $mode === 'whatsapp_only') {
        // Match existing allow-empty-phones setup flow.
    }

    $default = normalize_widget_channel((string) ($config['default'] ?? ''));
    if ($default === null) {
        $default = $whatsapp ? WIDGET_CHANNEL_WHATSAPP : WIDGET_CHANNEL_TELEGRAM;
    }
    if ($default === WIDGET_CHANNEL_WHATSAPP && !$whatsapp) {
        $default = WIDGET_CHANNEL_TELEGRAM;
    }
    if ($default === WIDGET_CHANNEL_TELEGRAM && !$telegram) {
        $default = WIDGET_CHANNEL_WHATSAPP;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'UPDATE widget_channels
         SET is_enabled = :is_enabled,
             is_default = :is_default,
             display_order = :display_order,
             updated_at = UTC_TIMESTAMP()
         WHERE widget_id = :widget_id AND channel = :channel'
    );

    $stmt->execute([
        'is_enabled' => $whatsapp ? 1 : 0,
        'is_default' => $default === WIDGET_CHANNEL_WHATSAPP ? 1 : 0,
        'display_order' => 1,
        'widget_id' => $widgetId,
        'channel' => WIDGET_CHANNEL_WHATSAPP,
    ]);

    $stmt->execute([
        'is_enabled' => $telegram ? 1 : 0,
        'is_default' => $default === WIDGET_CHANNEL_TELEGRAM ? 1 : 0,
        'display_order' => 2,
        'widget_id' => $widgetId,
        'channel' => WIDGET_CHANNEL_TELEGRAM,
    ]);

    return ['ok' => true, 'errors' => []];
}

/**
 * @return list<string>
 */
function enabled_widget_channels(int $widgetId, ?array $widget = null): array
{
    $config = get_widget_channel_config($widgetId, $widget);
    $enabled = [];
    if ($config['whatsapp']) {
        $enabled[] = WIDGET_CHANNEL_WHATSAPP;
    }
    if ($config['telegram']) {
        $enabled[] = WIDGET_CHANNEL_TELEGRAM;
    }

    return $enabled;
}

function widget_channel_is_enabled(int $widgetId, string $channel, ?array $widget = null): bool
{
    $channel = normalize_widget_channel($channel);
    if ($channel === null) {
        return false;
    }

    $config = get_widget_channel_config($widgetId, $widget);
    return !empty($config[$channel]);
}

function get_widget_channel_row(int $widgetId, string $channel): ?array
{
    $channel = normalize_widget_channel($channel);
    if ($channel === null || !channel_schema_ready()) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT * FROM widget_channels WHERE widget_id = :widget_id AND channel = :channel LIMIT 1'
    );
    $stmt->execute([
        'widget_id' => $widgetId,
        'channel' => $channel,
    ]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function update_widget_channel_selection_method(int $widgetId, string $channel, string $method, ?int $roundRobinIndex = null): void
{
    $channel = normalize_widget_channel($channel);
    if ($channel === null || !channel_schema_ready()) {
        return;
    }
    if (!in_array($method, ['single', 'random', 'round_robin'], true)) {
        return;
    }

    ensure_widget_channel_rows($widgetId);

    if ($roundRobinIndex === null) {
        $stmt = db()->prepare(
            'UPDATE widget_channels
             SET destination_selection_method = :method, updated_at = UTC_TIMESTAMP()
             WHERE widget_id = :widget_id AND channel = :channel'
        );
        $stmt->execute([
            'method' => $method,
            'widget_id' => $widgetId,
            'channel' => $channel,
        ]);
        return;
    }

    $stmt = db()->prepare(
        'UPDATE widget_channels
         SET destination_selection_method = :method,
             round_robin_next_index = :round_robin_next_index,
             updated_at = UTC_TIMESTAMP()
         WHERE widget_id = :widget_id AND channel = :channel'
    );
    $stmt->execute([
        'method' => $method,
        'round_robin_next_index' => max(0, $roundRobinIndex),
        'widget_id' => $widgetId,
        'channel' => $channel,
    ]);
}

function widget_owner_is_active(array $widget): bool
{
    $userId = (int) ($widget['user_id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT status FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $status = (string) ($stmt->fetchColumn() ?: '');

    return $status === USER_STATUS_ACTIVE;
}

function normalize_lead_channel(?string $channel): string
{
    $normalized = normalize_widget_channel((string) $channel);
    return $normalized ?? WIDGET_CHANNEL_WHATSAPP;
}
