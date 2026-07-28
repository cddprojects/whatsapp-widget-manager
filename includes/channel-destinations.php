<?php
declare(strict_types=1);

function count_active_channel_destinations(int $widgetId, string $channel): int
{
    $channel = normalize_widget_channel($channel);
    if ($channel === null || $widgetId <= 0) {
        return 0;
    }

    if ($channel === WIDGET_CHANNEL_WHATSAPP) {
        return count(widget_phone_list(find_widget_by_id($widgetId) ?? []));
    }

    if (!channel_schema_ready()) {
        return 0;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM channel_destinations
         WHERE widget_id = :widget_id
           AND channel = :channel
           AND is_active = 1
           AND deleted_at IS NULL'
    );
    $stmt->execute([
        'widget_id' => $widgetId,
        'channel' => $channel,
    ]);

    return (int) $stmt->fetchColumn();
}

/**
 * @return list<array>
 */
function list_channel_destinations(int $widgetId, string $channel, bool $includeInactive = true, bool $includeDeleted = false): array
{
    $channel = normalize_widget_channel($channel);
    if ($channel === null || $widgetId <= 0 || !channel_schema_ready()) {
        return [];
    }

    $sql = 'SELECT * FROM channel_destinations
            WHERE widget_id = :widget_id AND channel = :channel';
    if (!$includeDeleted) {
        $sql .= ' AND deleted_at IS NULL';
    }
    if (!$includeInactive) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute([
        'widget_id' => $widgetId,
        'channel' => $channel,
    ]);

    return $stmt->fetchAll() ?: [];
}

function find_channel_destination(int $destinationId, ?int $widgetId = null): ?array
{
    if ($destinationId <= 0 || !channel_schema_ready()) {
        return null;
    }

    $sql = 'SELECT * FROM channel_destinations WHERE id = :id';
    $params = ['id' => $destinationId];
    if ($widgetId !== null) {
        $sql .= ' AND widget_id = :widget_id';
        $params['widget_id'] = $widgetId;
    }
    $sql .= ' LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row ?: null;
}

