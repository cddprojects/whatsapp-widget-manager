<?php
declare(strict_types=1);

function render_lead_filter_pagination_fields(int $perPage): void
{
    ?>
    <input type="hidden" name="per_page" value="<?= (int) $perPage ?>">
    <?php
}

function render_lead_list_pagination(
    string $formAction,
    array $result,
    array $queryParams,
    int $perPage
): void {
    $total = (int) ($result['total'] ?? 0);
    $page = (int) ($result['page'] ?? 1);
    $pages = (int) ($result['pages'] ?? 1);
    $range = lead_list_visible_range($page, $perPage, $total);
    $baseParams = build_lead_list_query_params(array_merge($queryParams, [
        'per_page' => $perPage,
    ]));
    $previousParams = $baseParams;
    $nextParams = $baseParams;

    if ($page > 1) {
        $previousParams['page'] = $page - 1;
    } else {
        unset($previousParams['page']);
    }

    if ($page < $pages) {
        $nextParams['page'] = $page + 1;
    } else {
        unset($nextParams['page']);
    }

    $previousUrl = $formAction . '?' . http_build_query($previousParams);
    $nextUrl = $formAction . '?' . http_build_query($nextParams);
    ?>
    <div class="lead-pagination-bar" data-lead-pagination>
        <p class="lead-pagination-summary">
            <?php if ($total <= 0): ?>
                <?= e(t('results.leads_found_other', ['count' => '0'])) ?>
            <?php else: ?>
                <?= e(t('pagination.showing_range', [
                    'from' => (string) $range['from'],
                    'to' => (string) $range['to'],
                    'total' => (string) $total,
                ])) ?>
            <?php endif; ?>
        </p>

        <form class="lead-pagination-per-page" method="get" action="<?= e($formAction) ?>" data-lead-per-page-form>
            <?php foreach ($baseParams as $key => $value): ?>
                <?php if ($key === 'per_page' || $key === 'page') {
                    continue;
                } ?>
                <input type="hidden" name="<?= e((string) $key) ?>" value="<?= e((string) $value) ?>">
            <?php endforeach; ?>
            <label class="lead-pagination-per-page-label">
                <span><?= e(t('pagination.rows_per_page')) ?></span>
                <select name="per_page" data-lead-per-page-select>
                    <?php foreach (lead_list_allowed_per_page_options() as $option): ?>
                        <option value="<?= (int) $option ?>"<?= $option === $perPage ? ' selected' : '' ?>>
                            <?= e(t('pagination.per_page_option', ['count' => (string) $option])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <div class="lead-pagination-nav">
            <?php if ($page > 1): ?>
                <a class="btn btn-light" href="<?= e($previousUrl) ?>"><?= e(t('pagination.previous')) ?></a>
            <?php else: ?>
                <span class="btn btn-light is-disabled" aria-disabled="true"><?= e(t('pagination.previous')) ?></span>
            <?php endif; ?>
            <span class="lead-pagination-page"><?= e(t('pagination.page_of', ['page' => (string) $page, 'pages' => (string) $pages])) ?></span>
            <?php if ($page < $pages): ?>
                <a class="btn btn-light" href="<?= e($nextUrl) ?>"><?= e(t('pagination.next')) ?></a>
            <?php else: ?>
                <span class="btn btn-light is-disabled" aria-disabled="true"><?= e(t('pagination.next')) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
