<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    json_response(['success' => false, 'message' => 'Invalid request'], 400);
}

$widgetId = (int) ($payload['widget_id'] ?? 0);
$publicKey = trim((string) ($payload['public_key'] ?? ''));
$referrer = trim((string) ($payload['source_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '')));
$channelRaw = trim((string) ($payload['channel'] ?? WIDGET_CHANNEL_WHATSAPP));
$channel = normalize_widget_channel($channelRaw);
$leadId = (int) ($payload['lead_id'] ?? 0);

if ($widgetId <= 0 || $publicKey === '') {
    json_response(['success' => false, 'message' => 'Invalid widget'], 400);
}

if ($channel === null) {
    json_response(['success' => false, 'message' => 'Invalid channel'], 400);
}

$result = resolve_widget_destination(
    $widgetId,
    $publicKey,
    $referrer !== '' ? $referrer : null,
    $channel
);

if (empty($result['success'])) {
    json_response([
        'success' => false,
        'message' => (string) ($result['message'] ?? 'Unable to resolve destination'),
        'error' => (string) ($result['error'] ?? 'unable_to_continue'),
        'channel' => $channel,
    ], 422);
}

if ($leadId > 0) {
    $update = [
        'channel' => $channel,
        'channel_selected_at' => 'now',
        'destination_resolved_at' => 'now',
        'redirect_attempted_at' => 'now',
    ];

    if ($channel === WIDGET_CHANNEL_TELEGRAM) {
        $destination = is_array($result['destination'] ?? null) ? $result['destination'] : [];
        $update['channel_destination_id'] = (int) ($destination['id'] ?? 0) ?: null;
        $update['destination_type'] = (string) ($destination['destination_type'] ?? '');
        $update['destination_name'] = (string) ($destination['display_name'] ?? '');
        $update['destination_snapshot'] = (string) ($destination['destination_value'] ?? '');
        if (!empty($result['fallback']['type'])) {
            $update['fallback_type'] = (string) $result['fallback']['type'];
        }
        $update['whatsapp_redirect_url'] = (string) ($result['redirect_url'] ?? '');
    } else {
        $update['destination_type'] = 'phone';
        $update['destination_name'] = trim(
            (string) ($result['country_code'] ?? '') . ' ' . (string) ($result['number'] ?? '')
        );
        $update['destination_snapshot'] = (string) ($result['full_number'] ?? '');
    }

    update_widget_lead_channel_events($leadId, $widgetId, $update);
}

$response = [
    'success' => true,
    'channel' => $channel,
    'selection_method' => (string) ($result['selection_method'] ?? ''),
];

if ($channel === WIDGET_CHANNEL_WHATSAPP) {
    $response['country_code'] = (string) ($result['country_code'] ?? '');
    $response['number'] = (string) ($result['number'] ?? '');
    $response['full_number'] = (string) ($result['full_number'] ?? '');
} else {
    $response['redirect_url'] = (string) ($result['redirect_url'] ?? '');
    $response['destination_type'] = (string) ($result['destination']['destination_type'] ?? '');
    $response['destination_name'] = (string) ($result['destination']['display_name'] ?? '');
    if (!empty($result['fallback'])) {
        $response['fallback'] = $result['fallback'];
    }
}

json_response($response);
