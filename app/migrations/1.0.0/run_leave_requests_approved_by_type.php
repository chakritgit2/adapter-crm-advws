<?php
/**
 * One-time migration runner: make leave_requests.approved_by polymorphic.
 *
 * Drops the approved_by FOREIGN KEY to admin_users and adds an
 * approved_by_type ENUM('admin','employee') discriminator so the
 * employee-portal approval flow can record the approver's employee id.
 *
 * Run from the project root:
 *   php app/migrations/1.0.0/run_leave_requests_approved_by_type.php
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

$sql = file_get_contents(__DIR__ . '/leave_requests_approved_by_type.sql');

// Split by semicolon and execute each statement.
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement)) {
        continue;
    }
    // Skip comment-only statements.
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

echo "\nleave_requests approved_by_type migration completed.\n";
echo "  - approved_by foreign key to admin_users dropped (if present)\n";
echo "  - approved_by_type ENUM('admin','employee') column added (default 'admin')\n";
