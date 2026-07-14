<?php
declare(strict_types=1);

return [
    '/login' => [
        'file' => 'login.php',
        'methods' => ['GET', 'POST'],
        'public' => true,
    ],
    '/logout' => [
        'file' => 'logout.php',
        'methods' => ['GET'],
    ],
    '/register' => [
        'file' => 'register.php',
        'methods' => ['GET'],
        'public' => true,
    ],
    '/dashboard' => [
        'file' => 'dashboard.php',
        'methods' => ['GET'],
    ],
    '/clients' => [
        'file' => 'admin-clients.php',
        'methods' => ['GET'],
    ],
    '/manage-client' => [
        'file' => 'admin-client-detail.php',
        'methods' => ['GET', 'POST'],
    ],
    '/admin-client-create' => [
        'file' => 'admin-client-create.php',
        'methods' => ['GET', 'POST'],
    ],
    '/admin-client-edit' => [
        'file' => 'admin-client-edit.php',
        'methods' => ['GET', 'POST'],
    ],
    '/admin-client-delete' => [
        'file' => 'admin-client-delete.php',
        'methods' => ['GET', 'POST'],
    ],
    '/admin-client-reset-password' => [
        'file' => 'admin-client-reset-password.php',
        'methods' => ['GET', 'POST'],
    ],
    '/admin-client-leads' => [
        'file' => 'admin-client-leads.php',
        'methods' => ['GET'],
    ],
    '/admin-settings' => [
        'file' => 'admin-settings.php',
        'methods' => ['GET', 'POST'],
    ],
    '/admin-widgets' => [
        'file' => 'admin-widgets.php',
        'methods' => ['GET', 'POST'],
    ],
    '/all-leads' => [
        'file' => 'all-leads.php',
        'methods' => ['GET'],
    ],
    '/lead-recycle-bin' => [
        'file' => 'lead-recycle-bin.php',
        'methods' => ['GET'],
    ],
    '/client-dashboard' => [
        'file' => 'client-dashboard.php',
        'methods' => ['GET'],
    ],
    '/client-leads' => [
        'file' => 'client-leads.php',
        'methods' => ['GET'],
    ],
    '/create-widget' => [
        'file' => 'create-widget.php',
        'methods' => ['GET', 'POST'],
    ],
    '/edit-widget' => [
        'file' => 'edit-widget.php',
        'methods' => ['GET', 'POST'],
    ],
    '/edit-widget-phone' => [
        'file' => 'edit-widget-phone.php',
        'methods' => ['GET', 'POST'],
    ],
    '/embed-code' => [
        'file' => 'embed-code.php',
        'methods' => ['GET'],
    ],
    '/widget-preview' => [
        'file' => 'widget-preview.php',
        'methods' => ['GET'],
    ],
    '/set-language' => [
        'file' => 'set-language.php',
        'methods' => ['POST'],
    ],
    '/upload-phone-numbers' => [
        'file' => 'upload-phone-numbers.php',
        'methods' => ['POST'],
    ],
    '/update-phone-numbers' => [
        'file' => 'update-phone-numbers.php',
        'methods' => ['POST'],
    ],
    '/delete-lead' => [
        'file' => 'delete-lead.php',
        'methods' => ['POST'],
    ],
    '/restore-lead' => [
        'file' => 'restore-lead.php',
        'methods' => ['POST'],
    ],
    '/permanently-delete-lead' => [
        'file' => 'permanently-delete-lead.php',
        'methods' => ['POST'],
    ],
    '/bulk-delete-leads' => [
        'file' => 'bulk-delete-leads.php',
        'methods' => ['POST'],
    ],
    '/bulk-restore-leads' => [
        'file' => 'bulk-restore-leads.php',
        'methods' => ['POST'],
    ],
    '/bulk-permanently-delete-leads' => [
        'file' => 'bulk-permanently-delete-leads.php',
        'methods' => ['POST'],
    ],
    '/export-leads' => [
        'file' => 'export-leads.php',
        'methods' => ['GET'],
    ],
    '/export-widget-leads' => [
        'file' => 'export-widget-leads.php',
        'methods' => ['GET'],
    ],
    '/api/v1/leads/summary' => [
        'file' => 'api-v1-leads-summary.php',
        'methods' => ['GET'],
        'public' => true,
    ],
    '/api-credentials/manage' => [
        'file' => 'api-credentials-manage.php',
        'methods' => ['POST'],
    ],
];
