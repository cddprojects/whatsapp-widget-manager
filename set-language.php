<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!is_post()) {
    redirect('login.php');
}

verify_csrf();

$language = normalize_locale((string) ($_POST['language'] ?? ''));
$user = current_user();
$fallback = $user ? (is_superadmin_user($user) ? 'dashboard.php' : 'client-dashboard.php') : 'login.php';
$redirectPath = safe_redirect_path($_POST['redirect'] ?? '', $fallback);

if ($user) {
    update_user_preferred_language((int) $user['id'], $language);
    auth_reset_current_user_cache();
}

$_SESSION['locale'] = $language;
set_preferred_language_cookie($language);
set_current_locale($language);

redirect($redirectPath);
