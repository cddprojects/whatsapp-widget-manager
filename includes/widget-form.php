<?php
$widget = array_merge(default_widget_data(), $widget ?? []);
$phoneNumbers = widget_phone_list($widget);
$businessHours = json_decode((string) ($widget['business_hours_json'] ?? ''), true);
if (!is_array($businessHours)) {
    $businessHours = default_business_hours();
}
$embed = !empty($widget['id']) && !empty($widget['public_key']) ? embed_code($widget) : '';
$settingsSections = [
    ['id' => 'whatsapp-number', 'number' => '1', 'label' => t('section.whatsapp_number.title')],
    ['id' => 'prefilled-message', 'number' => '2', 'label' => t('section.prefilled_message.title')],
    ['id' => 'call-to-action', 'number' => '3', 'label' => t('section.call_to_action.title')],
    ['id' => 'style-position', 'number' => '4', 'label' => t('section.style_position.title')],
    ['id' => 'url-structure', 'number' => '5', 'label' => t('section.url_structure.title')],
    ['id' => 'display-settings', 'number' => '6', 'label' => t('section.display_settings.title')],
    ['id' => 'business-hours', 'number' => '7', 'label' => t('section.business_hours.title')],
    ['id' => 'greeting-dialog', 'number' => '8', 'label' => t('section.greeting_dialog.title')],
    ['id' => 'domain-embed', 'number' => '9', 'label' => t('section.domain_embed.title')],
    ['id' => 'custom-code', 'number' => '10', 'label' => t('section.custom_code.title')],
];
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong><?= e(t('form.fix_errors')) ?></strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="settings-form" data-widget-form>
    <?= csrf_field() ?>

    <?php if (!empty($showOwnerPicker)): ?>
        <section class="settings-card">
            <div class="section-title">
                <span>+</span>
                <div>
                    <h2><?= e(t('section.client_assignment.title')) ?></h2>
                    <p><?= e(t('section.client_assignment.description')) ?></p>
                </div>
            </div>
            <label>
                <span><?= e(t('label.assign_to_client')) ?></span>
                <select name="owner_user_id" required>
                    <option value=""><?= e(t('label.select_client')) ?></option>
                    <?php
                    $clientOptions = db()->query("SELECT id, name, email FROM users WHERE role = '" . ROLE_CLIENT . "' ORDER BY name ASC")->fetchAll();
                    foreach ($clientOptions as $clientOption):
                    ?>
                        <option value="<?= (int) $clientOption['id'] ?>"><?= e($clientOption['name']) ?> — <?= e($clientOption['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </section>
    <?php endif; ?>

    <div class="form-actions sticky-actions">
        <div class="ctcw-save-bar<?= empty($showLivePreview) ? ' ctcw-save-bar--compact' : '' ?>">
            <?php if (!empty($showLivePreview)): ?>
                <div class="ctcw-save-note">
                    <?= e(t('save_bar.preview_note')) ?>
                </div>
            <?php endif; ?>

            <div class="ctcw-save-actions">
                <?php if (!empty($showLivePreview)): ?>
                    <label class="ctcw-preview-toggle" for="ctcwEnableLivePreview">
                        <input type="checkbox" id="ctcwEnableLivePreview" data-live-preview-toggle data-role="admin-live-preview-toggle" checked>
                        <span class="ctcw-toggle-ui" aria-hidden="true"></span>
                        <span class="ctcw-toggle-text"><?= e(t('save_bar.enable_live_preview')) ?></span>
                    </label>
                <?php endif; ?>
                <a class="btn btn-light" href="dashboard.php"><?= e(t('button.back')) ?></a>
                <button type="submit" class="btn btn-primary"><?= e(t('button.save_widget')) ?></button>
            </div>
        </div>
    </div>

    <div class="settings-workspace" data-settings-workspace>
        <aside class="settings-sidebar" aria-label="<?= e(t('widget_setup.title')) ?>">
            <div class="settings-sidebar-card">
                <strong><?= e(t('widget_setup.title')) ?></strong>
                <p><?= e(t('widget_setup.description')) ?></p>
                <nav class="settings-section-nav">
                    <?php foreach ($settingsSections as $index => $section): ?>
                        <button
                            type="button"
                            class="settings-nav-item<?= $index === 0 ? ' is-active' : '' ?>"
                            data-section-target="<?= e($section['id']) ?>">
                            <span><?= e($section['number']) ?></span>
                            <?= e($section['label']) ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <div class="settings-panels">
    <section class="settings-card is-active" data-settings-panel="whatsapp-number">
        <div class="section-title">
            <span>1</span>
            <div>
                <h2><?= e(t('section.whatsapp_number.title')) ?></h2>
                <p><?= e(t('section.whatsapp_number.description')) ?></p>
            </div>
        </div>
        <label class="widget-name-field">
            <span><?= e(t('label.widget_name')) ?></span>
            <input type="text" name="widget_name" value="<?= e($widget['widget_name']) ?>">
        </label>

        <?php
        require __DIR__ . '/phone-number-list.php';
        $destinationNumbers = widget_phone_list($widget);
        $destinationCount = count($destinationNumbers);
        $destinationMethod = effective_destination_selection_method($widget, $destinationCount);
        ?>
        <div class="ctcw-destination-distribution" data-destination-distribution-panel<?= $destinationCount < 2 ? ' hidden' : '' ?>>
            <label>
                <span><?= e(t('distribution.label')) ?></span>
                <select name="destination_selection_method" data-destination-selection-method>
                    <option value="round_robin"<?= selected($destinationMethod, 'round_robin') ?>><?= e(t('distribution.option_round_robin')) ?></option>
                    <option value="random"<?= selected($destinationMethod, 'random') ?>><?= e(t('distribution.option_random')) ?></option>
                </select>
            </label>
            <p class="helper-text" data-destination-method-help="round_robin"<?= $destinationMethod === 'round_robin' ? '' : ' hidden' ?>><?= e(t('distribution.help_round_robin')) ?></p>
            <p class="helper-text" data-destination-method-help="random"<?= $destinationMethod === 'random' ? '' : ' hidden' ?>><?= e(t('distribution.help_random')) ?></p>
            <p class="ctcw-destination-summary" data-destination-summary-text><?= e(destination_distribution_label($widget, $destinationCount)) ?></p>
        </div>
        <p class="ctcw-destination-single-summary" data-destination-single-summary<?= $destinationCount === 1 ? '' : ' hidden' ?>><?= e(t('distribution.one_number')) ?></p>
    </section>

    <section class="settings-card" data-settings-panel="prefilled-message">
        <div class="section-title">
            <span>2</span>
            <div>
                <h2><?= e(t('section.prefilled_message.title')) ?></h2>
                <p><?= e(t('section.prefilled_message.description')) ?></p>
            </div>
        </div>
        <label>
            <span><?= e(t('label.message')) ?></span>
            <textarea name="prefilled_message" rows="5"><?= e($widget['prefilled_message']) ?></textarea>
        </label>
        <div class="helper-chips">
            <code>{site}</code>
            <code>{title}</code>
            <code>{url}</code>
            <code>{url_full}</code>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="call-to-action">
        <div class="section-title">
            <span>3</span>
            <div>
                <h2><?= e(t('section.call_to_action.title')) ?></h2>
                <p><?= e(t('section.call_to_action.description')) ?></p>
            </div>
        </div>
        <label>
            <span><?= e(t('label.button_text')) ?></span>
            <input type="text" name="call_to_action" value="<?= e($widget['call_to_action']) ?>" placeholder="<?= e(t('placeholder.call_to_action')) ?>">
        </label>
    </section>

    <section class="settings-card" data-settings-panel="style-position">
        <div class="section-title">
            <span>4</span>
            <div>
                <h2><?= e(t('section.style_position.title')) ?></h2>
                <p><?= e(t('section.style_position.description')) ?></p>
            </div>
        </div>
        <div class="form-grid two-columns">
            <label>
                <span><?= e(t('label.desktop_style')) ?></span>
                <select name="desktop_style" data-style-select>
                    <?php foreach (widget_styles() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= selected((string) $widget['desktop_style'], $key) ?>><?= e(translate_widget_style($key)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?= e(t('label.mobile_style')) ?></span>
                <select name="mobile_style" data-style-select>
                    <?php foreach (widget_styles() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= selected((string) $widget['mobile_style'], $key) ?>><?= e(translate_widget_style($key)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="style-preview-grid">
            <?php foreach (widget_styles() as $key => $label): ?>
                <div class="style-preview" data-style-preview-card="<?= e($key) ?>">
                    <div class="mini-widget ctcw-widget <?= e($key) ?>">
                        <span class="ctcw-icon"><?= whatsapp_icon_svg() ?></span>
                        <span class="ctcw-text"><?= e(t('preview.default_cta')) ?></span>
                    </div>
                    <small><?= e(translate_widget_style($key)) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="position-grid">
            <div>
                <h3><?= e(t('subheading.desktop_position')) ?></h3>
                <div class="form-grid two-columns">
                    <label>
                        <span><?= e(t('label.position_type')) ?></span>
                        <select name="desktop_position_type">
                            <option value="fixed"<?= selected((string) $widget['desktop_position_type'], 'fixed') ?>><?= e(t('option.fixed')) ?></option>
                            <option value="absolute"<?= selected((string) $widget['desktop_position_type'], 'absolute') ?>><?= e(t('option.absolute')) ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?= e(t('label.vertical_side')) ?></span>
                        <select name="desktop_vertical_position_type">
                            <option value="bottom"<?= selected((string) $widget['desktop_vertical_position_type'], 'bottom') ?>><?= e(t('option.bottom')) ?></option>
                            <option value="top"<?= selected((string) $widget['desktop_vertical_position_type'], 'top') ?>><?= e(t('option.top')) ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?= e(t('label.vertical_value')) ?></span>
                        <input type="text" name="desktop_vertical_position_value" value="<?= e($widget['desktop_vertical_position_value']) ?>" placeholder="<?= e(t('placeholder.position_value')) ?>">
                    </label>
                    <label>
                        <span><?= e(t('label.horizontal_side')) ?></span>
                        <select name="desktop_horizontal_position_type">
                            <option value="right"<?= selected((string) $widget['desktop_horizontal_position_type'], 'right') ?>><?= e(t('option.right')) ?></option>
                            <option value="left"<?= selected((string) $widget['desktop_horizontal_position_type'], 'left') ?>><?= e(t('option.left')) ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?= e(t('label.horizontal_value')) ?></span>
                        <input type="text" name="desktop_horizontal_position_value" value="<?= e($widget['desktop_horizontal_position_value']) ?>" placeholder="<?= e(t('placeholder.position_value')) ?>">
                    </label>
                </div>
            </div>
            <div>
                <h3><?= e(t('subheading.mobile_position')) ?></h3>
                <label class="toggle-row">
                    <input type="checkbox" name="same_mobile_desktop_settings" value="1"<?= checked($widget['same_mobile_desktop_settings']) ?> data-same-mobile>
                    <span><?= e(t('toggle.use_same_as_desktop')) ?></span>
                </label>
                <div class="form-grid two-columns" data-mobile-position-fields>
                    <label>
                        <span><?= e(t('label.position_type')) ?></span>
                        <select name="mobile_position_type">
                            <option value="fixed"<?= selected((string) $widget['mobile_position_type'], 'fixed') ?>><?= e(t('option.fixed')) ?></option>
                            <option value="absolute"<?= selected((string) $widget['mobile_position_type'], 'absolute') ?>><?= e(t('option.absolute')) ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?= e(t('label.vertical_side')) ?></span>
                        <select name="mobile_vertical_position_type">
                            <option value="bottom"<?= selected((string) $widget['mobile_vertical_position_type'], 'bottom') ?>><?= e(t('option.bottom')) ?></option>
                            <option value="top"<?= selected((string) $widget['mobile_vertical_position_type'], 'top') ?>><?= e(t('option.top')) ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?= e(t('label.vertical_value')) ?></span>
                        <input type="text" name="mobile_vertical_position_value" value="<?= e($widget['mobile_vertical_position_value']) ?>" placeholder="<?= e(t('placeholder.position_value')) ?>">
                    </label>
                    <label>
                        <span><?= e(t('label.horizontal_side')) ?></span>
                        <select name="mobile_horizontal_position_type">
                            <option value="right"<?= selected((string) $widget['mobile_horizontal_position_type'], 'right') ?>><?= e(t('option.right')) ?></option>
                            <option value="left"<?= selected((string) $widget['mobile_horizontal_position_type'], 'left') ?>><?= e(t('option.left')) ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?= e(t('label.horizontal_value')) ?></span>
                        <input type="text" name="mobile_horizontal_position_value" value="<?= e($widget['mobile_horizontal_position_value']) ?>" placeholder="<?= e(t('placeholder.position_value')) ?>">
                    </label>
                </div>
            </div>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="url-structure">
        <div class="section-title">
            <span>5</span>
            <div>
                <h2><?= e(t('section.url_structure.title')) ?></h2>
                <p><?= e(t('section.url_structure.description')) ?></p>
            </div>
        </div>
        <div class="form-grid two-columns">
            <label>
                <span><?= e(t('label.desktop_open_link_in')) ?></span>
                <select name="desktop_open_link_type">
                    <option value="new_tab"<?= selected((string) $widget['desktop_open_link_type'], 'new_tab') ?>><?= e(t('option.open_new_tab')) ?></option>
                    <option value="same_tab"<?= selected((string) $widget['desktop_open_link_type'], 'same_tab') ?>><?= e(t('option.redirect_current_page')) ?></option>
                </select>
            </label>
            <label>
                <span><?= e(t('label.desktop_url_structure')) ?></span>
                <select name="desktop_url_structure">
                    <option value="wa_me"<?= selected((string) $widget['desktop_url_structure'], 'wa_me') ?>><?= e(t('option.wa_me')) ?></option>
                    <option value="web_whatsapp"<?= selected((string) $widget['desktop_url_structure'], 'web_whatsapp') ?>><?= e(t('option.web_whatsapp')) ?></option>
                    <option value="whatsapp_app"<?= selected((string) $widget['desktop_url_structure'], 'whatsapp_app') ?>><?= e(t('option.whatsapp_app')) ?></option>
                    <option value="custom"<?= selected((string) $widget['desktop_url_structure'], 'custom') ?>><?= e(t('option.custom_url')) ?></option>
                </select>
            </label>
            <label>
                <span><?= e(t('label.mobile_open_link_in')) ?></span>
                <select name="mobile_open_link_type">
                    <option value="new_tab"<?= selected((string) $widget['mobile_open_link_type'], 'new_tab') ?>><?= e(t('option.open_new_tab')) ?></option>
                    <option value="same_tab"<?= selected((string) $widget['mobile_open_link_type'], 'same_tab') ?>><?= e(t('option.redirect_current_page')) ?></option>
                </select>
            </label>
            <label>
                <span><?= e(t('label.mobile_url_structure')) ?></span>
                <select name="mobile_url_structure">
                    <option value="wa_me"<?= selected((string) $widget['mobile_url_structure'], 'wa_me') ?>><?= e(t('option.wa_me')) ?></option>
                    <option value="whatsapp_app"<?= selected((string) $widget['mobile_url_structure'], 'whatsapp_app') ?>><?= e(t('option.whatsapp_app')) ?></option>
                    <option value="custom"<?= selected((string) $widget['mobile_url_structure'], 'custom') ?>><?= e(t('option.custom_url')) ?></option>
                </select>
            </label>
            <label class="span-2">
                <span><?= e(t('label.custom_url')) ?></span>
                <input type="url" name="custom_url" value="<?= e($widget['custom_url']) ?>" placeholder="<?= e(t('placeholder.custom_url')) ?>">
                <small><?= e(t('helper.custom_url_text_param')) ?></small>
            </label>
        </div>
        <div class="notice-box compact"><?= e(t('alert.url_structure_notice')) ?></div>
    </section>

    <section class="settings-card" data-settings-panel="display-settings">
        <div class="section-title">
            <span>6</span>
            <div>
                <h2><?= e(t('section.display_settings.title')) ?></h2>
                <p><?= e(t('section.display_settings.description')) ?></p>
            </div>
        </div>
        <div class="toggle-grid">
            <label class="toggle-row">
                <input type="checkbox" name="show_desktop" value="1"<?= checked($widget['show_desktop']) ?>>
                <span><?= e(t('toggle.show_on_desktop')) ?></span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="show_mobile" value="1"<?= checked($widget['show_mobile']) ?>>
                <span><?= e(t('toggle.show_on_mobile')) ?></span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="show_global" value="1"<?= checked($widget['show_global']) ?>>
                <span><?= e(t('toggle.show_globally')) ?></span>
            </label>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="business-hours">
        <div class="section-title">
            <span>7</span>
            <div>
                <h2><?= e(t('section.business_hours.title')) ?></h2>
                <p><?= e(t('section.business_hours.description')) ?></p>
            </div>
        </div>
        <?php $businessHoursMode = (string) ($widget['business_hours_mode'] ?? 'always_open'); ?>
        <label>
            <span><?= e(t('label.business_hours_mode')) ?></span>
            <select name="business_hours_mode" id="business_hours_mode" data-business-hours-mode>
                <option value="always_open"<?= selected($businessHoursMode, 'always_open') ?>><?= e(t('option.always_open')) ?></option>
                <option value="always_closed"<?= selected($businessHoursMode, 'always_closed') ?>><?= e(t('option.always_closed')) ?></option>
                <option value="custom"<?= selected($businessHoursMode, 'custom') ?>><?= e(t('option.custom_business_hours')) ?></option>
            </select>
        </label>

        <div class="business-hours-dynamic" id="alwaysOpenState" data-always-open-state<?= $businessHoursMode === 'always_open' ? '' : ' hidden' ?>>
            <span class="business-hours-status is-online"><?= e(t('business_hours.always_online')) ?></span>
            <p class="field-helper"><?= e(t('helper.business_hours_always_online')) ?></p>
        </div>

        <div class="business-hours-dynamic" id="offlineMessageGroup" data-offline-message-group<?= $businessHoursMode === 'always_open' ? ' hidden' : '' ?>>
            <p class="field-helper" data-offline-helper-closed<?= $businessHoursMode === 'always_closed' ? '' : ' hidden' ?>><?= e(t('helper.business_hours_always_offline')) ?></p>
            <p class="field-helper" data-offline-helper-custom<?= $businessHoursMode === 'custom' ? '' : ' hidden' ?>><?= e(t('helper.business_hours_custom')) ?></p>
            <label>
                <span><?= e(t('label.offline_message')) ?></span>
                <input type="text" name="offline_message" value="<?= e($widget['offline_message']) ?>">
            </label>
        </div>

        <div class="business-hours-dynamic" id="customBusinessHoursGroup" data-custom-business-hours-group<?= $businessHoursMode === 'custom' ? '' : ' hidden' ?>>
            <h3 class="business-hours-subheading"><?= e(t('subheading.weekly_availability')) ?></h3>
            <div class="business-hours-table" data-business-hours-table>
                <?php foreach ($businessHours as $day => $settings): ?>
                    <?php
                    $dayEnabled = !empty($settings['enabled']);
                    $dayDisabled = $businessHoursMode === 'custom' && !$dayEnabled;
                    ?>
                    <div class="business-row business-day-row<?= $dayDisabled ? ' is-disabled' : '' ?>" data-business-day-row>
                        <label class="toggle-row">
                            <input
                                type="checkbox"
                                name="business_hours[<?= e($day) ?>][enabled]"
                                value="1"
                                data-day-enabled
                                <?= checked($dayEnabled) ?>
                            >
                            <span><?= e(translate_day($day)) ?></span>
                        </label>
                        <input
                            type="time"
                            name="business_hours[<?= e($day) ?>][open]"
                            value="<?= e((string) ($settings['open'] ?? '09:00')) ?>"
                            <?= $dayDisabled ? 'disabled' : '' ?>
                        >
                        <input
                            type="time"
                            name="business_hours[<?= e($day) ?>][close]"
                            value="<?= e((string) ($settings['close'] ?? '18:00')) ?>"
                            <?= $dayDisabled ? 'disabled' : '' ?>
                        >
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="greeting-dialog">
        <div class="section-title">
            <span>8</span>
            <div>
                <h2><?= e(t('section.greeting_dialog.title')) ?></h2>
                <p><?= e(t('section.greeting_dialog.description')) ?></p>
            </div>
        </div>
        <label class="toggle-row">
            <input type="checkbox" name="greeting_enabled" value="1"<?= checked($widget['greeting_enabled']) ?>>
            <span><?= e(t('toggle.enable_greeting_dialog')) ?></span>
        </label>
        <div class="form-grid two-columns">
            <label>
                <span><?= e(t('label.greeting_title')) ?></span>
                <input type="text" name="greeting_title" value="<?= e($widget['greeting_title']) ?>">
            </label>
            <label>
                <span><?= e(t('label.greeting_delay_seconds')) ?></span>
                <input type="number" min="0" max="120" name="greeting_delay_seconds" value="<?= e((string) $widget['greeting_delay_seconds']) ?>">
            </label>
            <label class="span-2">
                <span><?= e(t('label.greeting_message')) ?></span>
                <textarea name="greeting_message" rows="3"><?= e($widget['greeting_message']) ?></textarea>
            </label>
        </div>
        <label class="toggle-row">
            <input type="checkbox" name="greeting_capture_phone" value="1" data-greeting-capture-toggle<?= checked($widget['greeting_capture_phone'] ?? 0) ?>>
            <span><?= e(t('toggle.enable_phone_capture')) ?></span>
        </label>
        <div class="greeting-capture-options" data-greeting-capture-options>
            <label class="toggle-row">
                <input
                    type="checkbox"
                    name="greeting_force_phone_capture"
                    value="1"
                    data-greeting-force-toggle
                    <?= checked($widget['greeting_force_phone_capture'] ?? 0) ?>
                >
                <span><?= e(t('toggle.force_phone_capture')) ?></span>
            </label>
            <small class="field-helper"><?= e(t('helper.greeting_force_phone')) ?></small>
            <label class="toggle-row">
                <input
                    type="checkbox"
                    name="greeting_phone_required"
                    value="1"
                    data-greeting-phone-required
                    <?= checked($widget['greeting_phone_required'] ?? 1) ?>
                >
                <span><?= e(t('toggle.phone_number_required')) ?></span>
            </label>
            <div class="form-grid two-columns">
                <label>
                    <span><?= e(t('label.phone_input_placeholder')) ?></span>
                    <input type="text" name="greeting_phone_placeholder" value="<?= e((string) ($widget['greeting_phone_placeholder'] ?? t('default.greeting_phone_placeholder'))) ?>">
                </label>
                <label>
                    <span><?= e(t('label.submit_button_text')) ?></span>
                    <input type="text" name="greeting_submit_text" value="<?= e((string) ($widget['greeting_submit_text'] ?? t('default.greeting_submit_text'))) ?>">
                </label>
                <label class="span-2">
                    <span><?= e(t('label.success_message')) ?></span>
                    <input type="text" name="greeting_lead_success_message" value="<?= e((string) ($widget['greeting_lead_success_message'] ?? t('default.greeting_lead_success_message'))) ?>">
                </label>
            </div>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="domain-embed">
        <div class="section-title">
            <span>9</span>
            <div>
                <h2><?= e(t('section.domain_embed.title')) ?></h2>
                <p><?= e(t('section.domain_embed.description')) ?></p>
            </div>
        </div>
        <label>
            <span><?= e(t('label.website_domain')) ?></span>
            <input type="text" name="website_domain" value="<?= e($widget['website_domain']) ?>" placeholder="<?= e(t('placeholder.website_domain')) ?>">
            <small><?= e(t('helper.domain_formats')) ?></small>
        </label>
        <div class="toggle-grid domain-lock-grid">
            <label class="toggle-row">
                <input type="checkbox" name="domain_lock_enabled" value="1"<?= checked($widget['domain_lock_enabled']) ?>>
                <span><?= e(t('toggle.enable_domain_lock')) ?></span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="allow_www" value="1"<?= checked($widget['allow_www']) ?>>
                <span><?= e(t('toggle.allow_www')) ?></span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="allow_subdomains" value="1"<?= checked($widget['allow_subdomains']) ?>>
                <span><?= e(t('toggle.allow_subdomains')) ?></span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="strict_domain_check" value="1" data-strict-domain-check<?= !empty($widget['strict_domain_check']) ? ' checked' : '' ?>>
                <span><?= e(t('toggle.strict_domain_check')) ?></span>
            </label>
        </div>
        <div class="notice-box compact"><?= e(t('alert.strict_domain_notice')) ?></div>
        <div class="notice-box compact alert-warning" data-strict-domain-warning<?= !empty($widget['strict_domain_check']) ? '' : ' hidden' ?>><?= e(t('alert.strict_domain_warning')) ?></div>
        <?php if ($embed !== ''): ?>
            <div class="embed-box">
                <div class="panel-heading">
                    <h3><?= e(t('subheading.iframe_embed_code')) ?></h3>
                    <button type="button" class="btn btn-small btn-primary" data-copy-target="#embed-code"><?= e(t('button.copy_code')) ?></button>
                </div>
                <div class="alert alert-warning"><?= e(t('embed.domain_locked_warning')) ?></div>
                <textarea id="embed-code" readonly rows="5"><?= e($embed) ?></textarea>
            </div>

            <div class="install-info-card">
                <h3><?= e(t('subheading.install_on_website')) ?></h3>
                <ol class="install-steps">
                    <li><?= e(t('embed.install_step_copy')) ?></li>
                    <li><?= e(t('embed.install_step_paste', ['domain' => $widget['website_domain']])) ?></li>
                    <li><?= e(t('embed.install_step_save')) ?></li>
                    <li><?= e(t('embed.install_step_preview')) ?></li>
                </ol>
                <a class="btn btn-light btn-small" href="widget-preview.php?id=<?= (int) $widget['id'] ?>" target="_blank" rel="noopener noreferrer"><?= e(t('button.open_saved_widget_preview')) ?></a>
            </div>
        <?php else: ?>
            <div class="notice-box"><?= e(t('embed.save_to_generate')) ?></div>
        <?php endif; ?>
    </section>

    <section class="settings-card" data-settings-panel="custom-code">
        <div class="section-title">
            <span>10</span>
            <div>
                <h2><?= e(t('section.custom_code.title')) ?></h2>
                <p><?= e(t('section.custom_code.description')) ?></p>
            </div>
        </div>
        <div class="alert alert-warning"><?= e(t('custom_code.warning')) ?></div>
        <label>
            <span><?= e(t('label.custom_css')) ?></span>
            <small><?= e(t('helper.custom_css')) ?></small>
            <div class="code-editor">
                <pre class="code-lines" aria-hidden="true">1</pre>
                <textarea class="code-textarea" name="custom_css" rows="7" spellcheck="false"><?= e($widget['custom_css']) ?></textarea>
            </div>
        </label>
        <label>
            <span><?= e(t('label.custom_script_head')) ?></span>
            <small><?= e(t('helper.custom_script_head')) ?></small>
            <div class="code-editor">
                <pre class="code-lines" aria-hidden="true">1</pre>
                <textarea class="code-textarea" name="custom_script_head" rows="6" spellcheck="false"><?= e($widget['custom_script_head']) ?></textarea>
            </div>
        </label>
        <label>
            <span><?= e(t('label.custom_script_body')) ?></span>
            <small><?= e(t('helper.custom_script_body')) ?></small>
            <div class="code-editor">
                <pre class="code-lines" aria-hidden="true">1</pre>
                <textarea class="code-textarea" name="custom_script_body" rows="6" spellcheck="false"><?= e($widget['custom_script_body']) ?></textarea>
            </div>
        </label>
        <label>
            <span><?= e(t('label.custom_script_foot')) ?></span>
            <small><?= e(t('helper.custom_script_foot')) ?></small>
            <div class="code-editor">
                <pre class="code-lines" aria-hidden="true">1</pre>
                <textarea class="code-textarea" name="custom_script_footer" rows="6" spellcheck="false"><?= e($widget['custom_script_footer']) ?></textarea>
            </div>
        </label>
        <button type="submit" name="reset_custom_code" value="1" class="btn btn-light" data-reset-custom-code><?= e(t('button.reset_custom_code')) ?></button>
    </section>
        </div>
    </div>

    <script type="application/json" id="country-code-data"><?= json_for_html(calling_code_options()) ?></script>
</form>
