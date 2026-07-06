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

$pageTitle = t('page.clients');
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading page-heading-row">
    <div>
        <p class="eyebrow"><?= e(t('eyebrow.super_admin')) ?></p>
        <h1><?= e(t('heading.clients')) ?></h1>
        <p><?= e(t('desc.clients')) ?></p>
    </div>
    <a class="btn btn-primary" href="admin-client-create.php"><?= e(t('button.add_client')) ?></a>
</section>

<section class="settings-card table-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('heading.all_clients')) ?></h2>
            <p class="results-meta inline-meta"><?= e(t($result['total'] === 1 ? 'results.clients_found_one' : 'results.clients_found_other', ['count' => (string) $result['total']])) ?></p>
        </div>
    </div>

    <form class="admin-filter-bar" method="get">
        <label class="search-field span-2">
            <span><?= e(t('filter.search')) ?></span>
            <input
                type="search"
                name="q"
                value="<?= e($query) ?>"
                placeholder="<?= e(t('filter.placeholder_clients')) ?>"
            >
        </label>
        <label>
            <span><?= e(t('filter.status')) ?></span>
            <select name="status">
                <option value="all"<?= selected($status, 'all') ?>><?= e(t('filter.all')) ?></option>
                <option value="active"<?= selected($status, 'active') ?>><?= e(t('status.active')) ?></option>
                <option value="disabled"<?= selected($status, 'disabled') ?>><?= e(t('status.disabled')) ?></option>
            </select>
        </label>
        <label>
            <span><?= e(t('filter.sort')) ?></span>
            <select name="sort">
                <option value="newest"<?= selected($sort, 'newest') ?>><?= e(t('filter.newest_first')) ?></option>
                <option value="oldest"<?= selected($sort, 'oldest') ?>><?= e(t('filter.oldest_first')) ?></option>
                <option value="name_az"<?= selected($sort, 'name_az') ?>><?= e(t('filter.name_az')) ?></option>
                <option value="most_widgets"<?= selected($sort, 'most_widgets') ?>><?= e(t('filter.most_widgets')) ?></option>
            </select>
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(t('button.apply')) ?></button>
            <a class="btn btn-light" href="admin-clients.php"><?= e(t('button.reset')) ?></a>
        </div>
    </form>

    <?php if (!$result['rows']): ?>
        <div class="empty-state compact-empty">
            <p><?= e(t('empty.no_clients_matched')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th><?= e(t('table.client_name')) ?></th>
                        <th><?= e(t('table.email')) ?></th>
                        <th><?= e(t('table.status')) ?></th>
                        <th><?= e(t('table.total_widgets')) ?></th>
                        <th><?= e(t('lead.today_title')) ?></th>
                        <th><?= e(t('lead.total_active_title')) ?></th>
                        <th><?= e(t('table.last_login')) ?></th>
                        <th><?= e(t('table.created')) ?></th>
                        <th class="col-actions"><?= e(t('table.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['rows'] as $client): ?>
                        <tr>
                            <td><strong><?= e($client['name']) ?></strong></td>
                            <td><?= e($client['email']) ?></td>
                            <td><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(translate_user_status((string) $client['status'])) ?></span></td>
                            <td><?= (int) $client['widget_count'] ?></td>
                            <td><?= (int) count_active_leads((int) $client['id'], true) ?></td>
                            <td><?= number_format(count_active_leads((int) $client['id'], false)) ?></td>
                            <td><?= e(format_datetime($client['last_login_at'] ?? null)) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $client['created_at']))) ?></td>
                            <td class="col-actions">
                                <a class="btn btn-small btn-light" href="admin-client-leads.php?client_id=<?= (int) $client['id'] ?>"><?= e(t('button.view_leads')) ?></a>
                                <a class="btn btn-small btn-primary" href="admin-client-detail.php?id=<?= (int) $client['id'] ?>"><?= e(t('button.manage')) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="pagination-bar">
                <?php if ($page > 1): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['q' => $query, 'status' => $status, 'sort' => $sort, 'page' => $page - 1])) ?>"><?= e(t('pagination.previous')) ?></a>
                <?php endif; ?>
                <span><?= e(t('pagination.page_of', ['page' => (string) $page, 'pages' => (string) $result['pages']])) ?></span>
                <?php if ($page < $result['pages']): ?>
                    <a class="btn btn-light" href="?<?= e(http_build_query(['q' => $query, 'status' => $status, 'sort' => $sort, 'page' => $page + 1])) ?>"><?= e(t('pagination.next')) ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
