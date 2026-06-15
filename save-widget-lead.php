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

if (!empty($payload['website'])) {
    json_response(['success' => true, 'message' => 'Lead saved']);
}

$widgetId = (int) ($payload['widget_id'] ?? 0);
$publicKey = trim((string) ($payload['public_key'] ?? ''));
$visitorPhone = trim((string) ($payload['visitor_phone'] ?? ''));

if ($widgetId <= 0 || $publicKey === '') {
    json_response(['success' => false, 'message' => 'Invalid widget'], 400);
}

$widget = find_public_widget($widgetId, $publicKey);
if (!$widget) {
    json_response(['success' => false, 'message' => 'Widget not found'], 404);
}

$referrer = (string) ($payload['source_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
if (!domain_matches_referrer($widget, $referrer !== '' ? $referrer : null)) {
    json_response(['success' => false, 'message' => 'Domain not allowed'], 403);
}

$normalized = normalize_visitor_phone($visitorPhone);
if ($normalized === null) {
    json_response(['success' => false, 'message' => 'Invalid phone number'], 422);
}

if (lead_recently_saved($widgetId, $normalized['visitor_full_phone'])) {
    json_response(['success' => true, 'message' => 'Lead saved']);
}

$sourceDomain = referrer_host($referrer);
$pageTitle = trim(strip_tags((string) ($payload['page_title'] ?? '')));
$redirectUrl = trim(strip_tags((string) ($payload['whatsapp_redirect_url'] ?? '')));

$storeIp = getenv('LEAD_STORE_IP') === '1';
$storeAgent = getenv('LEAD_STORE_USER_AGENT') === '1';

try {
    insert_widget_lead($widget, [
        'visitor_phone' => $normalized['visitor_phone'],
        'visitor_country_code' => $normalized['visitor_country_code'],
        'visitor_full_phone' => $normalized['visitor_full_phone'],
        'source_domain' => $sourceDomain,
        'source_url' => $referrer,
        'page_title' => mb_substr($pageTitle, 0, 255),
        'whatsapp_redirect_url' => $redirectUrl,
        'ip_address' => $storeIp ? substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 100) : null,
        'user_agent' => $storeAgent ? substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 2000) : null,
    ]);
} catch (Throwable $exception) {
    json_response(['success' => false, 'message' => 'Unable to save lead'], 500);
}

json_response(['success' => true, 'message' => 'Lead saved']);
