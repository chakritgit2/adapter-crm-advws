<?php
declare(strict_types=1);

use Phalcon\Html\Escaper;
use Phalcon\Flash\Direct as Flash;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Session\Manager as SessionManager;
use Phalcon\Session\Adapter\Stream as SessionStream;
use Phalcon\Mvc\Url as UrlResolver;

//Events manager and Dispatcher
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Dispatcher\Exception as DispatchException;

//Cache
use Phalcon\Storage\Adapter\Stream;
use Phalcon\Storage\SerializerFactory;

// Enable Session
// use Phalcon\Session\Manager;

// ACL
use Phalcon\Acl\Adapter\Memory as AclMemory;
use Phalcon\Acl\Component as AclComponent;
use Phalcon\Acl\Role as AclRole;
use Phalcon\Acl\Enum as AclEnum;

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});


/**
 * Enable Session
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    $session = new SessionManager();
    $stream = new SessionStream([
        'savePath' => sys_get_temp_dir(),
    ]);
    $session->setAdapter($stream)->start();
    return $session;
});

/**
 * AUTH Service
 */
// $di->setShared('auth', function () {
//     $auth = new AuthService($this);
//     return $auth;
// });


/**
 * Middleware
 */
/**
 * Dispatcher with events manager so controller lifecycle hooks
 * (beforeExecuteRoute / afterExecuteRoute) are actually called.
 * In Phalcon 5 the controller is NOT auto-attached as a listener,
 * so we wire it up explicitly here.
 */
$di->setShared('dispatcher', function () {
    $eventsManager = new EventsManager();

    $eventsManager->attach('dispatch:beforeExecuteRoute', function ($event, $dispatcher) {
        $controller = $dispatcher->getActiveController();
        if (is_object($controller) && method_exists($controller, 'beforeExecuteRoute')) {
            return $controller->beforeExecuteRoute($dispatcher);
        }
        return true;
    });

    $dispatcher = new Dispatcher();
    $dispatcher->setEventsManager($eventsManager);

    return $dispatcher;
});


/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
});

/**
 * Setting up the view component
 */
