<?php
declare(strict_types=1);

/** @var string $leadPageMode client|superadmin|recycle_bin */
/** @var array $result */
/** @var string $query */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var int $widgetFilterId */
/** @var int $clientFilterId */
/** @var string $deletedByRoleFilter */
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
$showSelection = true;
$showDeleteActions = !$isRecycleBin;
$showRestoreActions = $isRecycleBin;
?>

<section
    class="settings-card"
    data-leads-page
    data-leads-mode="<?= e($leadPageMode) ?>"
    data-csrf-token="<?= e($csrfToken) ?>"
    data-total-leads="<?= (int) $result['total'] ?>"
    data-empty-message="<?= e($emptyMessage) ?>"
>
    <form class="admin-filter-bar" method="get" action="<?= e($formAction) ?>">
        <?php if ($clientFilterId > 0 && $leadPageMode === 'superadmin'): ?>
            <input type="hidden" name="client_id" value="<?= (int) $clientFilterId ?>">
        <?php endif; ?>
        <label class="search-field span-2">
            <span><?= e(t('filter.search')) ?></span>
            <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(t('filter.placeholder_leads')) ?>">
        </label>
        <?php if ($showClientColumn && $clientOptions !== []): ?>
            <label>
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
        <?php if ($widgetOptions !== []): ?>
            <label>
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
        <?php if ($isRecycleBin): ?>
            <label>
                <span><?= e(t('lead.deleted_by')) ?></span>
                <select name="deleted_by_role">
                    <option value=""><?= e(t('filter.all')) ?></option>
                    <option value="client"<?= $deletedByRoleFilter === 'client' ? ' selected' : '' ?>><?= e(t('lead.deleted_by_client')) ?></option>
                    <option value="superadmin"<?= $deletedByRoleFilter === 'superadmin' ? ' selected' : '' ?>><?= e(t('lead.deleted_by_superadmin')) ?></option>
                </select>
            </label>
        <?php endif; ?>
        <label>
            <span><?= e(t('filter.from_date')) ?></span>
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
        </label>
        <label>
            <span><?= e(t('filter.to_date')) ?></span>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>">
        </label>
        <div class="form-actions lead-filter-actions">
            <button type="submit" class="btn btn-primary"><?= e(t('button.filter')) ?></button>
            <?php if (!$isRecycleBin && $result['total'] > 0): ?>
                <a class="btn btn-light" href="<?= e($exportUrl) ?>"><?= e(t('button.export_csv')) ?></a>
            <?php elseif (!$isRecycleBin): ?>
                <span class="btn btn-light is-disabled" aria-disabled="true"><?= e(t('button.export_csv')) ?></span>
            <?php endif; ?>
            <?php if ($showDeleteActions): ?>
                <button type="button" class="btn btn-danger-soft" data-delete-selected-leads disabled><?= e($deleteBulkLabel) ?></button>
                <span class="lead-selected-count" data-selected-lead-count hidden><?= e(t('lead.selected_count', ['count' => '0'])) ?></span>
            <?php endif; ?>
            <?php if ($showRestoreActions): ?>
                <button type="button" class="btn btn-primary-soft" data-restore-selected-leads disabled><?= e(t('lead.restore_selected')) ?></button>
                <button type="button" class="btn btn-danger-soft" data-permanent-delete-selected-leads disabled><?= e(t('lead.permanent_delete_selected')) ?></button>
                <span class="lead-selected-count" data-selected-lead-count hidden><?= e(t('lead.selected_count', ['count' => '0'])) ?></span>
            <?php endif; ?>
        </div>
    </form>

    <p class="results-meta" data-leads-results-meta><?= e(t($result['total'] === 1 ? 'results.leads_found_one' : 'results.leads_found_other', ['count' => (string) $result['total']])) ?></p>

    <?php if (!$result['rows']): ?>
        <div class="empty-state compact-empty" data-leads-empty-state>
            <p><?= e($emptyMessage) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table lead-table">
                <thead>
                    <tr>
                        <?php if ($showSelection): ?>
                            <th class="col-select">
                                <label class="lead-select-all-label">
                                    <input type="checkbox" data-select-all-leads aria-label="<?= e(t('table.select_all_aria')) ?>">
                                    <span><?= e(t('table.select_all')) ?></span>
                                </label>
                            </th>
                        <?php endif; ?>
                        <th><?= e(t('table.visitor_phone')) ?></th>
                        <?php if ($showClientOwnerColumn): ?><th><?= e(t('table.client_owner')) ?></th><?php endif; ?>
                        <?php if ($showRecycleMeta): ?><th><?= e(t('table.client')) ?></th><?php endif; ?>
                        <th><?= e($isRecycleBin ? t('lead.original_widget') : t('table.widget')) ?></th>
                        <?php if (!$isRecycleBin): ?>
                            <th><?= e(t('table.source_domain')) ?></th>
                            <th><?= e(t('table.source_url')) ?></th>
                            <th><?= e(t('table.page_title')) ?></th>
                        <?php endif; ?>
                        <th><?= e($isRecycleBin ? t('lead.deleted_at') : t('table.created')) ?></th>
                        <?php if ($showRecycleMeta): ?>
                            <th><?= e(t('lead.deleted_by')) ?></th>
                            <th><?= e(t('lead.days_remaining')) ?></th>
                        <?php endif; ?>
                        <th class="col-actions"><?= e(t('table.actions')) ?></th>
                    </tr>
                </thead>
                <tbody data-leads-table-body>
                    <?php foreach ($result['rows'] as $lead): ?>
                        <?php $maskedPhone = mask_lead_phone((string) ($lead['visitor_phone'] ?: $lead['visitor_full_phone'])); ?>
                        <tr data-lead-row data-lead-id="<?= (int) $lead['id'] ?>">
                            <?php if ($showSelection): ?>
                                <td class="col-select">
                                    <input type="checkbox" class="ctcw-lead-select" value="<?= (int) $lead['id'] ?>" aria-label="<?= e(t('lead.select_row_aria', ['phone' => $maskedPhone])) ?>">
                                </td>
                            <?php endif; ?>
                            <td><strong><?= e($lead['visitor_phone']) ?></strong><small><?= e($lead['visitor_full_phone']) ?></small></td>
                            <?php if ($showClientOwnerColumn): ?>
                                <td><strong><?= e($lead['owner_name']) ?></strong><small><?= e($lead['owner_email']) ?></small></td>
                            <?php endif; ?>
                            <?php if ($showRecycleMeta): ?>
                                <td><strong><?= e($lead['owner_name']) ?></strong><small><?= e($lead['owner_email']) ?></small></td>
                            <?php endif; ?>
                            <td><?= e($lead['widget_name']) ?></td>
                            <?php if (!$isRecycleBin): ?>
                                <td><?= e((string) ($lead['source_domain'] ?? '')) ?></td>
                                <td><small><?= e((string) ($lead['source_url'] ?? '')) ?></small></td>
                                <td><?= e((string) ($lead['page_title'] ?? '')) ?></td>
                                <td><?= e(format_datetime($lead['created_at'] ?? null, '')) ?></td>
                            <?php else: ?>
                                <td><?= e(format_datetime($lead['deleted_at'] ?? null, '')) ?></td>
                                <td><?= e(translate_deleted_by_role((string) ($lead['deleted_by_role'] ?? ''))) ?><?php if (!empty($lead['deleted_by_name'])): ?><small><?= e($lead['deleted_by_name']) ?></small><?php endif; ?></td>
                                <td><?= e((string) ($lead['days_remaining'] ?? '0')) ?></td>
                            <?php endif; ?>
                            <td class="col-actions">
                                <?php if ($showDeleteActions): ?>
                                    <button type="button" class="btn btn-danger-soft btn-compact" data-delete-lead data-lead-id="<?= (int) $lead['id'] ?>" data-lead-phone="<?= e($maskedPhone) ?>"><?= e($deleteSingleLabel) ?></button>
                                <?php endif; ?>
                                <?php if ($showRestoreActions): ?>
                                    <button type="button" class="btn btn-primary-soft btn-compact" data-restore-lead data-lead-id="<?= (int) $lead['id'] ?>"><?= e(t('lead.restore')) ?></button>
                                    <button type="button" class="btn btn-danger-soft btn-compact" data-permanent-delete-lead data-lead-id="<?= (int) $lead['id'] ?>"><?= e(t('lead.delete_permanently')) ?></button>
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
