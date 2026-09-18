<?php

/**
 * One-time migration runner: add workday_hours column to leave_types table.
 *
 * Run from the project root:
 *   php app/migrations/run_workday_hours_migration.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqlFile = __DIR__ . '/leave_types_add_workday_hours.sql';
$sql = file_get_contents($sqlFile);

$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement)) {
        continue;
    }
    $pdo->exec($statement);
    echo "Executed: " . substr(str_replace(["\n", "\r"], ' ', $statement), 0, 60) . "...\n";
}

echo "Migration completed.\n";
