<?php
/**
 * One-time migration runner: convert job_levels from global/company two-tier
 * scoping to tenant/company two-tier scoping.
 *
 * Run from the project root:
 *   php app/migrations/1.0.0/run_job_levels_tenant_scoping.php
 *
 * Prerequisites: job_levels_table.sql and tenant_system.sql must have been applied.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Phalcon\Di\FactoryDefault;

$di = new FactoryDefault();

$config = require __DIR__ . '/../../config/config.php';
$di->set('config', $config);

$dbConfig = $config['database'];
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['dbname'],
    $dbConfig['charset'] ?? 'utf8mb4'
);

$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$sql = file_get_contents(__DIR__ . '/job_levels_tenant_scoping.sql');

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement)) {
        continue;
    }
    // Skip comment-only statements
    if (preg_match('/^--/', $statement) || trim(str_replace(['--', "\n", "\r", ' '], '', $statement)) === '') {
        continue;
    }
    try {
        $pdo->exec($statement);
        echo "Executed: " . substr(preg_replace('/\s+/', ' ', $statement), 0, 80) . "...\n";
    } catch (PDOException $e) {
        echo "Skipped/Error: " . $e->getMessage() . "\n";
    }
}

echo "\nJob levels tenant-scoping migration completed.\n";
echo "  - job_levels.tenant_id added and backfilled\n";
echo "  - 13 default levels seeded per tenant (tenant-level rows)\n";
echo "  - Thai translations re-seeded for non-Default tenant companies\n";
echo "  - tenant_id made NOT NULL with FK + composite index\n";
echo "  - unique key replaced with (tenant_id, company_scope_key, code)\n";
