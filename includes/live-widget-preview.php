<?php
declare(strict_types=1);
?>
<aside class="ctcw-live-preview-column" aria-label="Live widget preview">
    <div class="ctcw-live-preview-card">
        <div class="ctcw-live-preview-header">
            <div>
                <h3>Live Preview</h3>
                <p>Preview updates based on your current settings. Clicks are disabled in preview mode.</p>
                <small class="ctcw-live-preview-note">Preview may include unsaved changes. Click Save Widget to publish.</small>
            </div>
            <button type="button" class="btn btn-small btn-light" id="ctcwToggleGreetingPreview" data-live-preview-greeting-toggle hidden>
                Show greeting
            </button>
        </div>

        <div class="ctcw-live-preview-badges" data-live-preview-badges hidden></div>

        <div class="ctcw-live-preview-canvas" id="ctcwLivePreviewCanvas">
            <div class="ctcw-fake-page">
                <div class="ctcw-fake-header"></div>
                <div class="ctcw-fake-line"></div>
                <div class="ctcw-fake-line short"></div>
                <div class="ctcw-fake-button"></div>
            </div>

            <div id="ctcwLiveWidgetPreview" class="ctcw-live-widget-preview"></div>
        </div>

        <p class="ctcw-live-preview-toast" id="ctcwLivePreviewToast" hidden></p>
    </div>
</aside>

<style id="ctcwLivePreviewCustomCss"></style>
<script type="application/json" id="ctcw-preview-icon"><?= json_for_html(whatsapp_icon_svg()) ?></script>
