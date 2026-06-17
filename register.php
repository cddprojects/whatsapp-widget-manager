<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = current_user();
if ($user !== null) {
    if (is_superadmin()) {
        redirect('admin-client-create.php');
    }
    redirect_after_login($user);
}

redirect('login.php');
