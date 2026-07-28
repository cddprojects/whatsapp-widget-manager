<?php
declare(strict_types=1);

/**
 * Click To Chat Manager migration runner.
 *
 * Usage:
 *   php migrate.php              Apply pending migrations
 *   php migrate.php --status     Show applied / pending migrations
 *   php migrate.php --baseline   Record historical migrations after full-state verification
 *
 * Never prints database credentials.
 */

require_once __DIR__ . '/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this script from the command line only.' . PHP_EOL);
}

const CTC_MIGRATE_LOCK_NAME = 'ctc_migrate';
const CTC_MIGRATE_LOCK_TIMEOUT_SECONDS = 30;

/**
 * @return list<string>
 */
function migrate_cli_args(array $argv): array
{
    return array_values(array_filter(array_slice($argv, 1), static fn ($arg) => is_string($arg) && $arg !== ''));
}

function migrate_fail(string $message, int $code = 1): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function migrate_info(string $message): void
{
    echo $message . PHP_EOL;
}

function migrate_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
    );
    $stmt->execute(['table_name' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function migrate_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function migrate_index_exists(PDO $pdo, string $table, string $indexName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND INDEX_NAME = :index_name'
    );
    $stmt->execute([
        'table_name' => $table,
        'index_name' => $indexName,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * @param list<string> $columns
 * @return list<string>
 */
function migrate_missing_columns(PDO $pdo, string $table, array $columns): array
{
    $missing = [];
    foreach ($columns as $column) {
        if (!migrate_column_exists($pdo, $table, $column)) {
            $missing[] = $table . '.' . $column;
        }
    }

    return $missing;
}

/**
 * Full expected-state checks for historical migrations.
 * Ambiguous or partial states return errors instead of guessing.
 *
 * @return array{ok: bool, missing: list<string>, notes: list<string>}
 */
function migrate_baseline_requirements(PDO $pdo, string $filename): array
{
    $missing = [];
    $notes = [];

    switch ($filename) {
        case '001_domain_lock.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'allow_www',
                'allow_subdomains',
                'domain_lock_enabled',
                'strict_domain_check',
            ]));
            break;

        case '002_user_roles.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'users', [
                'role',
                'status',
                'last_login_at',
                'password_changed_at',
            ]));
            if (!migrate_index_exists($pdo, 'users', 'idx_users_role_status')) {
                $missing[] = 'index users.idx_users_role_status';
            }
            break;

        case '003_greeting_lead_capture.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'greeting_capture_phone',
                'greeting_phone_required',
                'greeting_phone_placeholder',
                'greeting_submit_text',
                'greeting_lead_success_message',
            ]));
            if (!migrate_table_exists($pdo, 'widget_leads')) {
                $missing[] = 'table widget_leads';
            } else {
                $missing = array_merge($missing, migrate_missing_columns($pdo, 'widget_leads', [
                    'widget_id',
                    'user_id',
                    'visitor_phone',
                    'visitor_full_phone',
                    'whatsapp_redirect_url',
                    'created_at',
                ]));
            }
            break;

        case '004_greeting_force_phone_capture.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'greeting_force_phone_capture',
            ]));
            break;

        case '005_strict_domain_check_default.sql':
            if (!migrate_column_exists($pdo, 'widgets', 'strict_domain_check')) {
                $missing[] = 'widgets.strict_domain_check';
            }
            break;

        case '006_preferred_language.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'users', [
                'preferred_language',
            ]));
            break;

        case '008_round_robin_distribution.sql':
            // Require BOTH columns — do not baseline on a single column.
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'destination_selection_method',
                'round_robin_next_index',
            ]));
            break;

        case '010_widget_activation_status.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'widget_status',
            ]));
            break;

        case '011_client_leads_recycle_bin.sql':
            if (!migrate_table_exists($pdo, 'widget_leads')) {
                $missing[] = 'table widget_leads';
            } else {
                $missing = array_merge($missing, migrate_missing_columns($pdo, 'widget_leads', [
                    'client_id',
                    'deleted_at',
                    'deleted_by_user_id',
                    'deleted_by_role',
                    'restored_at',
                    'restored_by_user_id',
                ]));
            }
            if (!migrate_table_exists($pdo, 'app_settings')) {
                $missing[] = 'table app_settings';
            }
            foreach (['idx_widget_leads_client_active_created', 'idx_widget_leads_client_widget', 'idx_widget_leads_deleted_at'] as $indexName) {
                if (migrate_table_exists($pdo, 'widget_leads') && !migrate_index_exists($pdo, 'widget_leads', $indexName)) {
                    $missing[] = 'index widget_leads.' . $indexName;
                }
            }
            break;

        case '012_phone_submit_button_id.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'greeting_phone_submit_button_id',
            ]));
            break;

        case '013_website_name.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'website_name',
            ]));
            break;

        case '014_greeting_allow_phone_plus.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'greeting_allow_phone_plus',
            ]));
            break;

        case '015_greeting_open_behavior.sql':
            $missing = array_merge($missing, migrate_missing_columns($pdo, 'widgets', [
                'greeting_open_behavior',
            ]));
            break;

        case '016_api_credentials.sql':
            foreach (['api_credentials', 'api_request_logs', 'api_rate_limits'] as $table) {
                if (!migrate_table_exists($pdo, $table)) {
                    $missing[] = 'table ' . $table;
                }
            }
            if (migrate_table_exists($pdo, 'api_credentials')) {
                $missing = array_merge($missing, migrate_missing_columns($pdo, 'api_credentials', [
                    'owner_type',
                    'owner_id',
                    'credential_type',
                    'key_hash',
                    'key_ciphertext',
                    'is_active',
                    'revoked_at',
                ]));
            }
            break;

        default:
            $notes[] = 'No baseline verifier defined for ' . $filename . '; cannot safely baseline.';
            $missing[] = 'baseline_verifier:' . $filename;
            break;
    }

    return [
        'ok' => $missing === [],
        'missing' => $missing,
        'notes' => $notes,
    ];
}

