<?php
/**
 * One-time migration runner: add employee fields needed for org hierarchy CSV import.
 *
 * Run from the project root:
 *   php app/migrations/1.0.0/run_org_hierarchy_employee_fields.php
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Phalcon\Di\FactoryDefault;
use Phalcon\Config\Config;

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

$sql = file_get_contents(__DIR__ . '/org_hierarchy_employee_fields.sql');

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement)) {
        continue;
    }
    try {
        $pdo->exec($statement);
        echo "Executed: " . substr($statement, 0, 60) . "...\n";
    } catch (PDOException $e) {
        echo "Skipped/Error: " . $e->getMessage() . "\n";
    }
}

echo "Org hierarchy employee fields migration completed.\n";
