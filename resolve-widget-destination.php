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

if ($widgetId <= 0 || $publicKey === '') {
    json_response(['success' => false, 'message' => 'Invalid widget'], 400);
}

$result = resolve_widget_destination($widgetId, $publicKey, $referrer !== '' ? $referrer : null);
if (empty($result['success'])) {
    json_response([
        'success' => false,
        'message' => (string) ($result['message'] ?? 'Unable to resolve destination'),
    ], 422);
}

json_response([
    'success' => true,
    'country_code' => (string) ($result['country_code'] ?? ''),
    'number' => (string) ($result['number'] ?? ''),
    'full_number' => (string) ($result['full_number'] ?? ''),
    'selection_method' => (string) ($result['selection_method'] ?? ''),
]);
