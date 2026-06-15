<?php
$widget = array_merge(default_widget_data(), $widget ?? []);
$randomNumbers = json_decode((string) ($widget['random_numbers_json'] ?? '[]'), true);
if (!is_array($randomNumbers) || count($randomNumbers) === 0) {
    $randomNumbers = [['country_code' => $widget['whatsapp_country_code'] ?? '+60', 'number' => '']];
}
$businessHours = json_decode((string) ($widget['business_hours_json'] ?? ''), true);
if (!is_array($businessHours)) {
    $businessHours = default_business_hours();
}
$embed = !empty($widget['id']) && !empty($widget['public_key']) ? embed_code($widget) : '';
$settingsSections = [
    ['id' => 'whatsapp-number', 'number' => '1', 'label' => 'WhatsApp Number'],
    ['id' => 'prefilled-message', 'number' => '2', 'label' => 'Pre-Filled Message'],
    ['id' => 'call-to-action', 'number' => '3', 'label' => 'Call To Action'],
    ['id' => 'style-position', 'number' => '4', 'label' => 'Style & Position'],
    ['id' => 'url-structure', 'number' => '5', 'label' => 'URL Structure'],
    ['id' => 'display-settings', 'number' => '6', 'label' => 'Display Settings'],
    ['id' => 'business-hours', 'number' => '7', 'label' => 'Business Hours'],
    ['id' => 'greeting-dialog', 'number' => '8', 'label' => 'Greeting Dialog'],
    ['id' => 'domain-embed', 'number' => '9', 'label' => 'Domain Lock & Embed Code'],
    ['id' => 'custom-code', 'number' => '10', 'label' => 'Custom Code'],
];
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>Please fix the following:</strong>
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
                    <h2>Client assignment</h2>
                    <p>Select which client account will own this widget.</p>
                </div>
            </div>
            <label>
                <span>Assign to client</span>
                <select name="owner_user_id" required>
                    <option value="">Select client…</option>
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
        <a class="btn btn-light" href="dashboard.php">Back</a>
        <button type="submit" class="btn btn-primary">Save Widget</button>
    </div>

    <div class="settings-workspace" data-settings-workspace>
        <aside class="settings-sidebar" aria-label="Widget settings sections">
            <div class="settings-sidebar-card">
                <strong>Widget setup</strong>
                <p>Choose a section to edit.</p>
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
                <h2>WhatsApp Number</h2>
                <p>Use a normal WhatsApp number, WhatsApp Business number, or rotate multiple numbers randomly.</p>
            </div>
        </div>
        <div class="form-grid">
            <label>
                <span>Widget name</span>
                <input type="text" name="widget_name" value="<?= e($widget['widget_name']) ?>">
            </label>
            <label>
                <span>Country code</span>
                <select name="whatsapp_country_code"><?= render_country_options((string) $widget['whatsapp_country_code']) ?></select>
            </label>
            <label>
                <span>WhatsApp number</span>
                <input type="tel" name="whatsapp_number" value="<?= e($widget['whatsapp_number']) ?>" placeholder="123456789">
                <small>Digits only. Do not include spaces, symbols, or the country code.</small>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="use_random_numbers" value="1"<?= checked($widget['use_random_numbers']) ?>>
                <span>Enable random number selection on each click</span>
            </label>
        </div>

        <div class="random-number-panel">
            <div class="panel-heading">
                <h3>Random numbers</h3>
                <button type="button" class="btn btn-small btn-light" data-add-random-number>Add number</button>
            </div>
            <div data-random-number-list>
                <?php foreach ($randomNumbers as $index => $row): ?>
                    <div class="repeat-row">
                        <select name="random_numbers[<?= (int) $index ?>][country_code]"><?= render_country_options((string) ($row['country_code'] ?? '+60')) ?></select>
                        <input type="tel" name="random_numbers[<?= (int) $index ?>][number]" value="<?= e((string) ($row['number'] ?? '')) ?>" placeholder="123456789">
                        <button type="button" class="btn btn-small btn-danger-soft" data-remove-row>Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="prefilled-message">
        <div class="section-title">
            <span>2</span>
            <div>
                <h2>Pre-Filled Message</h2>
                <p>Message variables are replaced inside the iframe before opening WhatsApp.</p>
            </div>
        </div>
        <label>
            <span>Message</span>
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
                <h2>Call To Action</h2>
                <p>This text appears beside the WhatsApp icon where the selected style supports text.</p>
            </div>
        </div>
        <label>
            <span>Button text</span>
            <input type="text" name="call_to_action" value="<?= e($widget['call_to_action']) ?>" placeholder="WhatsApp us">
        </label>
    </section>

    <section class="settings-card" data-settings-panel="style-position">
        <div class="section-title">
            <span>4</span>
            <div>
                <h2>Style & Position</h2>
                <p>Desktop and mobile can use separate styles and placements.</p>
            </div>
        </div>
        <div class="form-grid two-columns">
            <label>
                <span>Desktop style</span>
                <select name="desktop_style" data-style-select>
                    <?php foreach (widget_styles() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= selected((string) $widget['desktop_style'], $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Mobile style</span>
                <select name="mobile_style" data-style-select>
                    <?php foreach (widget_styles() as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= selected((string) $widget['mobile_style'], $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="style-preview-grid">
            <?php foreach (widget_styles() as $key => $label): ?>
                <div class="style-preview" data-style-preview-card="<?= e($key) ?>">
                    <div class="mini-widget ctcw-widget <?= e($key) ?>">
                        <span class="ctcw-icon"><?= whatsapp_icon_svg() ?></span>
                        <span class="ctcw-text">WhatsApp us</span>
                    </div>
                    <small><?= e($label) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="position-grid">
            <div>
                <h3>Desktop position</h3>
                <div class="form-grid two-columns">
                    <label>
                        <span>Position type</span>
                        <select name="desktop_position_type">
                            <option value="fixed"<?= selected((string) $widget['desktop_position_type'], 'fixed') ?>>Fixed</option>
                            <option value="absolute"<?= selected((string) $widget['desktop_position_type'], 'absolute') ?>>Absolute</option>
                        </select>
                    </label>
                    <label>
                        <span>Vertical side</span>
                        <select name="desktop_vertical_position_type">
                            <option value="bottom"<?= selected((string) $widget['desktop_vertical_position_type'], 'bottom') ?>>Bottom</option>
                            <option value="top"<?= selected((string) $widget['desktop_vertical_position_type'], 'top') ?>>Top</option>
                        </select>
                    </label>
                    <label>
                        <span>Vertical value</span>
                        <input type="text" name="desktop_vertical_position_value" value="<?= e($widget['desktop_vertical_position_value']) ?>" placeholder="25px">
                    </label>
                    <label>
                        <span>Horizontal side</span>
                        <select name="desktop_horizontal_position_type">
                            <option value="right"<?= selected((string) $widget['desktop_horizontal_position_type'], 'right') ?>>Right</option>
                            <option value="left"<?= selected((string) $widget['desktop_horizontal_position_type'], 'left') ?>>Left</option>
                        </select>
                    </label>
                    <label>
                        <span>Horizontal value</span>
                        <input type="text" name="desktop_horizontal_position_value" value="<?= e($widget['desktop_horizontal_position_value']) ?>" placeholder="25px">
                    </label>
                </div>
            </div>
            <div>
                <h3>Mobile position</h3>
                <label class="toggle-row">
                    <input type="checkbox" name="same_mobile_desktop_settings" value="1"<?= checked($widget['same_mobile_desktop_settings']) ?> data-same-mobile>
                    <span>Use same settings as desktop</span>
                </label>
                <div class="form-grid two-columns" data-mobile-position-fields>
                    <label>
                        <span>Position type</span>
                        <select name="mobile_position_type">
                            <option value="fixed"<?= selected((string) $widget['mobile_position_type'], 'fixed') ?>>Fixed</option>
                            <option value="absolute"<?= selected((string) $widget['mobile_position_type'], 'absolute') ?>>Absolute</option>
                        </select>
                    </label>
                    <label>
                        <span>Vertical side</span>
                        <select name="mobile_vertical_position_type">
                            <option value="bottom"<?= selected((string) $widget['mobile_vertical_position_type'], 'bottom') ?>>Bottom</option>
                            <option value="top"<?= selected((string) $widget['mobile_vertical_position_type'], 'top') ?>>Top</option>
                        </select>
                    </label>
                    <label>
                        <span>Vertical value</span>
                        <input type="text" name="mobile_vertical_position_value" value="<?= e($widget['mobile_vertical_position_value']) ?>" placeholder="25px">
                    </label>
                    <label>
                        <span>Horizontal side</span>
                        <select name="mobile_horizontal_position_type">
                            <option value="right"<?= selected((string) $widget['mobile_horizontal_position_type'], 'right') ?>>Right</option>
                            <option value="left"<?= selected((string) $widget['mobile_horizontal_position_type'], 'left') ?>>Left</option>
                        </select>
                    </label>
                    <label>
                        <span>Horizontal value</span>
                        <input type="text" name="mobile_horizontal_position_value" value="<?= e($widget['mobile_horizontal_position_value']) ?>" placeholder="25px">
                    </label>
                </div>
            </div>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="url-structure">
        <div class="section-title">
            <span>5</span>
            <div>
                <h2>URL Structure</h2>
                <p>Choose how desktop and mobile users open WhatsApp.</p>
            </div>
        </div>
        <div class="form-grid two-columns">
            <label>
                <span>Desktop open link in</span>
                <select name="desktop_open_link_type">
                    <option value="new_tab"<?= selected((string) $widget['desktop_open_link_type'], 'new_tab') ?>>Open new tab only</option>
                    <option value="same_tab"<?= selected((string) $widget['desktop_open_link_type'], 'same_tab') ?>>Redirect current page only</option>
                </select>
            </label>
            <label>
                <span>Desktop URL structure</span>
                <select name="desktop_url_structure">
                    <option value="wa_me"<?= selected((string) $widget['desktop_url_structure'], 'wa_me') ?>>wa.me</option>
                    <option value="web_whatsapp"<?= selected((string) $widget['desktop_url_structure'], 'web_whatsapp') ?>>web.whatsapp.com</option>
                    <option value="whatsapp_app"<?= selected((string) $widget['desktop_url_structure'], 'whatsapp_app') ?>>WhatsApp://</option>
                    <option value="custom"<?= selected((string) $widget['desktop_url_structure'], 'custom') ?>>Custom URL</option>
                </select>
            </label>
            <label>
                <span>Mobile open link in</span>
                <select name="mobile_open_link_type">
                    <option value="new_tab"<?= selected((string) $widget['mobile_open_link_type'], 'new_tab') ?>>Open new tab only</option>
                    <option value="same_tab"<?= selected((string) $widget['mobile_open_link_type'], 'same_tab') ?>>Redirect current page only</option>
                </select>
            </label>
            <label>
                <span>Mobile URL structure</span>
                <select name="mobile_url_structure">
                    <option value="wa_me"<?= selected((string) $widget['mobile_url_structure'], 'wa_me') ?>>wa.me</option>
                    <option value="whatsapp_app"<?= selected((string) $widget['mobile_url_structure'], 'whatsapp_app') ?>>WhatsApp://</option>
                    <option value="custom"<?= selected((string) $widget['mobile_url_structure'], 'custom') ?>>Custom URL</option>
                </select>
            </label>
            <label class="span-2">
                <span>Custom URL</span>
                <input type="url" name="custom_url" value="<?= e($widget['custom_url']) ?>" placeholder="https://example.com/your-whatsapp-channel">
                <small>The encoded message is appended as a <code>text</code> query parameter.</small>
            </label>
        </div>
        <div class="notice-box compact">Desktop and mobile open behavior are separate. Set both to "Open new tab only" if you want the widget to never redirect the current page.</div>
    </section>

    <section class="settings-card" data-settings-panel="display-settings">
        <div class="section-title">
            <span>6</span>
            <div>
                <h2>Display Settings</h2>
                <p>Control desktop, mobile, and global visibility.</p>
            </div>
        </div>
        <div class="toggle-grid">
            <label class="toggle-row">
                <input type="checkbox" name="show_desktop" value="1"<?= checked($widget['show_desktop']) ?>>
                <span>Show on desktop</span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="show_mobile" value="1"<?= checked($widget['show_mobile']) ?>>
                <span>Show on mobile</span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="show_global" value="1"<?= checked($widget['show_global']) ?>>
                <span>Show on all pages globally</span>
            </label>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="business-hours">
        <div class="section-title">
            <span>7</span>
            <div>
                <h2>Business Hours</h2>
                <p>Outside business hours, the widget shows an offline state and disables the WhatsApp click.</p>
            </div>
        </div>
        <div class="form-grid two-columns">
            <label>
                <span>Business hours mode</span>
                <select name="business_hours_mode" data-business-hours-mode>
                    <option value="always_open"<?= selected((string) $widget['business_hours_mode'], 'always_open') ?>>Always Open / Online</option>
                    <option value="always_closed"<?= selected((string) $widget['business_hours_mode'], 'always_closed') ?>>Always Closed / Offline</option>
                    <option value="custom"<?= selected((string) $widget['business_hours_mode'], 'custom') ?>>Custom Business Hours</option>
                </select>
            </label>
            <label>
                <span>Offline message</span>
                <input type="text" name="offline_message" value="<?= e($widget['offline_message']) ?>">
            </label>
        </div>
        <div class="business-hours-table" data-business-hours-table>
            <?php foreach ($businessHours as $day => $settings): ?>
                <div class="business-row">
                    <label class="toggle-row">
                        <input type="checkbox" name="business_hours[<?= e($day) ?>][enabled]" value="1"<?= checked($settings['enabled'] ?? false) ?>>
                        <span><?= e(ucfirst($day)) ?></span>
                    </label>
                    <input type="time" name="business_hours[<?= e($day) ?>][open]" value="<?= e((string) ($settings['open'] ?? '09:00')) ?>">
                    <input type="time" name="business_hours[<?= e($day) ?>][close]" value="<?= e((string) ($settings['close'] ?? '18:00')) ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="greeting-dialog">
        <div class="section-title">
            <span>8</span>
            <div>
                <h2>Greeting Dialog</h2>
                <p>Show a delayed popup above the floating WhatsApp button.</p>
            </div>
        </div>
        <label class="toggle-row">
            <input type="checkbox" name="greeting_enabled" value="1"<?= checked($widget['greeting_enabled']) ?>>
            <span>Enable greeting dialog</span>
        </label>
        <div class="form-grid two-columns">
            <label>
                <span>Greeting title</span>
                <input type="text" name="greeting_title" value="<?= e($widget['greeting_title']) ?>">
            </label>
            <label>
                <span>Greeting delay seconds</span>
                <input type="number" min="0" max="120" name="greeting_delay_seconds" value="<?= e((string) $widget['greeting_delay_seconds']) ?>">
            </label>
            <label class="span-2">
                <span>Greeting message</span>
                <textarea name="greeting_message" rows="3"><?= e($widget['greeting_message']) ?></textarea>
            </label>
        </div>
        <label class="toggle-row">
            <input type="checkbox" name="greeting_capture_phone" value="1"<?= checked($widget['greeting_capture_phone'] ?? 0) ?>>
            <span>Enable phone number capture</span>
        </label>
        <label class="toggle-row">
            <input type="checkbox" name="greeting_phone_required" value="1"<?= checked($widget['greeting_phone_required'] ?? 1) ?>>
            <span>Phone number required</span>
        </label>
        <div class="form-grid two-columns">
            <label>
                <span>Phone input placeholder</span>
                <input type="text" name="greeting_phone_placeholder" value="<?= e((string) ($widget['greeting_phone_placeholder'] ?? 'Enter your phone number')) ?>">
            </label>
            <label>
                <span>Submit button text</span>
                <input type="text" name="greeting_submit_text" value="<?= e((string) ($widget['greeting_submit_text'] ?? 'Continue to WhatsApp')) ?>">
            </label>
            <label class="span-2">
                <span>Success message</span>
                <input type="text" name="greeting_lead_success_message" value="<?= e((string) ($widget['greeting_lead_success_message'] ?? 'Redirecting to WhatsApp...')) ?>">
            </label>
        </div>
    </section>

    <section class="settings-card" data-settings-panel="domain-embed">
        <div class="section-title">
            <span>9</span>
            <div>
                <h2>Domain Lock & Embed Code</h2>
                <p>Lock this iframe widget to the approved website domain before sharing the embed code.</p>
            </div>
        </div>
        <label>
            <span>Website domain</span>
            <input type="text" name="website_domain" value="<?= e($widget['website_domain']) ?>" placeholder="example.com">
            <small>Accepted formats: example.com, www.example.com, https://example.com.</small>
        </label>
        <div class="toggle-grid domain-lock-grid">
            <label class="toggle-row">
                <input type="checkbox" name="domain_lock_enabled" value="1"<?= checked($widget['domain_lock_enabled']) ?>>
                <span>Enable domain lock</span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="allow_www" value="1"<?= checked($widget['allow_www']) ?>>
                <span>Allow www version</span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="allow_subdomains" value="1"<?= checked($widget['allow_subdomains']) ?>>
                <span>Allow subdomains</span>
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="strict_domain_check" value="1"<?= checked($widget['strict_domain_check']) ?>>
                <span>Strict domain check</span>
            </label>
        </div>
        <div class="notice-box compact">Strict domain check blocks the widget when the browser does not send a referrer. Recommended default: enabled.</div>
        <?php if ($embed !== ''): ?>
            <div class="embed-box">
                <div class="panel-heading">
                    <h3>Iframe embed code</h3>
                    <button type="button" class="btn btn-small btn-primary" data-copy-target="#embed-code">Copy code</button>
                </div>
                <div class="alert alert-warning">This iframe widget is locked to your registered domain. It will not work if copied to another website.</div>
                <textarea id="embed-code" readonly rows="5"><?= e($embed) ?></textarea>
                <div class="preview-frame-wrap large">
                    <iframe src="widget-preview.php?id=<?= (int) $widget['id'] ?>" title="Widget preview" class="preview-frame"></iframe>
                </div>
            </div>
        <?php else: ?>
            <div class="notice-box">Save the widget once to generate the unique iframe embed code and preview.</div>
        <?php endif; ?>
    </section>

    <section class="settings-card" data-settings-panel="custom-code">
        <div class="section-title">
            <span>10</span>
            <div>
                <h2>Custom Code</h2>
                <p>Only add code from sources you trust. Incorrect code may break your widget display.</p>
            </div>
        </div>
        <div class="alert alert-warning">Custom code is stored as frontend HTML/CSS/JavaScript and rendered only inside this widget iframe. PHP code is stripped and never evaluated on the server.</div>
        <label>
            <span>Custom CSS</span>
            <small>Add CSS to override the default widget style.</small>
            <div class="code-editor">
                <pre class="code-lines" aria-hidden="true">1</pre>
                <textarea class="code-textarea" name="custom_css" rows="7" spellcheck="false"><?= e($widget['custom_css']) ?></textarea>
            </div>
        </label>
        <label>
            <span>Custom script (head)</span>
            <small>Inject custom script or links inside &lt;head&gt; tag.</small>
            <div class="code-editor">
                <pre class="code-lines" aria-hidden="true">1</pre>
                <textarea class="code-textarea" name="custom_script_head" rows="6" spellcheck="false"><?= e($widget['custom_script_head']) ?></textarea>
            </div>
        </label>
        <label>
            <span>Custom script (body)</span>
            <small>Inject custom script after &lt;body&gt; tag.</small>
            <div class="code-editor">
                <pre class="code-lines" aria-hidden="true">1</pre>
                <textarea class="code-textarea" name="custom_script_body" rows="6" spellcheck="false"><?= e($widget['custom_script_body']) ?></textarea>
            </div>
        </label>
        <label>
            <span>Custom script (foot)</span>
            <small>Inject custom script before &lt;/body&gt; tag.</small>
            <div class="code-editor">
                <pre class="code-lines" aria-hidden="true">1</pre>
                <textarea class="code-textarea" name="custom_script_footer" rows="6" spellcheck="false"><?= e($widget['custom_script_footer']) ?></textarea>
            </div>
        </label>
        <button type="submit" name="reset_custom_code" value="1" class="btn btn-light" data-reset-custom-code>Reset Custom Code</button>
    </section>
        </div>
    </div>
</form>