function migrate_ensure_tracking_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            filename VARCHAR(255) NOT NULL,
            checksum CHAR(64) NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_schema_migrations_filename (filename)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function migrate_acquire_lock(PDO $pdo): void
{
    $stmt = $pdo->prepare('SELECT GET_LOCK(:lock_name, :timeout)');
    $stmt->execute([
        'lock_name' => CTC_MIGRATE_LOCK_NAME,
        'timeout' => CTC_MIGRATE_LOCK_TIMEOUT_SECONDS,
    ]);
    $result = $stmt->fetchColumn();
    if ((int) $result !== 1) {
        migrate_fail('Unable to acquire migration lock. Another migration may be running.');
    }
}

function migrate_release_lock(PDO $pdo): void
{
    try {
        $stmt = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $stmt->execute(['lock_name' => CTC_MIGRATE_LOCK_NAME]);
    } catch (Throwable $exception) {
        fwrite(STDERR, 'Warning: failed to release migration lock.' . PHP_EOL);
    }
}

/**
 * @return list<array{path: string, filename: string, checksum: string}>
 */
function migrate_list_files(string $migrationDir): array
{
    $paths = glob($migrationDir . '/*.sql');
    if ($paths === false) {
        migrate_fail('Unable to read migrations directory.');
    }

    sort($paths, SORT_STRING);
    $files = [];
    foreach ($paths as $path) {
        $filename = basename($path);
        $contents = file_get_contents($path);
        if ($contents === false) {
            migrate_fail('Unable to read migration file: ' . $filename);
        }
        $files[] = [
            'path' => $path,
            'filename' => $filename,
            'checksum' => hash('sha256', $contents),
        ];
    }

    return $files;
}

/**
 * @return array<string, array{filename: string, checksum: string, applied_at: string}>
 */
function migrate_applied_map(PDO $pdo): array
{
    if (!migrate_table_exists($pdo, 'schema_migrations')) {
        return [];
    }

    $rows = $pdo->query('SELECT filename, checksum, applied_at FROM schema_migrations ORDER BY filename ASC')->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[(string) $row['filename']] = [
            'filename' => (string) $row['filename'],
            'checksum' => (string) $row['checksum'],
            'applied_at' => (string) $row['applied_at'],
        ];
    }

    return $map;
}

function migrate_record(PDO $pdo, string $filename, string $checksum): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO schema_migrations (filename, checksum, applied_at)
         VALUES (:filename, :checksum, UTC_TIMESTAMP())'
    );
    $stmt->execute([
        'filename' => $filename,
        'checksum' => $checksum,
    ]);
}