function telegram_destination_duplicate_exists(
    int $widgetId,
    string $destinationType,
    string $destinationValue,
    ?int $excludeId = null
): bool {
    if (!channel_schema_ready()) {
        return false;
    }

    $sql = 'SELECT id FROM channel_destinations
            WHERE widget_id = :widget_id
              AND channel = :channel
              AND destination_type = :destination_type
              AND destination_value = :destination_value
              AND deleted_at IS NULL';
    $params = [
        'widget_id' => $widgetId,
        'channel' => WIDGET_CHANNEL_TELEGRAM,
        'destination_type' => $destinationType,
        'destination_value' => $destinationValue,
    ];
    if ($excludeId !== null) {
        $sql .= ' AND id <> :exclude_id';
        $params['exclude_id'] = $excludeId;
    }
    $sql .= ' LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

/**
 * @return array{ok: bool, id?: int, errors: list<string>, field_errors?: array<string, string>}
 */
function save_telegram_destination(int $widgetId, array $input, ?int $destinationId = null): array
{
    if ($widgetId <= 0 || !channel_schema_ready()) {
        return ['ok' => false, 'errors' => ['Invalid widget.']];
    }

    $validated = validate_telegram_destination_input($input);
    if (!$validated['ok']) {
        $field = (string) ($validated['field'] ?? 'destination_value');
        return [
            'ok' => false,
            'errors' => [(string) $validated['error']],
            'field_errors' => [$field => (string) $validated['error']],
        ];
    }

    $displayName = trim((string) ($input['display_name'] ?? ''));
    if ($displayName === '') {
        $displayName = format_telegram_destination_display([
            'destination_type' => $validated['destination_type'],
            'destination_value' => $validated['destination_value'],
        ]);
    }
    if (mb_strlen($displayName) > 120) {
        return [
            'ok' => false,
            'errors' => [t('telegram.error.display_name_length')],
            'field_errors' => ['display_name' => t('telegram.error.display_name_length')],
        ];
    }

    if (telegram_destination_duplicate_exists(
        $widgetId,
        $validated['destination_type'],
        $validated['destination_value'],
        $destinationId
    )) {
        return [
            'ok' => false,
            'errors' => [t('telegram.error.duplicate')],
            'field_errors' => ['destination_value' => t('telegram.error.duplicate')],
        ];
    }

    $isActive = !empty($input['is_active']) ? 1 : 0;
    $sortOrder = isset($input['sort_order']) ? max(0, (int) $input['sort_order']) : 0;

    if ($destinationId !== null) {
        $existing = find_channel_destination($destinationId, $widgetId);
        if ($existing === null || ($existing['channel'] ?? '') !== WIDGET_CHANNEL_TELEGRAM || !empty($existing['deleted_at'])) {
            return ['ok' => false, 'errors' => [t('telegram.error.not_found')]];
        }

        $stmt = db()->prepare(
            'UPDATE channel_destinations
             SET destination_type = :destination_type,
                 destination_value = :destination_value,
                 display_name = :display_name,
                 bot_start_parameter = :bot_start_parameter,
                 is_active = :is_active,
                 sort_order = :sort_order,
                 updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND widget_id = :widget_id'
        );
        $stmt->execute([
            'destination_type' => $validated['destination_type'],
            'destination_value' => $validated['destination_value'],
            'display_name' => $displayName,
            'bot_start_parameter' => $validated['bot_start_parameter'],
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
            'id' => $destinationId,
            'widget_id' => $widgetId,
        ]);

        refresh_widget_destination_status($widgetId);

        return ['ok' => true, 'id' => $destinationId, 'errors' => []];
    }

    $maxSort = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) FROM channel_destinations
         WHERE widget_id = :widget_id AND channel = :channel AND deleted_at IS NULL'
    );
    $maxSort->execute([
        'widget_id' => $widgetId,
        'channel' => WIDGET_CHANNEL_TELEGRAM,
    ]);
    if (!isset($input['sort_order'])) {
        $sortOrder = ((int) $maxSort->fetchColumn()) + 1;
    }

    $stmt = db()->prepare(
        'INSERT INTO channel_destinations (
            widget_id, channel, destination_type, destination_value, display_name,
            bot_start_parameter, is_active, distribution_weight, sort_order,
            deleted_at, created_at, updated_at
         ) VALUES (
            :widget_id, :channel, :destination_type, :destination_value, :display_name,
            :bot_start_parameter, :is_active, 1, :sort_order,
            NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
         )'
    );
    $stmt->execute([
        'widget_id' => $widgetId,
        'channel' => WIDGET_CHANNEL_TELEGRAM,
        'destination_type' => $validated['destination_type'],
        'destination_value' => $validated['destination_value'],
        'display_name' => $displayName,
        'bot_start_parameter' => $validated['bot_start_parameter'],
        'is_active' => $isActive,
        'sort_order' => $sortOrder,
    ]);

    $id = (int) db()->lastInsertId();
    refresh_widget_destination_status($widgetId);

    return ['ok' => true, 'id' => $id, 'errors' => []];
}

function soft_delete_channel_destination(int $widgetId, int $destinationId): bool
{
    $existing = find_channel_destination($destinationId, $widgetId);
    if ($existing === null || !empty($existing['deleted_at'])) {
        return false;
    }

    $stmt = db()->prepare(
        'UPDATE channel_destinations
         SET deleted_at = UTC_TIMESTAMP(), is_active = 0, updated_at = UTC_TIMESTAMP()
         WHERE id = :id AND widget_id = :widget_id'
    );
    $stmt->execute([
        'id' => $destinationId,
        'widget_id' => $widgetId,
    ]);

    // If Telegram was enabled and no active destinations remain, disable Telegram only.
    if (($existing['channel'] ?? '') === WIDGET_CHANNEL_TELEGRAM
        && count_active_channel_destinations($widgetId, WIDGET_CHANNEL_TELEGRAM) < 1
    ) {
        db()->prepare(
            'UPDATE widget_channels
             SET is_enabled = 0, is_default = 0, updated_at = UTC_TIMESTAMP()
             WHERE widget_id = :widget_id AND channel = :channel'
        )->execute([
            'widget_id' => $widgetId,
            'channel' => WIDGET_CHANNEL_TELEGRAM,
        ]);

        // Prefer WhatsApp as default when still present.
        db()->prepare(
            'UPDATE widget_channels
             SET is_default = 1, updated_at = UTC_TIMESTAMP()
             WHERE widget_id = :widget_id AND channel = :channel AND is_enabled = 1'
        )->execute([
            'widget_id' => $widgetId,
            'channel' => WIDGET_CHANNEL_WHATSAPP,
        ]);
    }

    refresh_widget_destination_status($widgetId);

    return true;
}

