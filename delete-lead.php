<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'message' => t('error.access_denied')], 405);
}

$user = require_superadmin_json();

$postedToken = $_POST['csrf_token'] ?? '';
if (!is_string($postedToken) || !hash_equals(csrf_token(), $postedToken)) {
    json_response(['success' => false, 'message' => t('csrf.invalid_token')], 419);
}

$leadId = (int) ($_POST['lead_id'] ?? 0);
if ($leadId <= 0) {
    json_response(['success' => false, 'message' => t('lead.delete_failed')], 422);
}

$result = delete_widget_lead($leadId, (int) $user['id']);
$status = (int) ($result['http_status'] ?? ($result['success'] ? 200 : 500));
unset($result['http_status']);

json_response($result, $status);
