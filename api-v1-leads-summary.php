<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api-credentials.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'GET' && $method !== 'HEAD') {
    header('Allow: GET, HEAD');
    api_json_error('method_not_allowed', 'Method Not Allowed', 405);
}

try {
    ensure_api_credentials_schema();
    $auth = authenticate_api_key_pair();
    $client = $auth['client'];
    $widget = $auth['widget'];
    $clientCredential = $auth['client_credential'];
    $widgetCredential = $auth['widget_credential'];

    enforce_api_rate_limit((int) $clientCredential['id'], (int) $widgetCredential['id']);

    $period = parse_api_summary_period();
    $leadCount = count_widget_leads_for_api(
        (int) $client['id'],
        (int) $widget['id'],
        $period['start_utc'],
        $period['end_utc']
    );

    touch_api_credential_last_used((int) $clientCredential['id']);
    touch_api_credential_last_used((int) $widgetCredential['id']);

    log_api_request(
        (int) $clientCredential['id'],
        (int) $widgetCredential['id'],
        (int) $client['id'],
        (int) $widget['id'],
        '/api/v1/leads/summary',
        $method,
        200,
        (string) $period['type']
    );

    api_json_success([
        'success' => true,
        'timezone' => 'Asia/Kuala_Lumpur',
        'client' => [
            'name' => (string) $client['name'],
        ],
        'widget' => [
            'name' => (string) ($widget['widget_name'] ?? ''),
        ],
        'period' => [
            'type' => (string) $period['type'],
            'start' => format_api_datetime_local($period['start_local']),
            'end_exclusive' => format_api_datetime_local($period['end_local']),
        ],
        'lead_count' => $leadCount,
    ]);
} catch (Throwable $exception) {
    log_api_request(
        null,
        null,
        null,
        null,
        '/api/v1/leads/summary',
        $method,
        500,
        null
    );
    api_json_error('server_error', 'Unable to process this request.', 500);
}
