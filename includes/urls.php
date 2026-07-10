<?php
declare(strict_types=1);

function app_route_map(): array
{
    return [
        'admin-clients.php' => 'clients',
        'admin-client-detail.php' => 'manage-client',
    ];
}

function app_public_endpoints(): array
{
    return [
        'embed.js.php',
        'widget.php',
        'save-widget-lead.php',
        'resolve-widget-destination.php',
    ];
}

function app_pages(): array
{
    return [
        'login.php',
        'logout.php',
        'register.php',
        'dashboard.php',
        'admin-clients.php',
        'admin-client-create.php',
        'admin-client-detail.php',
        'admin-client-edit.php',
        'admin-client-delete.php',
        'admin-client-reset-password.php',
        'admin-client-leads.php',
        'admin-settings.php',
        'admin-widgets.php',
        'all-leads.php',
        'lead-recycle-bin.php',
        'client-dashboard.php',
        'client-leads.php',
        'create-widget.php',
        'edit-widget.php',
        'edit-widget-phone.php',
        'embed-code.php',
        'widget-preview.php',
        'set-language.php',
        'upload-phone-numbers.php',
        'update-phone-numbers.php',
        'delete-lead.php',
        'restore-lead.php',
        'permanently-delete-lead.php',
        'bulk-delete-leads.php',
        'bulk-restore-leads.php',
        'bulk-permanently-delete-leads.php',
        'export-leads.php',
        'export-widget-leads.php',
    ];
}

function app_slug_from_php(string $phpFile): string
{
    $basename = basename($phpFile);
    $map = app_route_map();

    if (isset($map[$basename])) {
        return $map[$basename];
    }

    if (str_ends_with($basename, '.php')) {
        return substr($basename, 0, -4);
    }

    return ltrim($basename, '/');
}

function app_php_from_slug(string $slug): string
{
    $reverse = array_flip(app_route_map());

    if (isset($reverse[$slug])) {
        return (string) $reverse[$slug];
    }

    return $slug . '.php';
}

function app_url(string $phpFile, array $query = []): string
{
    $slug = app_slug_from_php($phpFile);
    $url = '/' . $slug;

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function app_path(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        return '/';
    }

    if (!str_contains($path, '.php')) {
        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    $parts = explode('?', $path, 2);
    $query = [];

    if (isset($parts[1]) && $parts[1] !== '') {
        parse_str($parts[1], $query);
    }

    return app_url($parts[0], $query);
}

function current_app_page(): string
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if ($script !== '' && str_ends_with($script, '.php')) {
        return $script;
    }

    $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '';
    $slug = trim((string) $uri, '/');

    if ($slug === '') {
        return 'dashboard.php';
    }

    return app_php_from_slug($slug);
}

function app_clean_redirect_slugs(): array
{
    $slugs = [];

    foreach (app_pages() as $page) {
        $slugs[] = app_slug_from_php($page);
    }

    return array_values(array_unique($slugs));
}