function migrate_split_statements(string $sql): array
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
    $statements = [];
    $buffer = '';
    $lines = preg_split("/\r\n|\n|\r/", $sql) ?: [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $buffer .= $line . "\n";
        if (str_ends_with(rtrim($line), ';')) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function migrate_apply_file(PDO $pdo, array $file): void
{
    $sql = file_get_contents($file['path']);
    if ($sql === false) {
        throw new RuntimeException('Unable to read ' . $file['filename']);
    }

    $statements = migrate_split_statements($sql);
    if ($statements === []) {
        throw new RuntimeException('Migration file contains no executable statements: ' . $file['filename']);
    }

    // Record only after the whole migration succeeds.
    // DDL typically auto-commits in MySQL/MariaDB; we still avoid recording on any failure.
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    migrate_record($pdo, $file['filename'], $file['checksum']);
}

function migrate_run_pending(PDO $pdo, array $files, array $applied): int
{
    $pending = 0;
    foreach ($files as $file) {
        if (isset($applied[$file['filename']])) {
            continue;
        }

        $pending++;
        migrate_info('Running ' . $file['filename'] . '...');
        try {
            migrate_apply_file($pdo, $file);
            migrate_info('Done.');
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Migration failed: ' . $file['filename'] . ' — ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    if ($pending === 0) {
        migrate_info('No pending migrations.');
    } else {
        migrate_info('All pending migrations completed.');
    }

    return 0;
}

function migrate_run_status(array $files, array $applied): int
{
    migrate_info('Migration status:');
    foreach ($files as $file) {
        if (isset($applied[$file['filename']])) {
            $record = $applied[$file['filename']];
            $checksumNote = hash_equals($record['checksum'], $file['checksum'])
                ? 'checksum-ok'
                : 'checksum-changed';
            migrate_info(sprintf(
                '  [applied] %s (%s, applied_at=%s)',
                $file['filename'],
                $checksumNote,
                $record['applied_at']
            ));
        } else {
            migrate_info('  [pending] ' . $file['filename']);
        }
    }

    return 0;
}

function migrate_run_baseline(PDO $pdo, array $files, array $applied): int
{
    $baselinable = array_values(array_filter(
        $files,
        static fn (array $file): bool => (bool) preg_match('/^(00[1-9]|01[0-6])_/', $file['filename'])
    ));

    if ($baselinable === []) {
        migrate_info('No historical migrations found to baseline.');
        return 0;
    }

    $errors = [];
    $toRecord = [];

    foreach ($baselinable as $file) {
        if (isset($applied[$file['filename']])) {
            migrate_info('Already recorded: ' . $file['filename']);
            continue;
        }

        $check = migrate_baseline_requirements($pdo, $file['filename']);
        if (!$check['ok']) {
            $errors[] = $file['filename'] . ' incomplete/ambiguous. Missing: ' . implode(', ', $check['missing']);
            foreach ($check['notes'] as $note) {
                $errors[] = '  note: ' . $note;
            }
            continue;
        }

        $toRecord[] = $file;
        migrate_info('Verified full expected state: ' . $file['filename']);
    }

    if ($errors !== []) {
        fwrite(STDERR, "Baseline aborted. Ambiguous or incomplete migration states detected:\n");
        foreach ($errors as $error) {
            fwrite(STDERR, '  - ' . $error . PHP_EOL);
        }
        fwrite(STDERR, "No baseline records were written.\n");
        return 1;
    }

    foreach ($toRecord as $file) {
        migrate_record($pdo, $file['filename'], $file['checksum']);
        migrate_info('Recorded baseline: ' . $file['filename']);
    }

    if ($toRecord === []) {
        migrate_info('Nothing new to baseline.');
    } else {
        migrate_info('Baseline completed successfully.');
    }

    return 0;
}

$args = migrate_cli_args($argv ?? []);
$mode = 'apply';
foreach ($args as $arg) {
    if ($arg === '--status') {
        $mode = 'status';
    } elseif ($arg === '--baseline') {
        $mode = 'baseline';
    } elseif ($arg === '--help' || $arg === '-h') {
        migrate_info('Usage: php migrate.php [--status|--baseline]');
        exit(0);
    } else {
        migrate_fail('Unknown argument: ' . $arg . PHP_EOL . 'Usage: php migrate.php [--status|--baseline]');
    }
}

$migrationDir = __DIR__ . '/migrations';
if (!is_dir($migrationDir)) {
    migrate_fail('Migrations directory not found.');
}

$pdo = null;
$lockHeld = false;

try {
    $pdo = db();
    migrate_ensure_tracking_table($pdo);
    migrate_acquire_lock($pdo);
    $lockHeld = true;

    $files = migrate_list_files($migrationDir);
    $applied = migrate_applied_map($pdo);

    $exitCode = match ($mode) {
        'status' => migrate_run_status($files, $applied),
        'baseline' => migrate_run_baseline($pdo, $files, $applied),
        default => migrate_run_pending($pdo, $files, $applied),
    };

    migrate_release_lock($pdo);
    $lockHeld = false;
    exit($exitCode);
} catch (Throwable $exception) {
    if ($lockHeld && $pdo instanceof PDO) {
        migrate_release_lock($pdo);
    }
    migrate_fail($exception->getMessage());
}
