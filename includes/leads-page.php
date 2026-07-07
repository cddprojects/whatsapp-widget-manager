<?php
declare(strict_types=1);

/** @var string $leadPageMode client|superadmin|recycle_bin|all_leads */
/** @var string $sort */
/** @var array $result */
/** @var string $query */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var int $widgetFilterId */
/** @var int $clientFilterId */
/** @var array $widgetOptions */
/** @var array $clientOptions */
/** @var string $exportUrl */
/** @var string $formAction */
/** @var bool $showClientOwnerColumn */
/** @var bool $showClientColumn */
/** @var bool $showRecycleMeta */
/** @var string $deleteSingleLabel */
/** @var string $deleteBulkLabel */
/** @var string $emptyMessage */
/** @var string $csrfToken */

$isRecycleBin = $leadPageMode === 'recycle_bin';
$isClientPage = $leadPageMode === 'client';
$isAllLeadsPage = $leadPageMode === 'all_leads';
$isSuperadminPage = $leadPageMode === 'superadmin';
$useSuperadminLeadActions = $isSuperadminPage || $isAllLeadsPage;
$sort = $sort ?? 'newest';
$showSelection = true;
$showDeleteActions = !$isRecycleBin;
$showRestoreActions = $isRecycleBin;
$showClientWidgetColumn = $isAllLeadsPage || ($isSuperadminPage && $showClientColumn && $clientFilterId <= 0);
$hasWidgetFilter = $widgetOptions !== [];
$hasClientFilter = $showClientColumn && $clientOptions !== [];
$recyclePrimaryRowClass = 'recycle-bin-filter-row recycle-bin-filter-row-primary';
if (!$hasClientFilter && !$hasWidgetFilter) {
    $recyclePrimaryRowClass .= ' recycle-bin-filter-row-primary--search-only';
} elseif (!$hasClientFilter) {
    $recyclePrimaryRowClass .= ' recycle-bin-filter-row-primary--no-client';
} elseif (!$hasWidgetFilter) {
    $recyclePrimaryRowClass .= ' recycle-bin-filter-row-primary--no-widget';
}
$hasExport = !$isRecycleBin && $result['total'] > 0;
$tableClass = 'widget-table lead-table' . ($isRecycleBin ? ' lead-table-layout--recycle' : ' lead-table-layout--active');
$leadPageCardClass = 'settings-card lead-page-card' . ($isRecycleBin ? ' lead-recycle-bin-card' : '');
if ($showClientWidgetColumn) {
    $tableClass .= ' lead-table-layout--global';
}

$toolbarDefaultButtons = static function () use ($hasExport, $exportUrl, $isRecycleBin): void {
    ?>
    <button type="submit" class="btn btn-primary"><?= e(t('button.filter')) ?></button>
    <?php if ($hasExport): ?>
        <a class="btn btn-light" href="<?= e($exportUrl) ?>"><?= e(t('button.export_csv')) ?></a>
    <?php elseif (!$isRecycleBin): ?>
        <span class="btn btn-light is-disabled" aria-disabled="true"><?= e(t('button.export_csv')) ?></span>
    <?php endif; ?>
    <?php
};

