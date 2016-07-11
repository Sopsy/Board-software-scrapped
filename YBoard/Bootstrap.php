<?php
// Determine the environment from the web server setting
// In case it's not set or is unknown/unsupported, default to production.
$appEnv = strtolower(getenv('APPLICATION_ENVIRONMENT'));
if (!$appEnv OR !in_array($appEnv, ['production', 'development'])) {
    $appEnv = 'production';
}
define('APP_ENV', $appEnv);

// Define a few paths to be used later on
// PUBLIC_PATH defined on index.php
define('ROOT_PATH', dirname(__DIR__));
define('APP_URL', '//' . $_SERVER['HTTP_HOST']);

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
    $auxMessage = false;
    if (get_class($e) == 'YBoard\Exceptions\DatabaseException') {
        $auxMessage = $e->getMessage();
    }
    $c = new \YBoard\Controller\BasicErrors();
    $c->showException(false, false, $auxMessage);

    error_log(get_class($e) . ': ' . $e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
    die();
});

// Set the encoding
mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

// Remove query string from route
$rawRequestUrl = preg_replace('/\?.*/i', '', $_SERVER['REQUEST_URI']);

// Route the request
$routes = require('Config/UrlRoutes.php');
foreach ($routes AS $routeUrl => $routeTo) {
    if (preg_match($routeUrl, rawurldecode($rawRequestUrl), $routeMatches)) {
        array_shift($routeMatches);
        $controller = '\YBoard\Controller\\' . $routeTo[0];
        $controller = new $controller();
        call_user_func_array([$controller, $routeTo[1]], $routeMatches);
        break;
    }
}

// No route found
if (!isset($controller)) {
    throw new \Exception('No routes found for the requested URL: ' . $rawRequestUrl);
}