$di->setShared('view', function () {
    $config = $this->getConfig();

    $view = new View();
    $view->setDI($this);
    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines([
        '.volt' => function ($view) {
            $config = $this->getConfig();

            $volt = new VoltEngine($view, $this);

            $volt->setOptions([
                'path' => $config->application->cacheDir,
                'separator' => '_',
                'compiledPath' => $config->application->cacheDir,
                'always' => true, // Disable caching for development
            ]);

            $compiler = $volt->getCompiler();

            // Register the custom function for accessing $_SERVER
            $compiler->addFunction('server', function ($key) {
                return '$_SERVER[' . $key . ']';
            });

            //Register Number format
            $compiler->addFunction('number_format', function ($key) {
                return 'number_format(' . $key . ',".",",")';
            });

            // Register the custom function for accessing urldecode
            $compiler->addFunction('urldecode', function ($key) {
                return 'urldecode(' . $key . ')';
            });

            // Register the custom function for accessing basename
            $compiler->addFunction('basename', function ($key) {
                return 'basename(' . $key . ')';
            });

            // Register the custom function for accessing basename
            $compiler->addFunction(
                'contains_text',
                function ($resolvedArgs, $exprArgs) {
                if (true === function_exists('mb_stripos')) {
                    return 'mb_stripos(' . $resolvedArgs . ')';
                } else {
                    return 'stripos(' . $resolvedArgs . ')';
                }
            }
            );

            //
            $compiler->addFunction('base64_encode', function ($key) {
                return 'base64_encode(' . $key . ')';
            });

            $compiler->addFunction('json_encode', function ($key) {
                return 'json_encode(' . $key . ')';
            });

            // Register the custom function for accessing basename
            $compiler->addFunction('json_encode_beautiful', function ($key) {
                // if (!is_array($key)){
                //     return $key;
                // }
                return 'json_encode(' . $key . ', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);';
            });

            // Add round filter
            $compiler->addFunction('ceil', function ($resolvedArgs) {
                return "ceil($resolvedArgs)";
            });
            
            $compiler->addFunction('floor', function ($resolvedArgs) {
                return "floor($resolvedArgs)";
            });

            $compiler->addFunction('round', function ($resolvedArgs) {
                return "round($resolvedArgs)";
            });

            $compiler->addFunction('format_minutes', function ($resolvedArgs) {
                return 'format_minutes(' . $resolvedArgs . ')';
            });

            // Register the custom function for accessing basename
            $compiler->addFunction('date', function ($key) {
                return 'date("F j, Y",strtotime(' . $key . '))';
            });

            $compiler->addFunction('date_time', function ($key) {
                return 'date("F j, Y H:i",strtotime(' . $key . '))';
            });

            $compiler->addFunction('time', function ($key) {
                return 'date("g:i A",strtotime(' . $key . '))';
            });

            // Register the custom function for accessing basename
            $compiler->addFunction('from_unixtime', function ($key) {
                return 'date("Y-m-d H:i",' . $key . ')';
            });
            $compiler->addFunction('from_unixtime_dateonly', function ($key) {
                return 'date("Y-m-d",' . $key . ')';
            });
            $compiler->addFunction('from_unixtime_timeonly', function ($key) {
                return 'date("H:i",' . $key . ')';
            });

            //
            $compiler->addFunction('http_build_query', function ($key) {
                return 'http_build_query(' . $key . ')';
            });
            //
            $compiler->addFunction('datetimeLocal', function ($key) {
                return 'date("Y-m-d\TH:i",strtotime(' . $key . '))';
            });

            $compiler->addFunction('floor', function ($key) {
                return 'floor(' . $key . ')';
            });

            $compiler->addFunction('bankAccountNumberDisplay', function ($key) {
                return 'substr(' . $key . ', 0, 3) . "-" . substr(' . $key . ', 3, 3) . "-" . substr(' . $key . ', 6)';
            });

            $compiler->addFunction('str_replace', function ($resolvedArgs) {
                return 'str_replace(' . $resolvedArgs . ')';
            });

            $compiler->addFilter('replace', function ($resolvedArgs, $exprArgs) {
                return 'strtr(' . $resolvedArgs . ')';
            });

            $compiler->addFunction('strtotime', function ($resolvedArgs) {
                return 'strtotime(' . $resolvedArgs . ')';
            });

            // UI translation helper (Layer A — interface strings).
            // Usage in Volt:  {{ t('dashboard.title') }}
            //                 {{ t('leave.balance.remaining', ['name': 'Annual']) }}
            $compiler->addFunction('t', function ($resolvedArgs) {
                return '$this->getDI()->get(\'locale\')->t(' . $resolvedArgs . ')';
            });

            // Locale-aware date formatter: {{ date_localized(value) }}
            // Renders in the active UI locale's long format (requires PHP intl;
            // falls back to Y-m-d when intl is unavailable).
            $compiler->addFunction('date_localized', function ($resolvedArgs) {
                return '\\format_date_localized(' . $resolvedArgs . ', $this->getDI()->get(\'locale\')->getLocale())';
            });

            return $volt;
        },
        '.phtml' => PhpEngine::class

    ]);

    return $view;
});


/**
 * Enable Cross Site Request Forgery Protection
 */
$di->setShared('security', function () {
    $security = new Phalcon\Encryption\Security();
    $security->setWorkFactor(12);
    return $security;
});

// $di->setShared('csrf', function () {
//     return new class () {
//         public function generateTokenKey()
//         {
//             return bin2hex(random_bytes(16));
//         }
//         public function generateTokenValue()
//         {
//             return bin2hex(random_bytes(32));
//         }
//     };
// });


/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'charset' => $config->database->charset
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    return new $class($params);
});


