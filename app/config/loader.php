<?php

// Clear PHP's stat cache to help Docker volume mounts discover newly created files
clearstatcache(true);
$loader = new \Phalcon\Autoload\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->setDirectories(
    [
        $config->application->apiControllersDir,
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->traitsDir,
        $config->application->middleWareDir,
        $config->application->servicesDir,
        $config->application->helpersDir
    ]
);

$loader->register();