function set_channel_destination_active(int $widgetId, int $destinationId, bool $active): bool
{
    $existing = find_channel_destination($destinationId, $widgetId);
    if ($existing === null || !empty($existing['deleted_at'])) {
        return false;
    }

    $stmt = db()->prepare(
        'UPDATE channel_destinations
         SET is_active = :is_active, updated_at = UTC_TIMESTAMP()
         WHERE id = :id AND widget_id = :widget_id AND deleted_at IS NULL'
    );
    $stmt->execute([
        'is_active' => $active ? 1 : 0,
        'id' => $destinationId,
        'widget_id' => $widgetId,
    ]);

    refresh_widget_destination_status($widgetId);

    return true;
}

/**
 * Dual-write helper: mirror WhatsApp phone list into channel_destinations.
 */
function sync_whatsapp_destinations_from_legacy(int $widgetId, ?array $widget = null): void
{
    if ($widgetId <= 0 || !channel_schema_ready()) {
        return;
    }

    $widget = $widget ?? find_widget_by_id($widgetId);
    if ($widget === null) {
        return;
    }

    ensure_widget_channel_rows($widgetId, $widget);
    $phones = widget_phone_list($widget);
    $pdo = db();

    // Soft-delete existing active WhatsApp destinations, then recreate from current list.
    $pdo->prepare(
        'UPDATE channel_destinations
         SET deleted_at = UTC_TIMESTAMP(), is_active = 0, updated_at = UTC_TIMESTAMP()
         WHERE widget_id = :widget_id AND channel = :channel AND deleted_at IS NULL'
    )->execute([
        'widget_id' => $widgetId,
        'channel' => WIDGET_CHANNEL_WHATSAPP,
    ]);

    $insert = $pdo->prepare(
        'INSERT INTO channel_destinations (
            widget_id, channel, destination_type, destination_value, display_name,
            bot_start_parameter, is_active, distribution_weight, sort_order,
            deleted_at, created_at, updated_at
         ) VALUES (
            :widget_id, :channel, :destination_type, :destination_value, :display_name,
            NULL, 1, 1, :sort_order, NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
         )'
    );

    foreach (array_values($phones) as $index => $phone) {
        $full = clean_phone_number((string) ($phone['full_number'] ?? ''));
        if ($full === '') {
            $full = clean_phone_number((string) ($phone['country_code'] ?? ''))
                . clean_phone_number((string) ($phone['number'] ?? ''));
        }
        if ($full === '') {
            continue;
        }

        $display = trim((string) ($phone['country_code'] ?? '') . ' ' . (string) ($phone['number'] ?? ''));
        $insert->execute([
            'widget_id' => $widgetId,
            'channel' => WIDGET_CHANNEL_WHATSAPP,
            'destination_type' => 'phone',
            'destination_value' => $full,
            'display_name' => $display !== '' ? $display : $full,
            'sort_order' => $index,
        ]);
    }

    $method = effective_destination_selection_method($widget, count($phones));
    update_widget_channel_selection_method(
        $widgetId,
        WIDGET_CHANNEL_WHATSAPP,
        $method,
        (int) ($widget['round_robin_next_index'] ?? 0)
    );
}

