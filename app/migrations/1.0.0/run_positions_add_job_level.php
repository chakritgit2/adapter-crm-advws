<?php
/**
 * One-time migration runner: add job_level column to positions table.
 *
 * Run from the project root:
 *   php app/migrations/1.0.0/run_positions_add_job_level.php
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

$sql = file_get_contents(__DIR__ . '/positions_add_job_level.sql');

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

echo "Positions job_level migration completed.\n";
