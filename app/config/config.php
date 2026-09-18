<?php

/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config\Config([
    'database' => [
        'adapter'     => 'Mysql',
        'host'        => 'mariadb.cdi-advws',
        'username'    => 'hr',
        'password'    => '[b1dx[MkpKn!NcR/',
        'dbname'      => 'hr',
        'charset'     => 'utf8',
    ],
    'application' => [
        'appDir'            => APP_PATH . '/',
        'apiControllersDir' => APP_PATH . '/controllers/api',
        'controllersDir'    => APP_PATH . '/controllers/',
        'modelsDir'         => APP_PATH . '/models/',
        'middleWareDir'     => APP_PATH . '/middleware/',
        'helpersDir'        => APP_PATH . '/helpers/',
        'traitsDir'         => APP_PATH . '/traits/',
        'servicesDir'       => APP_PATH . '/services/',
        'migrationsDir'     => APP_PATH . '/migrations/',
        'viewsDir'          => APP_PATH . '/views/',
        'pluginsDir'        => APP_PATH . '/plugins/',
        'libraryDir'        => APP_PATH . '/library/',
        'cacheDir'          => BASE_PATH . '/cache/',
        'baseUri'           => '/',
    ],
    
    // App Name
    'appName' => 'HR',
    'appPublicName' => 'ฝ่ายบุคลากร บริษัท Advance Web Service จำกัด (มหาชน)',
    'appDescription' => 'ฝ่ายบุคลากร บริษัท Advance Web Service จำกัด (มหาชน)',
    
    // Login Path
    'loginPath' => '/loginhrm',
    

    // Site API Credentials Encryption
    'encryption' => [
        'credentialsKey' => 'adc-api-credentials-secure-key-2026-change-me',
    ],

    // Site Cookie
    'cookie' => [
        'signKey' => "tpdL(/ldn`Oneh]7S_wNkR6ql-<*Y-Y55SnPx>}3*>`&N9>E2n)wQBb8EmjL&LpSAMTADC"
    ],

    // SMTP2GO Email Service
    'smtp2go' => [
        'api_key' => 'api-707F199DE55F472698EC8AF779AC160F',
        'sender' => 'HR ADVWS <hr@advws.com>',
    ],

    // Google Service Account
    'google' => [
        'service_account_json' => APP_PATH . '/credentials/google-service-account.json',
    ],
   
    // N8N Webhook
    'n8n-webhook' => [
        'url' => 'https://n8n.samtstore.com/webhook/1268b81e-56da-4258-9f9f',
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'text/plain',
            'X-API-Key' => 'GT0kX2Sk92HyhFsXA20o1H2KvKJVA0JGu0',
        ],
    ],
    
    // Microsoft Clarity
    'clarityOn' => false,
    'clarityKey' => '',

    // UDA / external adapter
    'adapter' => [
        // Override this in deployment configuration; do not use the fallback in production.
        'credentialsKey' => getenv('ADAPTER_CREDENTIALS_KEY') ?: 'change-this-adapter-key',
        'maxRows' => 1000,
        'requestTimeout' => 10,
    ],

    // Localization — Polymorphic Translation Ledger
    'localization' => [
        'allowed_targets' => [
            'employees' => ['first_name', 'last_name'],
            'positions' => ['job_title', 'department'],
            'leave_types' => ['name'],
            'overtime_policies' => ['name'],
            'milestone_event_types' => ['name'],
            'job_levels' => ['name', 'category'],
        ],
    ],

    // UI Localization (Layer A — interface strings)
    // Thai is the default UI language; English is the fallback.
    'languages' => [
        'default'  => 'th',
        'fallback' => 'en',
        'supported' => [
            'th' => [
                'name'    => 'ภาษาไทย',
                'english' => 'Thai',
                'locale'  => 'th_TH',
                'dir'     => 'ltr',
            ],
            'en' => [
                'name'    => 'English',
                'english' => 'English',
                'locale'  => 'en_US',
                'dir'     => 'ltr',
            ],
        ],
    ],

]);
