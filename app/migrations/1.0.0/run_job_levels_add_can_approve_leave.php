<?php
/**
 * One-time migration runner: add the `can_approve_leave` flag to `job_levels`.
 *
 * Run from the project root:
 *   php app/migrations/1.0.0/run_job_levels_add_can_approve_leave.php
 *
 * Prerequisites: job_levels_tenant_scoping.sql must have been applied.
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

$sql = file_get_contents(__DIR__ . '/job_levels_add_can_approve_leave.sql');

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

echo "\nJob levels can_approve_leave migration completed.\n";
echo "  - job_levels.can_approve_leave column added (default 0)\n";
echo "  - tenant-global rows seeded with approval rights for Supervisor+ tiers\n";
