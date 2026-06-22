<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function is_logged_in(): bool
{
    return current_user() !== null;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user !== null) {
        return $user;
    }

    $stmt = db()->prepare(
        'SELECT id, name, email, role, status, created_at, last_login_at, password_changed_at, updated_at
         FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    if ($user === null) {
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        return null;
    }

    $_SESSION['user_role'] = (string) $user['role'];
    return $user;
}

function is_superadmin(): bool
{
    $user = current_user();
    return $user !== null && (string) $user['role'] === ROLE_SUPERADMIN;
}

function is_client(): bool
{
    $user = current_user();
    return $user !== null && (string) $user['role'] === ROLE_CLIENT;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }

    if ((string) $user['status'] === USER_STATUS_DISABLED) {
        logout_user();
        redirect('login.php');
    }

    return $user;
}

function require_superadmin(): array
{
    $user = require_login();
    if (!is_superadmin()) {
        if (is_client()) {
            redirect('client-dashboard.php');
        }
        http_response_code(403);
        exit('Access denied.');
    }

    return $user;
}

function require_client(): array
{
    $user = require_login();
    if (!is_client()) {
        redirect('dashboard.php');
    }

    return $user;
}

function redirect_if_authenticated(): void
{
    $user = current_user();
    if ($user === null) {
        return;
    }

    redirect_after_login($user);
}

function redirect_after_login(array $user): void
{
    redirect(is_superadmin_user($user) ? 'dashboard.php' : 'client-dashboard.php');
}

function is_superadmin_user(array $user): bool
{
    return (string) ($user['role'] ?? '') === ROLE_SUPERADMIN;
}

function login_user(int $userId): void
{
    $stmt = db()->prepare('SELECT id, role, status FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || (string) $user['status'] === USER_STATUS_DISABLED) {
        return;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = (string) $user['role'];
    unset($_SESSION['login_attempts'], $_SESSION['login_block_until']);

    $update = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $update->execute(['id' => $userId]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function login_is_limited(): bool
{
    return !empty($_SESSION['login_block_until']) && time() < (int) $_SESSION['login_block_until'];
}

function record_failed_login(): void
{
    $now = time();
    $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'first_at' => $now];

    if ($now - (int) $attempts['first_at'] > 900) {
        $attempts = ['count' => 0, 'first_at' => $now];
    }

    $attempts['count'] = (int) $attempts['count'] + 1;
    $_SESSION['login_attempts'] = $attempts;

    if ($attempts['count'] >= 5) {
        $_SESSION['login_block_until'] = $now + 300;
    }
}

function find_widget_by_id(int $widgetId): ?array
{
    $stmt = db()->prepare('SELECT * FROM widgets WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $widgetId]);
    $widget = $stmt->fetch();
    return $widget ?: null;
}

function find_accessible_widget(int $widgetId): ?array
{
    $widget = find_widget_by_id($widgetId);
    if (!$widget) {
        return null;
    }

    if (is_superadmin()) {
        return $widget;
    }

    $user = current_user();
    if ($user && (int) $widget['user_id'] === (int) $user['id']) {
        return $widget;
    }

    return null;
}

function require_widget_access(int $widgetId): array
{
    require_login();
    $widget = find_accessible_widget($widgetId);
    if (!$widget) {
        http_response_code(is_logged_in() ? 403 : 404);
        exit(is_logged_in() ? 'Access denied.' : 'Widget not found.');
    }

    return $widget;
}

function can_edit_full_widget(array $widget): bool
{
    return is_superadmin();
}

function can_edit_phone_only(array $widget): bool
{
    if (is_superadmin()) {
        return true;
    }

    $user = current_user();
    return $user !== null && (int) $widget['user_id'] === (int) $user['id'];
}

function find_client_user(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, name, email, role, status, created_at, last_login_at, updated_at
         FROM users WHERE id = :id AND role = :role LIMIT 1'
    );
    $stmt->execute(['id' => $userId, 'role' => ROLE_CLIENT]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function count_superadmins(): int
{
    $stmt = db()->query("SELECT COUNT(*) FROM users WHERE role = '" . ROLE_SUPERADMIN . "'");
    return (int) $stmt->fetchColumn();
}

function block_client_admin_access(): void
{
    if (is_client()) {
        redirect('client-dashboard.php');
    }
}
