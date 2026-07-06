<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'message' => t('error.access_denied')], 405);
}

$user = require_superadmin_json();
verify_lead_post_csrf();

$leadId = (int) ($_POST['lead_id'] ?? 0);
if ($leadId <= 0) {
    json_response(['success' => false, 'message' => t('lead.permanent_delete_failed')], 422);
}

$result = permanently_delete_lead($leadId, (int) $user['id']);
$status = (int) ($result['http_status'] ?? ($result['success'] ? 200 : 500));
unset($result['http_status']);

json_response($result, $status);
