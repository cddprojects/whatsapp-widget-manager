</main>
<script type="application/json" id="ctcw-i18n"><?= json_for_html(dashboard_js_i18n()) ?></script>
<script src="assets/js/dashboard.js?v=<?= (int) filemtime(__DIR__ . '/../assets/js/dashboard.js') ?>"></script>
<?php foreach ($pageScripts ?? [] as $script): ?>
<script src="<?= e($script) ?>?v=<?= (int) filemtime(__DIR__ . '/../' . ltrim($script, '/')) ?>"></script>
<?php endforeach; ?>
</body>
</html>
