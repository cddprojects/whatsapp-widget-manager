<div class="ctcw-lead-modal" data-lead-single-modal hidden>
    <div class="ctcw-lead-modal-backdrop" data-lead-modal-close></div>
    <div class="ctcw-lead-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ctcwLeadSingleModalTitle">
        <h3 id="ctcwLeadSingleModalTitle"></h3>
        <p data-lead-single-modal-message></p>
        <div class="form-actions">
            <button type="button" class="btn btn-light" data-lead-modal-cancel><?= e(t('button.cancel')) ?></button>
            <button type="button" class="btn btn-danger-soft" data-lead-single-modal-confirm></button>
        </div>
    </div>
</div>

<div class="ctcw-lead-modal" data-lead-bulk-modal hidden>
    <div class="ctcw-lead-modal-backdrop" data-lead-modal-close></div>
    <div class="ctcw-lead-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ctcwLeadBulkModalTitle">
        <h3 id="ctcwLeadBulkModalTitle"></h3>
        <p data-lead-bulk-modal-message></p>
        <div class="form-actions">
            <button type="button" class="btn btn-light" data-lead-modal-cancel><?= e(t('button.cancel')) ?></button>
            <button type="button" class="btn btn-danger-soft" data-lead-bulk-modal-confirm></button>
        </div>
    </div>
</div>

<div class="ctcw-lead-modal" data-lead-restore-modal hidden>
    <div class="ctcw-lead-modal-backdrop" data-lead-modal-close></div>
    <div class="ctcw-lead-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ctcwLeadRestoreModalTitle">
        <h3 id="ctcwLeadRestoreModalTitle"><?= e(t('lead.restore_title')) ?></h3>
        <p data-lead-restore-modal-message></p>
        <div class="form-actions">
            <button type="button" class="btn btn-light" data-lead-modal-cancel><?= e(t('button.cancel')) ?></button>
            <button type="button" class="btn btn-primary-soft" data-lead-restore-modal-confirm><?= e(t('lead.restore')) ?></button>
        </div>
    </div>
</div>

<div class="ctcw-lead-modal" data-lead-permanent-modal hidden>
    <div class="ctcw-lead-modal-backdrop" data-lead-modal-close></div>
    <div class="ctcw-lead-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ctcwLeadPermanentModalTitle">
        <h3 id="ctcwLeadPermanentModalTitle"><?= e(t('lead.permanent_delete_title')) ?></h3>
        <p data-lead-permanent-modal-message><?= e(t('lead.permanent_delete_body')) ?></p>
        <div class="form-actions">
            <button type="button" class="btn btn-light" data-lead-modal-cancel><?= e(t('button.cancel')) ?></button>
            <button type="button" class="btn btn-danger-soft" data-lead-permanent-modal-confirm><?= e(t('lead.delete_permanently')) ?></button>
        </div>
    </div>
</div>
