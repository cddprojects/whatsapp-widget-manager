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
$leadId = (int) ($payload['lead_id'] ?? 0);
$channelRaw = trim((string) ($payload['channel'] ?? ''));
$channel = $channelRaw !== '' ? normalize_widget_channel($channelRaw) : null;

if ($widgetId <= 0 || $publicKey === '') {
    json_response(['success' => false, 'message' => 'Invalid widget'], 400);
}

if ($channelRaw !== '' && $channel === null) {
    json_response(['success' => false, 'message' => 'Invalid channel'], 400);
}

$widget = find_public_widget($widgetId, $publicKey);
if (!$widget) {
    json_response(['success' => false, 'message' => 'Widget not found'], 404);
}

if (!widget_owner_is_active($widget)) {
    json_response(['success' => false, 'message' => 'Widget not available'], 403);
}

$referrer = (string) ($payload['source_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
if (!domain_matches_referrer($widget, $referrer !== '' ? $referrer : null)) {
    json_response(['success' => false, 'message' => 'Domain not allowed'], 403);
}

// Channel-event update for an existing lead (after channel selection / redirect attempt).
if ($leadId > 0 && (
    $channel !== null
    || array_key_exists('fallback_type', $payload)
    || array_key_exists('redirect_attempted', $payload)
    || array_key_exists('destination_snapshot', $payload)
)) {
    $fields = [];
    if ($channel !== null) {
        $fields['channel'] = $channel;
        $fields['channel_selected_at'] = 'now';
    }
    if (!empty($payload['redirect_attempted'])) {
        $fields['redirect_attempted_at'] = 'now';
    }
    if (isset($payload['fallback_type'])) {
        $fields['fallback_type'] = trim(strip_tags((string) $payload['fallback_type']));
    }
    if (isset($payload['destination_type'])) {
        $fields['destination_type'] = trim(strip_tags((string) $payload['destination_type']));
    }
    if (isset($payload['destination_name'])) {
        $fields['destination_name'] = mb_substr(trim(strip_tags((string) $payload['destination_name'])), 0, 120);
    }
    if (isset($payload['destination_snapshot'])) {
        $fields['destination_snapshot'] = mb_substr(trim(strip_tags((string) $payload['destination_snapshot'])), 0, 512);
    }
    if (isset($payload['whatsapp_redirect_url'])) {
        $fields['whatsapp_redirect_url'] = trim(strip_tags((string) $payload['whatsapp_redirect_url']));
    }
    if (isset($payload['channel_destination_id'])) {
        $fields['channel_destination_id'] = (int) $payload['channel_destination_id'] ?: null;
    }
    if (!empty($payload['destination_resolved'])) {
        $fields['destination_resolved_at'] = 'now';
    }

    update_widget_lead_channel_events($leadId, $widgetId, $fields);
    json_response(['success' => true, 'message' => 'Lead updated', 'lead_id' => $leadId]);
}

$normalized = validate_captured_visitor_phone($visitorPhone, widget_allows_phone_plus($widget));
if (empty($normalized['valid'])) {
    json_response(['success' => false, 'message' => $normalized['message'] ?? t('widget.phone_validation.invalid')], 422);
}

$normalized = $normalized['normalized'];

if (lead_recently_saved($widgetId, $normalized['visitor_full_phone'])) {
    $existingId = find_recent_widget_lead_id($widgetId, $normalized['visitor_full_phone']);
    json_response([
        'success' => true,
        'message' => 'Lead saved',
        'lead_id' => $existingId,
    ]);
}

$sourceDomain = referrer_host($referrer);
$pageTitle = trim(strip_tags((string) ($payload['page_title'] ?? '')));
$redirectUrl = trim(strip_tags((string) ($payload['whatsapp_redirect_url'] ?? '')));

$storeIp = getenv('LEAD_STORE_IP') === '1';
$storeAgent = getenv('LEAD_STORE_USER_AGENT') === '1';

$leadPayload = [
    'visitor_phone' => $normalized['visitor_phone'],
    'visitor_country_code' => $normalized['visitor_country_code'],
    'visitor_full_phone' => $normalized['visitor_full_phone'],
    'source_domain' => $sourceDomain,
    'source_url' => $referrer,
    'page_title' => mb_substr($pageTitle, 0, 255),
    'whatsapp_redirect_url' => $redirectUrl,
    'ip_address' => $storeIp ? substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 100) : null,
    'user_agent' => $storeAgent ? substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 2000) : null,
];

if ($channel !== null && table_has_column('widget_leads', 'channel')) {
    $leadPayload['channel'] = $channel;
    $leadPayload['channel_selected_at'] = date('Y-m-d H:i:s');
}

try {
    $newLeadId = insert_widget_lead($widget, $leadPayload);
} catch (Throwable $exception) {
    json_response(['success' => false, 'message' => 'Unable to save lead'], 500);
}

json_response(['success' => true, 'message' => 'Lead saved', 'lead_id' => $newLeadId]);
