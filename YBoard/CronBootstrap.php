<?php
define('ROOT_PATH', dirname(__DIR__));

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
$className = '\YBoard\Cron\\' . $command;
$cron = new $className();
$cron->runJob();

// Why not?
echo "\n";
