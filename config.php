<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/env.php';

/*
 * Click To Chat Manager configuration.
 *
 * Update these values after importing database.sql, or define the matching
 * environment variables on your server.
 */
define('APP_NAME', 'Click To Chat Manager');
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_CLIENT', 'client');
define('USER_STATUS_ACTIVE', 'active');
define('USER_STATUS_DISABLED', 'disabled');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'click_to_chat_manager');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/*
 * Set SYSTEM_BASE_URL to the public URL where this app is hosted, for example:
 * https://chat.example.com
 */
define('SYSTEM_BASE_URL', rtrim(getenv('SYSTEM_BASE_URL') ?: 'http://localhost/click-to-chat-manager/whatsapp-widget-manager', '/'));

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Kuala_Lumpur');

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
        static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST;

    // Optional database port.
    // Local Docker can use DB_PORT=3307.
    // Production behavior remains unchanged when DB_PORT is not defined.
    $dbPort = trim((string) (getenv('DB_PORT') ?: ''));

    if ($dbPort !== '') {
        if (
            !ctype_digit($dbPort)
            || (int) $dbPort < 1
            || (int) $dbPort > 65535
        ) {
            throw new RuntimeException('DB_PORT must be a valid TCP port number.');
        }

        $dsn .= ';port=' . (int) $dbPort;
    }

    $dsn .= ';dbname=' . DB_NAME;
    $dsn .= ';charset=' . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
