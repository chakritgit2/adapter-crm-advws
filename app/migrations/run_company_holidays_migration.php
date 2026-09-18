<?php

/**
 * One-time migration runner: create the company_holidays table.
 *
 * Run from the project root:
 *   php app/migrations/run_company_holidays_migration.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqlFile = __DIR__ . '/company_holidays.sql';
$sql = file_get_contents($sqlFile);

// Split into individual statements and execute them safely
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement)) {
        continue;
    }
    $pdo->exec($statement);
    echo "Executed: " . substr(str_replace(["\n", "\r"], ' ', $statement), 0, 60) . "...\n";
}

echo "Migration completed.\n";