$toolbarSelectedButtons = static function () use (
    $hasExport,
    $exportUrl,
    $showDeleteActions,
    $showRestoreActions,
    $useSuperadminLeadActions
): void {
    ?>
    <span class="lead-selected-pill" data-selected-lead-count><?= e(t('lead.selected_count', ['count' => '0'])) ?></span>
    <?php if ($hasExport): ?>
        <a class="btn btn-light" href="<?= e($exportUrl) ?>"><?= e(t('button.export_csv')) ?></a>
    <?php endif; ?>
    <?php if ($showDeleteActions): ?>
        <?php if ($useSuperadminLeadActions): ?>
            <button type="button" class="btn btn-light btn-lead-bulk-action" data-delete-selected-leads>
                <span class="lead-action-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M2.5 4.5A1.5 1.5 0 0 1 4 3h12a1.5 1.5 0 0 1 1.415 1.002l-2 6A1.5 1.5 0 0 1 14.001 11H6.236l-.586 2.344A1.5 1.5 0 0 1 4.18 14.5H3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h.68l2-8H4a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h1.5Zm3.736 6 1-4h7.265l1.333 4H6.236Z"/></svg>
                </span>
                <?= e(t('lead.move_to_bin')) ?>
            </button>
        <?php else: ?>
            <button type="button" class="btn btn-danger-soft btn-lead-bulk-action" data-delete-selected-leads><?= e(t('button.delete_selected')) ?></button>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($showRestoreActions): ?>
        <button type="button" class="btn btn-primary-soft btn-lead-bulk-action" data-restore-selected-leads><?= e(t('lead.restore_selected')) ?></button>
        <button type="button" class="btn btn-danger-soft btn-lead-bulk-action" data-permanent-delete-selected-leads><?= e(t('lead.permanent_delete_selected')) ?></button>
    <?php endif; ?>
    <?php
};
?>

<section
    class="<?= e($leadPageCardClass) ?>"
    data-leads-page
    data-leads-mode="<?= e($leadPageMode) ?>"
    data-csrf-token="<?= e($csrfToken) ?>"
    data-total-leads="<?= (int) $result['total'] ?>"
    data-empty-message="<?= e($emptyMessage) ?>"
