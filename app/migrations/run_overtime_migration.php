<?php
/**
 * One-time migration runner: create overtime tables and add is_toil flag.
 *
 * Run from the project root:
 *   php app/migrations/run_overtime_migration.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Phalcon\Di\FactoryDefault;
use Phalcon\Config\Config;

$di = new FactoryDefault();

$config = require __DIR__ . '/../config/config.php';
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

$sql = file_get_contents(__DIR__ . '/overtime_system.sql');

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

echo "Overtime migration completed.\n";
