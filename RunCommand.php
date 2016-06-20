<?php
if (php_sapi_name() != "cli") {
    die("This script should only be run from the CLI.\n\n");
}

if (empty($argv[1])) {
    die("Missing controller name\n\n");
}
if (empty($argv[2])) {
    $argv[2] = 'index';
}

$controller = $argv[1];
$command = $argv[2];

if (!empty($argv[3])) {
    define('QUIET', true);
} else {
    define('QUIET', false);
}

// Pre-setup
define('ROOT_PATH', __DIR__);

// Register the autoloader
spl_autoload_register(function ($className) {
    // Not quite working on Linux, so we need some bubble gum.
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    if (is_file(ROOT_PATH . '/' . $className . '.php')) {
        require(ROOT_PATH . '/' . $className . '.php');
    }
});

// Exception handler
set_exception_handler(function (Throwable $e) {
    die(get_class($e) . ': ' . $e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine() . "\n\n");
});

// Set the encoding
mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

// Run
$className = '\YBoard\CliController\\' . $controller;
$cron = new $className();
$cron->$command();