>
    <?php if ($isRecycleBin): ?>
        <form class="recycle-bin-filter-form admin-filter-bar" method="get" action="<?= e($formAction) ?>">
            <div class="<?= e($recyclePrimaryRowClass) ?>">
                <label class="search-field lead-filter-search">
                    <span><?= e(t('filter.search')) ?></span>
                    <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(t('filter.placeholder_leads')) ?>">
                </label>
                <?php if ($hasClientFilter): ?>
                    <label class="lead-filter-client">
                        <span><?= e(t('filter.client')) ?></span>
                        <select name="client_id">
                            <option value="0"><?= e(t('filter.all_clients')) ?></option>
                            <?php foreach ($clientOptions as $clientOption): ?>
                                <option value="<?= (int) $clientOption['id'] ?>"<?= (int) $clientOption['id'] === $clientFilterId ? ' selected' : '' ?>>
                                    <?= e($clientOption['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <?php if ($hasWidgetFilter): ?>
                    <label class="lead-filter-widget">
                        <span><?= e(t('filter.widget')) ?></span>
                        <select name="widget_id">
                            <option value="0"><?= e(t('filter.all_widgets')) ?></option>
                            <?php foreach ($widgetOptions as $widgetOption): ?>
                                <option value="<?= (int) $widgetOption['id'] ?>"<?= (int) $widgetOption['id'] === $widgetFilterId ? ' selected' : '' ?>>
                                    <?= e($widgetOption['widget_name']) ?><?php if (!empty($widgetOption['owner_name'])): ?> — <?= e($widgetOption['owner_name']) ?><?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
            </div>
            <div class="recycle-bin-filter-row recycle-bin-filter-row-secondary">
                <label class="lead-filter-from">
                    <span><?= e(t('filter.from_date')) ?></span>
                    <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
                </label>
                <label class="lead-filter-to">
                    <span><?= e(t('filter.to_date')) ?></span>
                    <input type="date" name="date_to" value="<?= e($dateTo) ?>">
                </label>
                <div class="recycle-bin-filter-actions">
                    <div class="lead-toolbar-default" data-lead-toolbar-default>
                        <?php $toolbarDefaultButtons(); ?>
                    </div>
                    <?php if ($showRestoreActions): ?>
                        <div class="lead-toolbar-selected" data-lead-toolbar-selected hidden>
                            <?php $toolbarSelectedButtons(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php elseif ($isAllLeadsPage): ?>
        <form class="all-leads-filter-form admin-filter-bar" method="get" action="<?= e($formAction) ?>">
            <div class="all-leads-filter-row all-leads-filter-row-primary">
                <label class="search-field lead-filter-search">
                    <span><?= e(t('filter.search')) ?></span>
                    <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(t('filter.placeholder_leads')) ?>">
                </label>
                <?php if ($hasClientFilter): ?>
                    <label class="lead-filter-client">
                        <span><?= e(t('filter.client')) ?></span>
                        <select name="client_id">
                            <option value="0"><?= e(t('filter.all_clients')) ?></option>
                            <?php foreach ($clientOptions as $clientOption): ?>
                                <option value="<?= (int) $clientOption['id'] ?>"<?= (int) $clientOption['id'] === $clientFilterId ? ' selected' : '' ?>>
                                    <?= e($clientOption['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <?php if ($hasWidgetFilter): ?>
                    <label class="lead-filter-widget">
                        <span><?= e(t('filter.widget')) ?></span>
                        <select name="widget_id">
                            <option value="0"><?= e(t('filter.all_widgets')) ?></option>
                            <?php foreach ($widgetOptions as $widgetOption): ?>
                                <option value="<?= (int) $widgetOption['id'] ?>"<?= (int) $widgetOption['id'] === $widgetFilterId ? ' selected' : '' ?>>
                                    <?= e($widgetOption['widget_name']) ?><?php if (!empty($widgetOption['owner_name'])): ?> — <?= e($widgetOption['owner_name']) ?><?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <label class="lead-filter-sort">
                    <span><?= e(t('filter.sort')) ?></span>
                    <select name="sort">
                        <option value="newest"<?= $sort === 'newest' ? ' selected' : '' ?>><?= e(t('filter.newest_first')) ?></option>
                        <option value="oldest"<?= $sort === 'oldest' ? ' selected' : '' ?>><?= e(t('filter.oldest_first')) ?></option>
                        <option value="phone_az"<?= $sort === 'phone_az' ? ' selected' : '' ?>><?= e(t('filter.lead_phone_az')) ?></option>
                        <option value="phone_za"<?= $sort === 'phone_za' ? ' selected' : '' ?>><?= e(t('filter.lead_phone_za')) ?></option>
                        <option value="client_az"<?= $sort === 'client_az' ? ' selected' : '' ?>><?= e(t('filter.client_name_az')) ?></option>
                        <option value="client_za"<?= $sort === 'client_za' ? ' selected' : '' ?>><?= e(t('filter.client_name_za')) ?></option>
                    </select>
                </label>
            </div>
            <div class="all-leads-filter-row all-leads-filter-row-secondary">
                <label class="lead-filter-from">
                    <span><?= e(t('filter.from_date')) ?></span>
                    <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
                </label>
                <label class="lead-filter-to">
                    <span><?= e(t('filter.to_date')) ?></span>
                    <input type="date" name="date_to" value="<?= e($dateTo) ?>">
                </label>
                <div class="all-leads-toolbar-actions">
                    <div class="lead-toolbar-default" data-lead-toolbar-default>
                        <?php $toolbarDefaultButtons(); ?>
                    </div>
                    <?php if ($showDeleteActions): ?>
                        <div class="lead-toolbar-selected" data-lead-toolbar-selected hidden>
                            <?php $toolbarSelectedButtons(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php elseif ($isClientPage): ?>
        <form
            class="client-lead-filter-form admin-filter-bar<?= $hasWidgetFilter ? '' : ' client-lead-filter-form--no-widget' ?>"
            method="get"
            action="<?= e($formAction) ?>"
        >
            <div class="client-lead-filter-row client-lead-filter-row-primary<?= $hasWidgetFilter ? '' : ' client-lead-filter-row-primary--no-widget' ?>">
                <label class="search-field lead-filter-search">
                    <span><?= e(t('filter.search')) ?></span>
                    <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(t('filter.placeholder_leads')) ?>">
                </label>
                <?php if ($hasWidgetFilter): ?>
                    <label class="lead-filter-widget">
                        <span><?= e(t('filter.widget')) ?></span>
                        <select name="widget_id">
                            <option value="0"><?= e(t('filter.all_widgets')) ?></option>
                            <?php foreach ($widgetOptions as $widgetOption): ?>
                                <option value="<?= (int) $widgetOption['id'] ?>"<?= (int) $widgetOption['id'] === $widgetFilterId ? ' selected' : '' ?>>
                                    <?= e($widgetOption['widget_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
            </div>
            <div class="client-lead-filter-row client-lead-filter-row-secondary">
                <label class="lead-filter-from">
                    <span><?= e(t('filter.from_date')) ?></span>
                    <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
                </label>
                <label class="lead-filter-to">
                    <span><?= e(t('filter.to_date')) ?></span>
                    <input type="date" name="date_to" value="<?= e($dateTo) ?>">
                </label>
                <div class="client-lead-toolbar-actions">
                    <div class="lead-toolbar-default" data-lead-toolbar-default>
                        <?php $toolbarDefaultButtons(); ?>
                    </div>
                    <?php if ($showDeleteActions): ?>
                        <div class="lead-toolbar-selected" data-lead-toolbar-selected hidden>
                            <?php $toolbarSelectedButtons(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php else: ?>
        <form
            class="lead-filter-toolbar admin-filter-bar<?= $hasWidgetFilter ? '' : ' lead-filter-toolbar--no-widget' ?>"
            method="get"
            action="<?= e($formAction) ?>"
        >
            <?php if ($clientFilterId > 0 && $leadPageMode === 'superadmin'): ?>
                <input type="hidden" name="client_id" value="<?= (int) $clientFilterId ?>">
            <?php endif; ?>
            <label class="search-field lead-filter-search">
                <span><?= e(t('filter.search')) ?></span>
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(t('filter.placeholder_leads')) ?>">
            </label>
            <?php if ($hasWidgetFilter): ?>
                <label class="lead-filter-widget">
                    <span><?= e(t('filter.widget')) ?></span>
                    <select name="widget_id">
                        <option value="0"><?= e(t('filter.all_widgets')) ?></option>
                        <?php foreach ($widgetOptions as $widgetOption): ?>
                            <option value="<?= (int) $widgetOption['id'] ?>"<?= (int) $widgetOption['id'] === $widgetFilterId ? ' selected' : '' ?>>
                                <?= e($widgetOption['widget_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <div class="lead-date-range">
                <label class="lead-filter-from">
                    <span><?= e(t('filter.from_date')) ?></span>
                    <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
                </label>
                <label class="lead-filter-to">
                    <span><?= e(t('filter.to_date')) ?></span>
                    <input type="date" name="date_to" value="<?= e($dateTo) ?>">
                </label>
            </div>
            <div class="lead-toolbar-actions">
                <div class="lead-toolbar-default" data-lead-toolbar-default>
                    <?php $toolbarDefaultButtons(); ?>
                </div>
                <?php if ($showDeleteActions): ?>
                    <div class="lead-toolbar-selected" data-lead-toolbar-selected hidden>
                        <?php $toolbarSelectedButtons(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>

    <p class="results-meta" data-leads-results-meta><?= e(t($result['total'] === 1 ? 'results.leads_found_one' : 'results.leads_found_other', ['count' => (string) $result['total']])) ?></p>

    <?php if (!$result['rows']): ?>
        <div class="empty-state compact-empty" data-leads-empty-state>
            <p><?= e($emptyMessage) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap lead-table-wrap">
            <table class="<?= e($tableClass) ?>">
                <colgroup>
                    <col class="col-select">
                    <col class="col-lead">
                    <?php if (!$isRecycleBin): ?>
                        <col class="col-source">
                        <?php if ($showClientWidgetColumn): ?>
                            <col class="col-client-widget">
                        <?php else: ?>
                            <col class="col-widget">
                        <?php endif; ?>
                        <col class="col-captured">
                    <?php else: ?>
                        <?php if ($showRecycleMeta): ?><col class="col-client"><?php endif; ?>
                        <col class="col-widget">
                        <col class="col-captured">
                        <?php if ($showRecycleMeta): ?>
                            <col class="col-expires-in">
                        <?php endif; ?>
                    <?php endif; ?>
                    <col class="col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <?php if ($showSelection): ?>
                            <th class="col-select" scope="col">
                                <input
                                    type="checkbox"
                                    data-select-all-leads
                                    aria-label="<?= e(t('lead.select_all_visible')) ?>"
                                    title="<?= e(t('lead.select_all_visible')) ?>"
                                >
                            </th>
                        <?php endif; ?>
                        <th scope="col"><?= e(t('table.lead')) ?></th>
                        <?php if (!$isRecycleBin): ?>
                            <th scope="col"><?= e(t('table.source_page')) ?></th>
                            <?php if ($showClientWidgetColumn): ?>
                                <th scope="col"><?= e(t('table.client_widget')) ?></th>
                            <?php else: ?>
                                <th scope="col"><?= e(t('table.widget')) ?></th>
                            <?php endif; ?>
                            <th scope="col"><?= e(t('table.captured')) ?></th>
                        <?php else: ?>
                            <?php if ($showRecycleMeta): ?><th scope="col"><?= e(t('table.client')) ?></th><?php endif; ?>
                            <th scope="col"><?= e(t('lead.original_widget')) ?></th>
                            <th scope="col"><?= e(t('lead.deleted_at')) ?></th>
                            <?php if ($showRecycleMeta): ?>
                                <th scope="col"><?= e(t('lead.expires_in')) ?></th>
                            <?php endif; ?>
                        <?php endif; ?>
                        <th class="col-actions" scope="col"><?= e(t('table.actions')) ?></th>
                    </tr>
                </thead>
                <tbody data-leads-table-body>
                    <?php foreach ($result['rows'] as $lead): ?>
                        <?php
                        $displayPhone = format_lead_display_phone($lead);
                        $maskedPhone = mask_lead_phone($displayPhone);
                        $copyPhone = $displayPhone !== '' ? $displayPhone : (string) ($lead['visitor_full_phone'] ?? '');
                        $daysRemaining = max(0, (int) ($lead['days_remaining'] ?? 0));
                        ?>
                        <tr data-lead-row data-lead-id="<?= (int) $lead['id'] ?>">
                            <?php if ($showSelection): ?>
                                <td class="col-select" data-label="">
                                    <input type="checkbox" class="ctcw-lead-select" value="<?= (int) $lead['id'] ?>" aria-label="<?= e(t('lead.select_row_aria', ['phone' => $maskedPhone])) ?>">
                                </td>
                            <?php endif; ?>
                            <td class="col-lead" data-label="<?= e(t('table.lead')) ?>">
                                <?php if ($copyPhone !== ''): ?>
                                    <button
                                        type="button"
                                        class="lead-phone-copy"
                                        data-copy-lead-phone
                                        data-phone="<?= e($copyPhone) ?>"
                                        title="<?= e(t('lead.copy_phone')) ?>"
                                    ><?= e($displayPhone !== '' ? $displayPhone : $copyPhone) ?></button>
                                <?php else: ?>
                                    <span class="lead-phone-empty">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!$isRecycleBin): ?>
                                <td class="col-source" data-label="<?= e(t('table.source_page')) ?>">
                                    <?php render_lead_source_cell($lead); ?>
                                </td>
                                <?php if ($showClientWidgetColumn): ?>
                                    <td class="col-client-widget" data-label="<?= e(t('table.client_widget')) ?>">
                                        <span class="lead-client-widget-cell">
                                            <strong><?= e((string) ($lead['owner_name'] ?? '')) ?></strong>
                                            <span><?= e((string) ($lead['widget_name'] ?? '')) ?></span>
                                        </span>
                                    </td>
                                <?php else: ?>
                                    <td class="col-widget" data-label="<?= e(t('table.widget')) ?>">
                                        <span class="lead-widget-name"><?= e((string) ($lead['widget_name'] ?? '')) ?></span>
                                    </td>
                                <?php endif; ?>
                                <td class="col-captured" data-label="<?= e(t('table.captured')) ?>">
                                    <?php render_lead_captured_cell($lead['created_at'] ?? null); ?>
                                </td>
                            <?php else: ?>
                                <?php if ($showRecycleMeta): ?>
                                    <td class="col-client" data-label="<?= e(t('table.client')) ?>">
                                        <span class="lead-client-name"><?= e($lead['owner_name']) ?></span>
                                    </td>
                                <?php endif; ?>
                                <td class="col-widget" data-label="<?= e(t('lead.original_widget')) ?>">
                                    <span class="lead-widget-name"><?= e($lead['widget_name']) ?></span>
                                </td>
                                <td class="col-captured" data-label="<?= e(t('lead.deleted_at')) ?>">
                                    <?php render_lead_captured_cell($lead['deleted_at'] ?? null); ?>
                                </td>
                                <?php if ($showRecycleMeta): ?>
                                    <td class="col-expires-in" data-label="<?= e(t('lead.expires_in')) ?>">
                                        <span class="lead-expires-in"><?= e(format_recycle_bin_expires_label($daysRemaining)) ?></span>
                                    </td>
                                <?php endif; ?>
                            <?php endif; ?>
                            <td class="col-actions" data-label="<?= e(t('table.actions')) ?>">
                                <?php if ($showRestoreActions): ?>
                                    <div class="recycle-bin-actions recycle-bin-actions--menu">
                                        <button type="button" class="btn btn-primary-soft btn-compact btn-lead-action" data-restore-lead data-lead-id="<?= (int) $lead['id'] ?>"><?= e(t('lead.restore')) ?></button>
                                        <div class="action-menu" data-action-menu>
                                            <button
                                                type="button"
                                                class="btn btn-light btn-compact action-menu-toggle"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="<?= e(t('lead.more_actions_for_lead')) ?>"
                                            >⋯</button>
                                            <div class="action-menu-panel" role="menu">
                                                <button
                                                    type="button"
                                                    class="action-menu-danger btn-lead-action"
                                                    role="menuitem"
                                                    data-permanent-delete-lead
                                                    data-lead-id="<?= (int) $lead['id'] ?>"
                                                ><?= e(t('lead.delete_permanently')) ?></button>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-danger-soft btn-compact btn-lead-action recycle-bin-delete-fallback"
                                            data-permanent-delete-lead
                                            data-lead-id="<?= (int) $lead['id'] ?>"
                                        ><?= e(t('lead.delete_permanently')) ?></button>
                                    </div>
                                <?php else: ?>
                                <div class="lead-row-actions">
                                    <?php if ($showDeleteActions): ?>
                                        <?php if ($useSuperadminLeadActions): ?>
                                            <button
                                                type="button"
                                                class="btn btn-light btn-compact btn-lead-action"
                                                data-delete-lead
                                                data-lead-id="<?= (int) $lead['id'] ?>"
                                                data-lead-phone="<?= e($maskedPhone) ?>"
                                                title="<?= e(t('lead.move_to_recycle_bin')) ?>"
                                            >
                                                <span class="lead-action-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M2.5 4.5A1.5 1.5 0 0 1 4 3h12a1.5 1.5 0 0 1 1.415 1.002l-2 6A1.5 1.5 0 0 1 14.001 11H6.236l-.586 2.344A1.5 1.5 0 0 1 4.18 14.5H3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h.68l2-8H4a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h1.5Zm3.736 6 1-4h7.265l1.333 4H6.236Z"/></svg>
                                                </span>
                                                <?= e(t('lead.move')) ?>
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="btn btn-danger-soft btn-compact btn-lead-action"
                                                data-delete-lead
                                                data-lead-id="<?= (int) $lead['id'] ?>"
                                                data-lead-phone="<?= e($maskedPhone) ?>"
                                            ><?= e(t('button.delete')) ?></button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/leads-modals.php'; ?>
    <div class="ctcw-lead-toast" data-lead-toast hidden role="status" aria-live="polite"></div>
</section>
