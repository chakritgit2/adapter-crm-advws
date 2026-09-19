<?php

declare(strict_types=1);

class AdapterConnectionService
{
    private const SQL_ENGINES = ['mysql', 'mariadb', 'pgsql'];
    private const MONGO_FORBIDDEN_KEYS = [
        '$accumulator' => true,
        '$changeStream' => true,
        '$collStats' => true,
        '$currentOp' => true,
        '$function' => true,
        '$indexStats' => true,
        '$listLocalSessions' => true,
        '$listSessions' => true,
        '$merge' => true,
        '$out' => true,
        '$planCacheStats' => true,
        '$queryStats' => true,
        '$where' => true,
    ];
    private const MONGO_ALLOWED_STAGES = [
        '$addFields' => true,
        '$bucket' => true,
        '$bucketAuto' => true,
        '$count' => true,
        '$densify' => true,
        '$documents' => true,
        '$facet' => true,
        '$fill' => true,
        '$geoNear' => true,
        '$graphLookup' => true,
        '$group' => true,
        '$limit' => true,
        '$lookup' => true,
        '$match' => true,
        '$project' => true,
        '$redact' => true,
        '$replaceRoot' => true,
        '$replaceWith' => true,
        '$sample' => true,
        '$search' => true,
        '$searchMeta' => true,
        '$set' => true,
        '$setWindowFields' => true,
        '$skip' => true,
        '$sort' => true,
        '$sortByCount' => true,
        '$unionWith' => true,
        '$unset' => true,
        '$unwind' => true,
        '$vectorSearch' => true,
    ];
    private const MONGO_STATIC_VALUE_FIELDS = [
        'as' => true,
        'coll' => true,
        'connectFromField' => true,
        'connectToField' => true,
        'depthField' => true,
        'field' => true,
        'foreignField' => true,
        'from' => true,
        'localField' => true,
        'path' => true,
    ];

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
            $options = [];
            if (!empty($connection['options_json'])) {
                $options = json_decode((string)$connection['options_json'], true) ?: [];
            }
            $credentials = $username !== '' ? rawurlencode($username) . ':' . rawurlencode($password) . '@' : '';
            $uri = sprintf('mongodb://%s%s:%d', $credentials, $host, $port ?: 27017);
            if ($username !== '') {
                $authSource = (string)($options['authSource'] ?? 'admin');
                $uri .= '?authSource=' . rawurlencode($authSource);
            }
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
        $placeholders = $this->placeholders($query);
        $this->assertParameters($placeholders, $parameters);
        $query = $this->normalizeSqlTemplate($query);

        $client = $this->connect($connection);
        if (!$client instanceof PDO) {
            throw new InvalidArgumentException('Use executeMongo() for MongoDB endpoints.');
        }

        $statement = $client->prepare($query);
        foreach ($placeholders as $placeholder) {
            $value = $parameters[$placeholder];
            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException(sprintf('SQL request parameter must be a scalar: %s', $placeholder));
            }
            $statement->bindValue(':' . $placeholder, $value === null ? null : (string)$value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        $statement->execute();
        return $statement->fetchAll();
    }

    public function executeMongo(array $connection, array $filter, array $parameters = []): array
    {
        if (strtolower((string)$connection['engine']) !== 'mongodb') {
            throw new InvalidArgumentException('MongoDB execution requires a MongoDB connection.');
        }
        $collection = $filter['_collection'] ?? '';
        if (!is_string($collection) || trim($collection) === '') {
            throw new InvalidArgumentException('MongoDB endpoints require a collection.');
        }
        if ($this->hasTemplateSyntax($collection)) {
            throw new InvalidArgumentException('MongoDB collection names cannot use request variables.');
        }

        $isAggregate = array_key_exists('_pipeline', $filter);
        if ($isAggregate) {
            if (count($filter) !== 2 || !is_array($filter['_pipeline'])) {
                throw new InvalidArgumentException('MongoDB aggregation templates require only _collection and a _pipeline array.');
            }
            $this->validateMongoPipeline($filter['_pipeline']);
        }

        $placeholders = $this->mongoPlaceholders($filter);
        $this->assertParameters($placeholders, $parameters);
        $filter = $this->resolveMongoTemplate($filter, $parameters);
        $collection = (string)$filter['_collection'];
        unset($filter['_collection']);

        $client = $this->connect($connection);
        if ($isAggregate) {
            $pipeline = $filter['_pipeline'];
            $cursor = $client->executeCommand(
                (string)$connection['db_name'],
                new MongoDB\Driver\Command([
                    'aggregate' => $collection,
                    'pipeline' => $pipeline,
                    'cursor' => new stdClass(),
                ])
            );
        } else {
            $cursor = $client->executeQuery(
                (string)$connection['db_name'] . '.' . $collection,
                new MongoDB\Driver\Query($filter)
            );
        }
        return array_map(static fn($row) => json_decode(json_encode($row), true), $cursor->toArray());
    }

