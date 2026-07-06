#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$startedAt = microtime(true);
$result = purge_expired_recycled_leads();
$elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

$deleted = (int) ($result['deleted'] ?? 0);
$message = sprintf(
    '[CTC] purge_deleted_leads deleted=%d elapsed_ms=%d enabled=%s retention_days=%d',
    $deleted,
    $elapsedMs,
    lead_recycle_auto_purge_enabled() ? '1' : '0',
    lead_recycle_retention_days()
);

fwrite(STDOUT, $message . PHP_EOL);
error_log($message);

exit(!empty($result['success']) ? 0 : 1);