/**
 * Locale / UI translation service (Layer A — interface strings)
 *
 * Resolves the active UI language from session.active_language (falling back
 * to the configured default, Thai) and exposes t() for translating message
 * catalog keys. Registered as a shared service so the Volt t() helper and
 * controllers can reach it via $this->locale / $di->get('locale').
 */
$di->setShared('locale', function () {
    return new LocaleService();
});


/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Register the session flash service with the Twitter Bootstrap classes
 */
$di->set('flash', function () {
    $escaper = new Escaper();
    $flash = new Flash($escaper);
    $flash->setImplicitFlush(false);
    $flash->setCssClasses([
        'error' => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice' => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ]);

    return $flash;
});




/**
 * Cache Configuration
 */
$di->setShared('cache', function () {
    // Define cache options
    $options = [
        'defaultSerializer' => 'Json',
        'lifetime' => 7200, // Cache lifetime in seconds
        'storageDir' => BASE_PATH . '/cache/', // Ensure this directory is writable
    ];

    // Create serializer factory
    $serializerFactory = new SerializerFactory();

    // Create the cache adapter
    $adapter = new Stream($serializerFactory, $options);

    return $adapter;
});


$di->setShared('cookies', function () {
    /**
     * By default Phalcon will encrypt cookies if you set a key via
     * $cookies->setKey($yourSecretKey) — you can turn that off with useEncryption(false)
     */
    $config = $this->getConfig();

    $cookies = new Phalcon\Http\Response\Cookies();
    $cookies->setSignKey($config->cookie->signKey);
    // $cookies->useEncryption(false);
    return $cookies;
});


/**
 * API CALLER
 */
$di->setShared('apicaller', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'charset' => $config->database->charset
    ];
    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    $apicaller = new ApiCallerService(new $class($params));
    return $apicaller;
});


/**
 * CURL Service
 */
$di->setShared('curl', function () {
    $curl = new CurlService();
    return $curl;
});

/**
 * Error Service
 */
$di->setShared('errorService', function () {
    $errorService = new ErrorService($this);
    return $errorService;
});

/**
 * Email Service
 */
$di->setShared('emailService', function () {
    $emailService = new EmailService($this);
    return $emailService;
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
    return new AdapterConnectionService($this->getDI()->get('adapterEncryption'));
});

$di->setShared('transformer', function () {
    return new TransformerService();
});


if (!function_exists('format_minutes')) {
    function format_minutes($totalMinutes)
    {
        $isNegative = $totalMinutes < 0;
        $totalMinutes = abs((int) $totalMinutes);
        if ($totalMinutes <= 0) {
            return '0 minutes';
        }
        $days = floor($totalMinutes / 480);
        $hours = floor(($totalMinutes % 480) / 60);
        $minutes = $totalMinutes % 60;
        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' day' . ($days > 1 ? 's' : '');
        }
        if ($hours > 0) {
            $parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        $formatted = empty($parts) ? '0 minutes' : implode(', ', $parts);
        return $isNegative ? '-' . $formatted : $formatted;
    }
}

function vd($ANYTHING, $SHOW_CALLER_FUNCS = false)
{
    $MSG = "";
    if (is_object($ANYTHING) && @get_class($ANYTHING)) {
        listfunc(get_class($ANYTHING));
    }
    if ($SHOW_CALLER_FUNCS == TRUE) {
        $TRACE = debug_backtrace();
        if (isset($TRACE[0]))
            $CALLER = $TRACE[0];
        else
            $CALLER = NULL;
        if (!isset($IS_CLI) || !$IS_CLI) {
            $SPANSTART = "<strong style='color:blue'>";
            $SPANEND = "</strong>";
        }
        $MSG = "{$SPANSTART}[VD] - {$CALLER['file']} - [Line:{$CALLER['line']}]{$SPANEND}";
    }
    echo "<pre>{$MSG} - ";
    var_dump($ANYTHING);
    echo "</pre>";
}