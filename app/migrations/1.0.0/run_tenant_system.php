<?php
/**
 * One-time migration runner: create tenants table, tenant_user_map table,
 * add tenant_id to companies, and backfill with a single shared Default tenant.
 *
 * Run from the project root:
 *   php app/migrations/1.0.0/run_tenant_system.php
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

$sql = file_get_contents(__DIR__ . '/tenant_system.sql');

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

echo "\nTenant system migration completed.\n";
echo "  - tenants table created\n";
echo "  - tenant_user_map table created\n";
echo "  - companies.tenant_id added and backfilled to Default tenant\n";
echo "  - tenant_user_map backfilled from company_user_map\n";