    public function placeholders(string $query): array
    {
        preg_match_all('/(?<!:):([A-Za-z_][A-Za-z0-9_]*)|\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/', $query, $matches, PREG_SET_ORDER);
        $names = [];
        foreach ($matches as $match) {
            $name = !empty($match[1]) ? $match[1] : ($match[2] ?? '');
            if ($name !== '') {
                $names[] = $name;
            }
        }
        return array_values(array_unique($names));
    }

    public function mongoPlaceholders(array $filter): array
    {
        $names = [];
        foreach ($filter as $key => $value) {
            if (is_string($key) && $this->hasTemplateSyntax($key)) {
                throw new InvalidArgumentException(sprintf('MongoDB filter keys cannot use request variables: %s', $key));
            }
            if ($key === '_collection') {
                continue;
            }
            $this->collectMongoPlaceholders($value, $names, (string)$key);
        }
        return array_values(array_unique($names));
    }

    public function validateMongoPipeline(array $pipeline): void
    {
        if ($pipeline === [] || !$this->isList($pipeline)) {
            throw new InvalidArgumentException('MongoDB _pipeline must be a non-empty array of aggregation stages.');
        }
        foreach ($pipeline as $index => $stage) {
            if (!is_array($stage) || count($stage) !== 1) {
                throw new InvalidArgumentException(sprintf('MongoDB pipeline stage %d must contain exactly one stage operator.', $index));
            }
            $stageName = (string)array_key_first($stage);
            if (!isset(self::MONGO_ALLOWED_STAGES[$stageName])) {
                throw new InvalidArgumentException(sprintf('MongoDB pipeline stage is not allowed: %s', $stageName));
            }
            if ($stageName === '$facet') {
                $facets = $stage[$stageName];
                if (!is_array($facets) || $facets === []) {
                    throw new InvalidArgumentException('MongoDB $facet requires at least one pipeline.');
                }
                foreach ($facets as $facetName => $facetPipeline) {
                    if (!is_string($facetName) || trim($facetName) === '' || $this->hasTemplateSyntax($facetName)) {
                        throw new InvalidArgumentException('MongoDB facet names must be static strings.');
                    }
                    if (!is_array($facetPipeline)) {
                        throw new InvalidArgumentException(sprintf('MongoDB facet %s must be a pipeline array.', $facetName));
                    }
                    $this->validateMongoPipeline($facetPipeline);
                }
                continue;
            }
            $this->assertMongoStageValue($stage[$stageName], sprintf('_pipeline[%d].%s', $index, $stageName), $stageName, $stageName === '$match');
        }
    }

