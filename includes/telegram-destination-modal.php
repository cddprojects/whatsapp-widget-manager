<?php
declare(strict_types=1);

/**
 * Telegram destination create/edit modal.
 *
 * Expects:
 * - $widgetId (int)
 * - $saveUrl (string) optional
 */
$widgetId = (int) ($widgetId ?? 0);
$saveUrl = (string) ($saveUrl ?? app_url('telegram-destination-save.php'));
?>
<dialog class="ctcw-modal ctcw-telegram-modal" data-telegram-modal aria-labelledby="telegram-destination-modal-title">
    <form
        method="post"
        action="<?= e($saveUrl) ?>"
        class="ctcw-telegram-modal-form"
        data-telegram-destination-form
    >
        <?= csrf_field() ?>
        <input type="hidden" name="widget_id" value="<?= $widgetId ?>">
        <input type="hidden" name="destination_id" value="" data-telegram-destination-id>
        <input type="hidden" name="destination_action" value="save">
        <input type="hidden" name="response" value="json">

        <header class="ctcw-telegram-modal-header">
            <div class="ctcw-telegram-modal-heading">
                <span class="ctcw-telegram-modal-icon" aria-hidden="true"><?= telegram_icon_svg() ?></span>
                <div>
                    <h3 id="telegram-destination-modal-title" data-telegram-modal-title>
                        <?= e(t('telegram.add_destination')) ?>
                    </h3>
                    <p class="ctcw-telegram-modal-help"><?= e(t('telegram.modal_help')) ?></p>
                </div>
            </div>
            <button type="button" class="ctcw-telegram-modal-close" data-close-telegram-modal aria-label="<?= e(t('button.close')) ?>">
                <span aria-hidden="true">&times;</span>
            </button>
        </header>

        <div class="ctcw-telegram-modal-body">
            <?php
            $telegramDestination = [];
            $telegramFormId = 'telegram-destination-form';
            require __DIR__ . '/telegram-destination-form.php';
            ?>
            <p class="ctcw-telegram-modal-status" data-telegram-form-status hidden></p>
        </div>

        <footer class="ctcw-telegram-modal-footer">
            <button type="button" class="btn btn-light" data-close-telegram-modal><?= e(t('button.cancel')) ?></button>
            <button type="submit" class="btn btn-primary ctcw-telegram-save-btn" data-telegram-save-button>
                <?= e(t('button.save')) ?>
            </button>
        </footer>
    </form>
</dialog>
