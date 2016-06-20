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

// Set the encoding
mb_internal_encoding('UTF-8');
// Timezone will be moved to i18n later
date_default_timezone_set('Europe/Helsinki');

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

// Debug: Execution time and memory usage
echo '<!-- ',
    round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])*1000, 2), ' ms ',
    round(memory_get_usage()/1024/1024, 2) . ' MB',
    ' -->';