/**
 * @return list<array>
 */
function active_telegram_destinations(int $widgetId): array
{
    return list_channel_destinations($widgetId, WIDGET_CHANNEL_TELEGRAM, false, false);
}

/**
 * Resolve a Telegram destination for a widget using channel-specific distribution.
 *
 * @return array{success: bool, message?: string, channel?: string, destination?: array, redirect_url?: string, fallback?: array, selection_method?: string}
 */
function resolve_telegram_destination(int $widgetId, string $publicKey, ?string $referrer = null): array
{
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

        if (empty($widget['show_global']) || !widget_is_publicly_active($widget)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Widget not available'];
        }

        if (!is_widget_online($widget)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Widget offline'];
        }

        if (!widget_channel_is_enabled($widgetId, WIDGET_CHANNEL_TELEGRAM, $widget)) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Telegram is currently unavailable',
                'error' => 'channel_disabled',
            ];
        }

        $channelStmt = $pdo->prepare(
            'SELECT * FROM widget_channels
             WHERE widget_id = :widget_id AND channel = :channel
             LIMIT 1
             FOR UPDATE'
        );
        $channelStmt->execute([
            'widget_id' => $widgetId,
            'channel' => WIDGET_CHANNEL_TELEGRAM,
        ]);
        $channelRow = $channelStmt->fetch();
        if (!$channelRow || empty($channelRow['is_enabled'])) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Telegram is currently unavailable',
                'error' => 'channel_disabled',
            ];
        }

        $destStmt = $pdo->prepare(
            'SELECT * FROM channel_destinations
             WHERE widget_id = :widget_id
               AND channel = :channel
               AND is_active = 1
               AND deleted_at IS NULL
             ORDER BY sort_order ASC, id ASC
             FOR UPDATE'
        );
        $destStmt->execute([
            'widget_id' => $widgetId,
            'channel' => WIDGET_CHANNEL_TELEGRAM,
        ]);
        $destinations = $destStmt->fetchAll() ?: [];
        if ($destinations === []) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'No active Telegram destination is configured',
                'error' => 'no_active_destination',
            ];
        }

        $count = count($destinations);
        $method = (string) ($channelRow['destination_selection_method'] ?? 'round_robin');
        if ($count === 1) {
            $method = 'single';
        } elseif (!in_array($method, ['random', 'round_robin'], true)) {
            $method = 'round_robin';
        }

        if ($method === 'single' || $count === 1) {
            $selected = $destinations[0];
        } elseif ($method === 'round_robin') {
            $currentIndex = (int) ($channelRow['round_robin_next_index'] ?? 0);
            $safeIndex = $count > 0 ? ($currentIndex % $count) : 0;
            $selected = $destinations[$safeIndex];
            $nextIndex = ($safeIndex + 1) % $count;
            $update = $pdo->prepare(
                'UPDATE widget_channels
                 SET round_robin_next_index = :idx, updated_at = UTC_TIMESTAMP()
                 WHERE id = :id'
            );
            $update->execute([
                'idx' => $nextIndex,
                'id' => (int) $channelRow['id'],
            ]);
        } else {
            $selected = $destinations[array_rand($destinations)];
        }

        $built = build_telegram_redirect_url($selected);
        if (!$built['ok']) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Invalid Telegram destination'];
        }

        $pdo->commit();

        $response = [
            'success' => true,
            'channel' => WIDGET_CHANNEL_TELEGRAM,
            'destination' => [
                'id' => (int) $selected['id'],
                'destination_type' => (string) $selected['destination_type'],
                'destination_value' => (string) $selected['destination_value'],
                'display_name' => (string) $selected['display_name'],
                'bot_start_parameter' => $selected['bot_start_parameter'],
            ],
            'redirect_url' => $built['url'],
            'selection_method' => $method,
        ];
        if (!empty($built['fallback'])) {
            $response['fallback'] = $built['fallback'];
        }

        return $response;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['success' => false, 'message' => 'Unable to resolve destination'];
    }
}
