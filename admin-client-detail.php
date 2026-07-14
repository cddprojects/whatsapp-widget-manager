<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api-credentials.php';
require_superadmin();

$clientId = (int) ($_GET['id'] ?? 0);
$client = find_client_user($clientId);
if (!$client) {
    http_response_code(404);
    exit(t('error.client_not_found'));
}

if (is_post() && ($_POST['action'] ?? '') === 'delete_widget') {
    verify_csrf();
    $widgetId = (int) ($_POST['widget_id'] ?? 0);
    $widget = find_widget_by_id($widgetId);
    if ($widget && (int) $widget['user_id'] === $clientId) {
        $stmt = db()->prepare('DELETE FROM widgets WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $widgetId, 'user_id' => $clientId]);
        flash('success', t('flash.widget_deleted'));
    }
    redirect('admin-client-detail.php?id=' . $clientId);
}

if (is_post() && ($_POST['action'] ?? '') === 'toggle_status') {
    verify_csrf();
    if ((string) $client['status'] === USER_STATUS_ACTIVE) {
        $stmt = db()->prepare('UPDATE users SET status = :status WHERE id = :id AND role = :role');
        $stmt->execute(['status' => USER_STATUS_DISABLED, 'id' => $clientId, 'role' => ROLE_CLIENT]);
        flash('success', t('flash.client_disabled'));
    } else {
        $stmt = db()->prepare('UPDATE users SET status = :status WHERE id = :id AND role = :role');
        $stmt->execute(['status' => USER_STATUS_ACTIVE, 'id' => $clientId, 'role' => ROLE_CLIENT]);
        flash('success', t('flash.client_enabled'));
    }
    redirect('admin-client-detail.php?id=' . $clientId);
}

$widgets = widgets_for_user($clientId);
$widgetCount = count($widgets);
$createdPassword = $_SESSION['created_client_password'] ?? null;
$createdClientEmail = $_SESSION['created_client_email'] ?? null;
unset($_SESSION['created_client_password'], $_SESSION['created_client_email']);

$clientApiCredential = find_api_credential(API_CREDENTIAL_OWNER_CLIENT, $clientId, API_CREDENTIAL_TYPE_CLIENT);
$clientApiView = api_credential_public_view($clientApiCredential);

$widgetApiViews = [];
foreach ($widgets as $widgetRow) {
    $widgetId = (int) $widgetRow['id'];
    $widgetApiViews[$widgetId] = api_credential_public_view(
        find_api_credential(API_CREDENTIAL_OWNER_WIDGET, $widgetId, API_CREDENTIAL_TYPE_WIDGET)
    );
}

$pageTitle = t('page.client_profile');
$pageScripts = ['assets/js/api-credentials.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-heading page-heading-row">
    <div>
        <p class="eyebrow"><?= e(t('eyebrow.client_profile')) ?></p>
        <h1><?= e($client['name']) ?></h1>
        <p><?= e(t('desc.client_profile')) ?></p>
    </div>
    <a class="btn btn-primary" href="<?= e(app_url('create-widget.php', ['user_id' => (int) $client['id']])) ?>"><?= e(t('button.create_widget_for_client')) ?></a>
    <a class="btn btn-light" href="<?= e(app_url('admin-client-leads.php', ['client_id' => (int) $client['id']])) ?>"><?= e(t('button.view_leads')) ?></a>
</section>

<?php if ($createdPassword !== null): ?>
    <div class="alert alert-success">
        <?= e($createdClientEmail
            ? t('alert.client_created_for', ['email' => (string) $createdClientEmail])
            : t('alert.client_created')) ?>
    </div>
    <div class="alert alert-warning">
        <?= e(t('alert.copy_password_now')) ?>
    </div>
    <section class="settings-card">
        <div class="temp-password-box">
            <span class="meta-label"><?= e(t('meta.password')) ?></span>
            <code><?= e((string) $createdPassword) ?></code>
        </div>
    </section>
<?php endif; ?>

<section class="settings-card profile-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('heading.client_profile')) ?></h2>
            <p><?= e(t('desc.client_profile_overview')) ?></p>
        </div>
        <div class="action-list">
            <div class="action-menu client-settings-menu" data-action-menu>
                <button type="button" class="btn btn-light action-menu-toggle" aria-haspopup="true" aria-expanded="false">
                    <?= e(t('button.client_settings')) ?> <span class="user-menu-caret" aria-hidden="true">▾</span>
                </button>
                <div class="action-menu-panel" role="menu">
                    <a role="menuitem" href="<?= e(app_url('admin-client-edit.php', ['id' => (int) $client['id']])) ?>"><?= e(t('button.edit_client')) ?></a>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <button type="submit" class="action-menu-button" role="menuitem">
                            <?= e((string) $client['status'] === USER_STATUS_ACTIVE ? t('button.disable_client') : t('button.enable_client')) ?>
                        </button>
                    </form>
                    <a role="menuitem" href="<?= e(app_url('admin-client-reset-password.php', ['id' => (int) $client['id']])) ?>"><?= e(t('button.reset_password')) ?></a>
                    <div class="action-menu-divider" role="separator"></div>
                    <button
                        type="button"
                        role="menuitem"
                        class="action-menu-button"
                        data-open-api-key-modal
                        data-owner-type="client"
                        data-owner-id="<?= (int) $client['id'] ?>"
                        data-owner-label="<?= e((string) $client['name']) ?>"
                        data-client-label="<?= e((string) $client['name']) ?>"
                    ><?= e(t('action.client_api_key')) ?></button>
                </div>
            </div>
        </div>
    </div>
    <div class="profile-grid">
        <div><span class="meta-label"><?= e(t('meta.email')) ?></span><strong><?= e($client['email']) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.status')) ?></span><span class="<?= e(user_status_badge_class((string) $client['status'])) ?>"><?= e(translate_user_status((string) $client['status'])) ?></span></div>
        <div><span class="meta-label"><?= e(t('meta.created')) ?></span><strong><?= e(date('M j, Y', strtotime((string) $client['created_at']))) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.last_login')) ?></span><strong><?= e(format_datetime($client['last_login_at'] ?? null)) ?></strong></div>
        <div><span class="meta-label"><?= e(t('meta.total_widgets')) ?></span><strong><?= (int) $widgetCount ?></strong></div>
    </div>
</section>

<section class="settings-card table-card">
    <div class="card-header-row">
        <div>
            <h2><?= e(t('heading.client_widgets')) ?></h2>
            <p><?= e(t('desc.client_widgets')) ?></p>
        </div>
    </div>

    <?php if (!$widgets): ?>
        <div class="empty-state compact-empty">
            <p><?= e(t('empty.client_no_widgets')) ?></p>
            <a class="btn btn-primary" href="<?= e(app_url('create-widget.php', ['user_id' => (int) $client['id']])) ?>"><?= e(t('button.create_widget_for_client')) ?></a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="widget-table">
                <thead>
                    <tr>
                        <th><?= e(t('table.widget_name')) ?></th>
                        <th><?= e(t('table.domain')) ?></th>
                        <th><?= e(t('table.whatsapp_number')) ?></th>
                        <th><?= e(t('table.widget_status')) ?></th>
                        <th><?= e(t('table.random_numbers')) ?></th>
                        <th><?= e(t('table.global_display')) ?></th>
                        <th><?= e(t('table.updated')) ?></th>
                        <th class="col-actions"><?= e(t('table.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($widgets as $widget): ?>
                        <tr>
                            <td><strong><?= e($widget['widget_name']) ?></strong></td>
                            <td><?= e($widget['website_domain']) ?></td>
                            <td><?= format_whatsapp_display($widget) ?></td>
                            <td><?php render_widget_activation_status($widget, true); ?></td>
                            <td><?= feature_status_pill($widget['use_random_numbers'] ?? 0) ?></td>
                            <td><?= feature_status_pill($widget['show_global'] ?? 0) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $widget['updated_at']))) ?></td>
                            <td class="col-actions">
                                <?php render_widget_action_menu($widget, [
                                    'show_delete' => true,
                                    'delete_client_id' => $clientId,
                                    'show_api_key' => true,
                                    'client_name' => (string) $client['name'],
                                ]); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="settings-card danger-zone-card">
    <h2><?= e(t('heading.danger_zone')) ?></h2>
    <p class="danger-zone-copy"><?= e(t('desc.danger_zone')) ?></p>
    <a class="btn btn-danger-soft" href="<?= e(app_url('admin-client-delete.php', ['id' => (int) $client['id']])) ?>"><?= e(t('button.delete_client')) ?></a>
</section>

<div class="form-actions">
    <a class="btn btn-light" href="<?= e(app_url('admin-clients.php')) ?>"><?= e(t('button.back_to_clients')) ?></a>
</div>

<div
    id="api-key-modal"
    class="ctcw-api-key-modal"
    hidden
    data-manage-url="<?= e(app_url('api-credentials-manage.php')) ?>"
    data-csrf-token="<?= e(csrf_token()) ?>"
    data-crypto-ready="<?= api_credentials_crypto_ready() ? '1' : '0' ?>"
>
    <div class="ctcw-api-key-modal-backdrop" data-api-key-modal-close></div>
    <div class="ctcw-api-key-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="api-key-modal-title">
        <div class="ctcw-api-key-modal-header">
            <h2 id="api-key-modal-title"><?= e(t('api_key.modal_title_client')) ?></h2>
            <button type="button" class="ctcw-api-key-modal-close" data-api-key-modal-close aria-label="<?= e(t('button.cancel')) ?>">×</button>
        </div>
        <div class="ctcw-api-key-modal-body">
            <p class="ctcw-api-key-meta" data-api-key-context></p>
            <div class="ctcw-api-key-empty" data-api-key-empty hidden>
                <p><?= e(t('api_key.none_generated')) ?></p>
                <button type="button" class="btn btn-primary" data-api-key-generate><?= e(t('api_key.generate')) ?></button>
            </div>
            <div class="ctcw-api-key-details" data-api-key-details hidden>
                <div class="ctcw-api-key-grid">
                    <div>
                        <span class="meta-label"><?= e(t('meta.status')) ?></span>
                        <strong><span class="status-pill status-active" data-api-key-status-pill><?= e(t('api_key.status_active')) ?></span></strong>
                    </div>
                    <div>
                        <span class="meta-label"><?= e(t('api_key.key_label')) ?></span>
                        <code data-api-key-masked></code>
                    </div>
                    <div>
                        <span class="meta-label"><?= e(t('meta.created')) ?></span>
                        <strong data-api-key-created></strong>
                    </div>
                    <div>
                        <span class="meta-label"><?= e(t('api_key.last_used')) ?></span>
                        <strong data-api-key-last-used></strong>
                    </div>
                </div>
                <div class="ctcw-api-key-actions">
                    <button type="button" class="btn btn-primary" data-api-key-copy><?= e(t('api_key.copy')) ?></button>
                    <button type="button" class="btn btn-light" data-api-key-regenerate><?= e(t('api_key.regenerate')) ?></button>
                    <button type="button" class="btn btn-light" data-api-key-toggle><?= e(t('api_key.disable')) ?></button>
                </div>
                <p class="ctcw-api-key-feedback" data-api-key-feedback hidden></p>
            </div>
            <p class="ctcw-api-key-error" data-api-key-error hidden></p>
        </div>
    </div>
</div>

<script type="application/json" id="api-key-bootstrap"><?= json_for_html([
    'client' => array_merge($clientApiView, [
        'created_label' => format_api_credential_datetime($clientApiView['created_at'] ?? null),
        'last_used_label' => format_api_credential_datetime($clientApiView['last_used_at'] ?? null),
        'status_label' => !empty($clientApiView['is_active']) ? t('api_key.status_active') : t('api_key.status_disabled'),
    ]),
    'widgets' => array_map(static function (array $view): array {
        return array_merge($view, [
            'created_label' => format_api_credential_datetime($view['created_at'] ?? null),
            'last_used_label' => format_api_credential_datetime($view['last_used_at'] ?? null),
            'status_label' => !empty($view['is_active']) ? t('api_key.status_active') : t('api_key.status_disabled'),
        ]);
    }, $widgetApiViews),
    'i18n' => [
        'client_title' => t('api_key.modal_title_client'),
        'widget_title' => t('api_key.modal_title_widget'),
        'client_label' => t('api_key.client_label'),
        'widget_label' => t('api_key.widget_label'),
        'none_client' => t('api_key.none_generated_client'),
        'none_widget' => t('api_key.none_generated_widget'),
        'generate_client' => t('api_key.generate_client'),
        'generate_widget' => t('api_key.generate_widget'),
        'copy' => t('api_key.copy'),
        'copied' => t('api_key.copied'),
        'regenerate' => t('api_key.regenerate'),
        'disable' => t('api_key.disable'),
        'enable' => t('api_key.enable'),
        'status_active' => t('api_key.status_active'),
        'status_disabled' => t('api_key.status_disabled'),
        'regenerate_confirm' => t('api_key.regenerate_confirm'),
        'disable_confirm' => t('api_key.disable_confirm'),
        'crypto_missing' => t('api_key.crypto_missing'),
        'copy_failed' => t('api_key.copy_failed'),
        'operation_failed' => t('api_key.operation_failed'),
    ],
]) ?></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
