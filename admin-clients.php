<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_superadmin();

$query = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? 'all');
$sort = (string) ($_GET['sort'] ?? 'newest');
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = search_clients([
    'q' => $query,
    'status' => $status,
    'sort' => $sort,
    'page' => $page,
    'per_page' => 20,
]);

$pageTitle = 'Clients';
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <p class="eyebrow">Super admin</p>
    <h1>Clients</h1>
    <p>Search and manage all client accounts.</p>
</section>

<section class="settings-card">
    <form class="admin-filter-bar" method="get">
        <label class="search-field span-2">
            <span>Search</span>
            <input
                type="search"
                name="q"
                value="<?= e($query) ?>"
                placeholder="Search client name, email, domain, or widget name…"
            >
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="all"<?= selected($status, 'all') ?>>All</option>
                <option value="active"<?= selected($status, 'active') ?>>Active</option>
                <option value="disabled"<?= selected($status, 'disabled') ?>>Disabled</option>
            </select>
        </label>
        <label>
            <span>Sort</span>
            <select name="sort">
                <option value="newest"<?= selected($sort, 'newest') ?>>Newest first</option>
                <option value="oldest"<?= selected($sort, 'oldest') ?>>Oldest first</option>
                <option value="name_az"<?= selected($sort, 'name_az') ?>>Name A-Z</option>
                <option value="most_widgets"<?= selected($sort, 'most_widgets') ?>>Most widgets</option>
            </select>
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a class="btn btn-light" href="admin-clients.php">Reset</a>
        </div>
    </form>

    <p class="results-meta"><?= (int) $result['total'] ?> client<?= $result['total'] === 1 ? '' : 's' ?> found</p>

    <?php if (!$result['rows']): ?>
        <div class="empty-state compact-empty">
            <p>No clients matched your search.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th>Client name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Total widgets</th>
                        <th>Last login</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['rows'] as $client): ?>
                        <tr>
                            <td><strong><?= e($client['name']) ?></strong></td>
                            <td><?= e($client['email']) ?></td>
                            <td><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(ucfirst((string) $client['status'])) ?></span></td>
                            <td><?= (int) $client['widget_count'] ?></td>
                            <td><?= e(format_datetime($client['last_login_at'] ?? null)) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $client['created_at']))) ?></td>
                            <td><a class="btn btn-small btn-primary-soft" href="admin-client-detail.php?id=<?= (int) $client['id'] ?>">View Client</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="pagination-bar">
                <?php if ($page > 1): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['q' => $query, 'status' => $status, 'sort' => $sort, 'page' => $page - 1])) ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?= (int) $page ?> of <?= (int) $result['pages'] ?></span>
                <?php if ($page < $result['pages']): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['q' => $query, 'status' => $status, 'sort' => $sort, 'page' => $page + 1])) ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
