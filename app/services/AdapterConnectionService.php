<?php

declare(strict_types=1);

use PDO;
use PDOException;

class AdapterConnectionService
{
    private const SQL_ENGINES = ['mysql', 'mariadb', 'pgsql'];

    public function __construct(private CredentialEncryption $encryption)
    {
    }

    public function connect(array $connection): object
    {
        $engine = strtolower((string)($connection['engine'] ?? ''));
        $host = (string)($connection['host'] ?? '');
        $port = (int)($connection['port'] ?? 0);
        $database = (string)($connection['db_name'] ?? '');
        $username = (string)($connection['username'] ?? '');
        $password = $this->decryptPassword($connection['password_ciphertext'] ?? null);

        if ($host === '' || $database === '') {
            throw new InvalidArgumentException('Connection host and database are required.');
        }

        if (in_array($engine, self::SQL_ENGINES, true)) {
            $driver = $engine === 'mariadb' ? 'mysql' : $engine;
            $dsn = $driver === 'pgsql'
                ? sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port ?: 5432, $database)
                : sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port ?: 3306, $database);

            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10,
            ]);
        }

        if ($engine === 'mongodb') {
            if (!class_exists('MongoDB\\Driver\\Manager')) {
                throw new RuntimeException('MongoDB support requires the mongodb PHP extension.');
            }
            $credentials = $username !== '' ? rawurlencode($username) . ':' . rawurlencode($password) . '@' : '';
            $uri = sprintf('mongodb://%s%s:%d', $credentials, $host, $port ?: 27017);
            return new MongoDB\Driver\Manager($uri);
        }

        throw new InvalidArgumentException('Unsupported external database engine.');
    }

    public function test(array $connection): void
    {
        $client = $this->connect($connection);
        if ($client instanceof PDO) {
            $client->query('SELECT 1');
            return;
        }
        $client->executeCommand((string)$connection['db_name'], new MongoDB\Driver\Command(['ping' => 1]))->toArray();
    }

    public function execute(array $connection, string $query, array $parameters): array
    {
        $client = $this->connect($connection);
        if (!$client instanceof PDO) {
            throw new InvalidArgumentException('Use executeMongo() for MongoDB endpoints.');
        }

        $placeholders = $this->placeholders($query);
        foreach ($placeholders as $placeholder) {
            if (!array_key_exists($placeholder, $parameters)) {
                throw new InvalidArgumentException(sprintf('Missing required parameter: %s', $placeholder));
            }
        }
        foreach (array_keys($parameters) as $name) {
            if (!in_array($name, $placeholders, true)) {
                throw new InvalidArgumentException(sprintf('Unexpected query parameter: %s', $name));
            }
        }

        $statement = $client->prepare($query);
        foreach ($placeholders as $placeholder) {
            $value = $parameters[$placeholder];
            $statement->bindValue(':' . $placeholder, is_scalar($value) ? (string)$value : json_encode($value));
        }
        $statement->execute();
        return $statement->fetchAll();
    }

    public function executeMongo(array $connection, array $filter): array
    {
        if (strtolower((string)$connection['engine']) !== 'mongodb') {
            throw new InvalidArgumentException('MongoDB execution requires a MongoDB connection.');
        }
        $client = $this->connect($connection);
        $collection = (string)($filter['_collection'] ?? '');
        unset($filter['_collection']);
        if ($collection === '') {
            throw new InvalidArgumentException('MongoDB endpoints require a collection.');
        }
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery((string)$connection['db_name'] . '.' . $collection, $query);
        return array_map(static fn($row) => json_decode(json_encode($row), true), $cursor->toArray());
    }

    public function placeholders(string $query): array
    {
        preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $query, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    private function decryptPassword(?string $ciphertext): string
    {
        return $ciphertext ? $this->encryption->decrypt($ciphertext) : '';
    }
}