    private function assertMongoStageValue($value, string $path, string $stageName = '', bool $allowVariables = false): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $keyName = (string)$key;
                if (is_string($key) && $this->isForbiddenMongoKey($keyName)) {
                    throw new InvalidArgumentException(sprintf('MongoDB operator is not allowed at %s: %s', $path, $keyName));
                }
                if (is_string($key) && $this->hasTemplateSyntax($key)) {
                    throw new InvalidArgumentException(sprintf('MongoDB filter keys cannot use request variables: %s', $path . '.' . $keyName));
                }
                if (!$allowVariables && isset(self::MONGO_STATIC_VALUE_FIELDS[$keyName])) {
                    $this->assertStaticMongoValue($item, $path . '.' . $keyName);
                    continue;
                }
                if ($keyName === 'pipeline' && $stageName !== '' && in_array($stageName, ['$lookup', '$unionWith'], true)) {
                    if (!is_array($item)) {
                        throw new InvalidArgumentException(sprintf('MongoDB nested pipeline must be an array: %s', $path . '.pipeline'));
                    }
                    $this->validateMongoPipeline($item);
                    continue;
                }
                $childAllowsVariables = $allowVariables || in_array($keyName, ['filter', 'let', 'query', 'restrictSearchWithMatch'], true);
                if (in_array($keyName, ['$expr', '$jsonSchema', '$regex', '$regularExpression'], true)) {
                    $childAllowsVariables = false;
                }
                $this->assertMongoStageValue($item, $path . '.' . $keyName, '', $childAllowsVariables);
            }
            return;
        }
        if (is_string($value) && $this->hasTemplateSyntax($value) && !$allowVariables) {
            throw new InvalidArgumentException(sprintf('MongoDB request variables are only allowed in filter values: %s', $path));
        }
        if ($stageName === '$unionWith' && (!is_string($value) || trim($value) === '' || $this->hasTemplateSyntax($value))) {
            throw new InvalidArgumentException(sprintf('MongoDB collection reference must be static: %s', $path));
        }
    }

    private function assertStaticMongoValue($value, string $path): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->assertStaticMongoValue($item, $path);
            }
            return;
        }
        if (!is_string($value) || trim($value) === '' || $this->hasTemplateSyntax($value)) {
            throw new InvalidArgumentException(sprintf('MongoDB field or collection reference must be static: %s', $path));
        }
    }

    private function isForbiddenMongoKey(string $key): bool
    {
        return isset(self::MONGO_FORBIDDEN_KEYS[$key]) || str_starts_with($key, '$internal');
    }

    private function isList(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    private function assertParameters(array $placeholders, array $parameters): void
    {
        foreach (['apikey', '_url'] as $reserved) {
            if (in_array($reserved, $placeholders, true)) {
                throw new InvalidArgumentException(sprintf('Reserved query parameter cannot be used as a variable: %s', $reserved));
            }
        }
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
    }

    private function normalizeSqlTemplate(string $query): string
    {
        $normalized = preg_replace_callback(
            '/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/',
            static fn(array $match): string => ':' . $match[1],
            $query
        );
        if ($this->hasTemplateSyntax((string)$normalized)) {
            throw new InvalidArgumentException('Invalid query variable syntax.');
        }
        return (string)$normalized;
    }

    private function collectMongoPlaceholders($value, array &$names, string $path): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($key) && $this->hasTemplateSyntax($key)) {
                    throw new InvalidArgumentException(sprintf('MongoDB filter keys cannot use request variables: %s', $path . '.' . $key));
                }
                $this->collectMongoPlaceholders($item, $names, $path . '.' . $key);
            }
            return;
        }
        if (is_string($value)) {
            $found = $this->templateVariableNames($value);
            if (!$found && $this->hasTemplateSyntax($value)) {
                throw new InvalidArgumentException(sprintf('Invalid MongoDB query variable syntax: %s', $path));
            }
            foreach ($found as $name) {
                $names[] = $name;
            }
        }
    }

    private function resolveMongoTemplate($value, array $parameters)
    {
        if (is_array($value)) {
            $resolved = [];
            foreach ($value as $key => $item) {
                $resolved[$key] = $this->resolveMongoTemplate($item, $parameters);
            }
            return $resolved;
        }
        if (!is_string($value) || !$this->hasTemplateSyntax($value)) {
            return $value;
        }

        $names = $this->templateVariableNames($value);
        if (!$names) {
            throw new InvalidArgumentException('Invalid MongoDB query variable syntax.');
        }
        if (count($names) === 1 && preg_match('/^\{\{\s*' . preg_quote($names[0], '/') . '\s*\}\}$/', $value)) {
            return $this->mongoParameterValue($parameters[$names[0]]);
        }
        $resolved = preg_replace_callback(
            '/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/',
            function (array $match) use ($parameters): string {
                $value = $parameters[$match[1]];
                if (!is_scalar($value) && $value !== null) {
                    throw new InvalidArgumentException(sprintf('MongoDB parameter must be a scalar: %s', $match[1]));
                }
                if (is_string($value) && str_starts_with(ltrim($value), '$')) {
                    throw new InvalidArgumentException(sprintf('MongoDB request parameter values cannot start with $: %s', $match[1]));
                }
                return (string)$value;
            },
            $value
        );
        if (!is_string($resolved) || $this->hasTemplateSyntax($resolved)) {
            throw new InvalidArgumentException('Invalid MongoDB query variable syntax.');
        }
        return $resolved;
    }

    private function mongoParameterValue($value)
    {
        if (is_array($value)) {
            if (!$this->isList($value)) {
                throw new InvalidArgumentException('MongoDB array parameters must be sequential lists.');
            }
            $resolved = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    throw new InvalidArgumentException('MongoDB array parameters may only contain scalar values.');
                }
                $resolved[] = $this->mongoParameterValue($item);
            }
            return $resolved;
        }
        if (!is_scalar($value) && $value !== null) {
            throw new InvalidArgumentException('MongoDB request parameters must be scalar values or lists.');
        }
        if (!is_string($value)) {
            return $value;
        }
        $trimmed = trim($value);
        if (str_starts_with($trimmed, '$')) {
            throw new InvalidArgumentException('MongoDB request parameter values cannot start with $.');
        }
        $lower = strtolower($trimmed);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }
        if ($lower === 'null') {
            return null;
        }
        if (preg_match('/^-?(0|[1-9][0-9]*)$/', $trimmed)) {
            return (int)$trimmed;
        }
        if (preg_match('/^-?(?:[0-9]+\.[0-9]+|\.[0-9]+)$/', $trimmed)) {
            return (float)$trimmed;
        }
        return $value;
    }

    private function templateVariableNames(string $value): array
    {
        preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/', $value, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    private function hasTemplateSyntax(string $value): bool
    {
        return str_contains($value, '{{') || str_contains($value, '}}');
    }

    private function decryptPassword(?string $ciphertext): string
    {
        return $ciphertext ? $this->encryption->decrypt($ciphertext) : '';
    }
}
