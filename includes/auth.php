<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user !== null) {
        return $user;
    }

    $stmt = db()->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    if ($user === null) {
        unset($_SESSION['user_id']);
    }

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }

    return $user;
}

function redirect_if_authenticated(): void
{
    if (current_user() !== null) {
        redirect('dashboard.php');
    }
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    unset($_SESSION['login_attempts'], $_SESSION['login_block_until']);
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
