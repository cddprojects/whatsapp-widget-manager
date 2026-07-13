<?php
declare(strict_types=1);

require_once __DIR__ . '/router.php';

function app_public_endpoints(): array
{
    return [
        'embed.js.php',
        'widget.php',
        'save-widget-lead.php',
        'resolve-widget-destination.php',
    ];
}

function app_route_path_by_file(string $phpFile): ?string
{
    $basename = basename($phpFile);

    foreach (app_routes() as $path => $route) {
        if (($route['file'] ?? '') === $basename) {
            return $path;
        }
    }

    return null;
}

function app_route_file_by_path(string $path): ?string
{
    $path = normalize_request_path($path);
    $routes = app_routes();

    if (!isset($routes[$path]['file'])) {
        return null;
    }

    $file = $routes[$path]['file'];

    return is_string($file) ? $file : null;
}

function normalize_route_target(string $target): string
{
    $target = trim($target);

    if ($target === '') {
        return '/';
    }

    if (str_starts_with($target, '/')) {
        return normalize_request_path($target);
    }

    if (str_contains($target, '.php')) {
        $mapped = app_route_path_by_file($target);

        return $mapped ?? normalize_request_path('/' . substr(basename($target), 0, -4));
    }

    return normalize_request_path('/' . $target);
}

function app_path(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        return '/';
    }

    $parts = explode('?', $path, 2);
    $routePath = normalize_route_target($parts[0]);
    $query = [];

    if (isset($parts[1]) && $parts[1] !== '') {
        parse_str($parts[1], $query);
    }

    return app_url(ltrim($routePath, '/'), $query);
}

function app_url(string $target, array $query = []): string
{
    if ($query === [] && str_contains($target, '?')) {
        $parts = explode('?', $target, 2);
        $target = $parts[0];
        if (isset($parts[1]) && $parts[1] !== '') {
            parse_str($parts[1], $query);
        }
    }

    $routePath = normalize_route_target($target);
    $url = $routePath;

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function route_url(string $target, array $query = []): string
{
    return rtrim(SYSTEM_BASE_URL, '/') . app_url($target, $query);
}

function current_app_route(): string
{
    if (defined('CTC_APP_ROUTE') && is_string(CTC_APP_ROUTE) && CTC_APP_ROUTE !== '') {
        return CTC_APP_ROUTE;
    }

    $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '/';

    return normalize_request_path($uri);
}

function current_app_path(): string
{
    return current_app_route();
}

function current_app_page(): string
{
    if (defined('CTC_APP_FILE') && is_string(CTC_APP_FILE) && CTC_APP_FILE !== '') {
        return CTC_APP_FILE;
    }

    $mapped = app_route_file_by_path(current_app_route());
    if ($mapped !== null) {
        return $mapped;
    }

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script !== '' && str_ends_with($script, '.php')) {
        return $script;
    }

    return 'dashboard.php';
}

function app_slug_from_php(string $phpFile): string
{
    $mapped = app_route_path_by_file($phpFile);
    if ($mapped !== null) {
        return ltrim($mapped, '/');
    }

    $basename = basename($phpFile);
    if (str_ends_with($basename, '.php')) {
        return substr($basename, 0, -4);
    }

    return ltrim($basename, '/');
}

function redirect_legacy_php_request(string $phpFile): void
{
    maybe_redirect_legacy_php_request($phpFile);
}

function maybe_redirect_legacy_php_request(?string $phpFile = null): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (defined('CTC_FRONT_CONTROLLER') && CTC_FRONT_CONTROLLER) {
        return;
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method !== 'GET' && $method !== 'HEAD') {
        return;
    }

    if ($phpFile === null) {
        $phpFile = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    } else {
        $phpFile = basename($phpFile);
    }

    if ($phpFile === '' || !str_ends_with($phpFile, '.php')) {
        return;
    }

    if (in_array($phpFile, app_public_endpoints(), true)) {
        return;
    }

    $routePath = app_route_path_by_file($phpFile);
    if ($routePath === null) {
        return;
    }

    $routes = app_routes();
    $route = $routes[$routePath] ?? null;
    $allowedMethods = $route['methods'] ?? ['GET'];

    if ($method === 'GET' && !in_array('GET', $allowedMethods, true)) {
        return;
    }

    if ($method === 'HEAD' && !in_array('HEAD', $allowedMethods, true) && !in_array('GET', $allowedMethods, true)) {
        return;
    }

    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $target = $routePath . ($query !== '' ? '?' . $query : '');

    header('Location: ' . $target, true, 301);
    exit;
}
