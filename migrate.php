<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this script from the command line only.');
}

$migrationDir = __DIR__ . '/migrations';
$files = glob($migrationDir . '/*.sql');
sort($files);

if ($files === false || $files === []) {
    echo "No migration files found.\n";
    exit(0);
}

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Unable to read {$file}\n");
        continue;
    }

    echo 'Running ' . basename($file) . "...\n";
    db()->exec($sql);
    echo "Done.\n";
}

echo "All migrations completed.\n";
