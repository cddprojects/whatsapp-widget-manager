<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api-credentials.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');

if (!is_post()) {
    json_response(['success' => false, 'message' => t('error.access_denied')], 405);
}

$user = current_user();
if ($user === null || !is_superadmin_user($user)) {
    json_response(['success' => false, 'message' => t('error.access_denied')], 403);
}

verify_lead_post_csrf();
ensure_api_credentials_schema();

$action = trim((string) ($_POST['action'] ?? ''));
$ownerType = trim((string) ($_POST['owner_type'] ?? ''));
$ownerId = (int) ($_POST['owner_id'] ?? 0);

$allowedActions = [
    'generate',
    'reveal',
    'regenerate',
    'enable',
    'disable',
];

if (!in_array($action, $allowedActions, true) || $ownerId <= 0) {
    json_response(['success' => false, 'message' => t('api_key.invalid_request')], 422);
}

if ($ownerType === API_CREDENTIAL_OWNER_CLIENT) {
    $client = find_client_user($ownerId);
    if ($client === null) {
        json_response(['success' => false, 'message' => t('error.client_not_found')], 404);
    }
    $credentialType = API_CREDENTIAL_TYPE_CLIENT;
    $displayName = (string) $client['name'];
    $context = [
        'client_name' => $displayName,
        'widget_name' => null,
    ];
} elseif ($ownerType === API_CREDENTIAL_OWNER_WIDGET) {
    $widget = find_widget_by_id($ownerId);
    if ($widget === null) {
        json_response(['success' => false, 'message' => t('error.widget_not_found')], 404);
    }
    $client = find_client_user((int) $widget['user_id']);
    if ($client === null) {
        json_response(['success' => false, 'message' => t('error.client_not_found')], 404);
    }
    $credentialType = API_CREDENTIAL_TYPE_WIDGET;
    $displayName = (string) ($widget['widget_name'] ?? '');
    $context = [
        'client_name' => (string) $client['name'],
        'widget_name' => $displayName,
    ];
} else {
    json_response(['success' => false, 'message' => t('api_key.invalid_request')], 422);
}

try {
    if ($action === 'generate' || $action === 'regenerate') {
        $result = create_or_replace_api_credential(
            $ownerType,
            $ownerId,
            $credentialType,
            (int) $user['id']
        );
        $view = $result['view'];
        json_response([
            'success' => true,
            'message' => $action === 'generate' ? t('api_key.generated') : t('api_key.regenerated'),
            'raw_key' => $result['raw_key'],
            'credential' => $view,
            'context' => $context,
            'created_label' => format_api_credential_datetime($view['created_at'] ?? null),
            'last_used_label' => format_api_credential_datetime($view['last_used_at'] ?? null),
            'status_label' => $view['is_active'] ? t('api_key.status_active') : t('api_key.status_disabled'),
        ]);
    }

    if ($action === 'reveal') {
        $rawKey = reveal_api_credential_key($ownerType, $ownerId, $credentialType);
        $credential = find_api_credential($ownerType, $ownerId, $credentialType);
        $view = api_credential_public_view($credential);
        json_response([
            'success' => true,
            'raw_key' => $rawKey,
            'credential' => $view,
            'context' => $context,
        ]);
    }

    if ($action === 'enable' || $action === 'disable') {
        $credential = set_api_credential_active(
            $ownerType,
            $ownerId,
            $credentialType,
            $action === 'enable'
        );
        if ($credential === null) {
            json_response(['success' => false, 'message' => t('api_key.not_found')], 404);
        }
        $view = api_credential_public_view($credential);
        json_response([
            'success' => true,
            'message' => $action === 'enable' ? t('api_key.enabled') : t('api_key.disabled'),
            'credential' => $view,
            'context' => $context,
            'created_label' => format_api_credential_datetime($view['created_at'] ?? null),
            'last_used_label' => format_api_credential_datetime($view['last_used_at'] ?? null),
            'status_label' => $view['is_active'] ? t('api_key.status_active') : t('api_key.status_disabled'),
        ]);
    }

    json_response(['success' => false, 'message' => t('api_key.invalid_request')], 422);
} catch (Throwable $exception) {
    json_response(['success' => false, 'message' => t('api_key.operation_failed')], 500);
}
