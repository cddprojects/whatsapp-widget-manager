<?php
declare(strict_types=1);

function normalize_request_path(?string $path): string
{
    $path = is_string($path) ? $path : '/';
    $path = '/' . trim($path, '/');

    if ($path === '//' || $path === '') {
        return '/';
    }

    return $path;
}

function app_routes(): array
{
    static $routes = null;

    if ($routes === null) {
        $routes = require __DIR__ . '/routes.php';
    }

    return $routes;
}

function dispatch_app_request(): void
{
    $routes = app_routes();

    $requestPath = normalize_request_path(
        parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH)
    );

    if ($requestPath === '/') {
        $requestPath = '/login';
    }

    $route = $routes[$requestPath] ?? null;

    if ($route === null) {
        http_response_code(404);
        require dirname(__DIR__) . '/404.php';
        exit;
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $allowedMethods = $route['methods'] ?? ['GET'];

    if (!in_array($method, $allowedMethods, true)) {
        header('Allow: ' . implode(', ', $allowedMethods));
        http_response_code(405);
        exit('Method Not Allowed');
    }

    $file = $route['file'] ?? '';

    if (!is_string($file) || !preg_match('/^[A-Za-z0-9_-]+\.php$/', $file)) {
        http_response_code(500);
        exit('Invalid route target');
    }

    $target = dirname(__DIR__) . '/' . $file;

    if (!is_file($target)) {
        http_response_code(500);
        exit('Route target not found');
    }

    if (!defined('CTC_FRONT_CONTROLLER')) {
        define('CTC_FRONT_CONTROLLER', true);
    }

    if (!defined('CTC_APP_ROUTE')) {
        define('CTC_APP_ROUTE', $requestPath);
    }

    if (!defined('CTC_APP_FILE')) {
        define('CTC_APP_FILE', $file);
    }

    require $target;
}
