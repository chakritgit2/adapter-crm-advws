<?php

use Phalcon\Cli\Console;
use Phalcon\Di\FactoryDefault\Cli;
use Phalcon\Autoload\Loader;

require __DIR__ . '/config/config.php';

include_once BASE_PATH . '/vendor/autoload.php';

$di = new Cli();

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'charset'  => $config->database->charset
    ];
    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }
    return new $class($params);
});

$di->setShared('adapterEncryption', function () {
    $config = $this->getConfig();
    $key = (string)$config->path('adapter.credentialsKey', '');
    if ($key === '' || $key === 'change-this-adapter-key') {
        throw new RuntimeException('ADAPTER_CREDENTIALS_KEY must be configured before using the adapter.');
    }
    return new CredentialEncryption($key);
});

$di->setShared('adapterConnections', function () {
    return new AdapterConnectionService($this->get('adapterEncryption'));
});

$di->setShared('transformer', function () {
    return new TransformerService();
});


// Register the tasks directory
/*IF THERE IS A PROBLEM VAR_DUMP THE LOADER */
$loader = new Loader();
$loader->setDirectories([
    __DIR__ . '/tasks/', 
    __DIR__ . '/models/',
    __DIR__ . '/services/',
    __DIR__ . '/helpers/',
]);
$loader->setNamespaces([
    'App\Helpers' => BASE_PATH . '/app/helpers/',
]);
$loader->register();


$di->setShared('errorService', function () {
    return new ErrorService($this);
});

$di->setShared('emailService', function () {
    return new EmailService($this);
});


// Register the application
$console = new Console($di);

$arguments = array_slice($argv, 1);
$params = array_slice($arguments, 2);

$arguments = [
    'task' => $arguments[0] ?? null,
    'action' => $arguments[1] ?? null,
    'params' => $params
];

// Handle the CLI request
try {
    $console->handle($arguments);
} catch (Phalcon\Cli\Dispatcher\Exception $e) {
    echo "Error: ", $e->getMessage(), PHP_EOL;
}
