<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'message' => t('error.access_denied')], 405);
}

$user = require_superadmin_json();
verify_lead_post_csrf();

$leadIds = $_POST['lead_ids'] ?? [];
if (!is_array($leadIds)) {
    json_response(['success' => false, 'message' => t('lead.restore_none_selected')], 422);
}

$result = bulk_restore_leads($leadIds, (int) $user['id']);
$status = (int) ($result['http_status'] ?? ($result['success'] ? 200 : 500));
unset($result['http_status']);

json_response($result, $status);
