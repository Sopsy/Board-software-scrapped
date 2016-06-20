<?php
namespace YFW;

use YFW\Exception\InternalException;
use YFW\Library\Router;

class Bootstrap
{
    protected $errorPage;
    protected $appName;

    public function __construct()
    {
        // Define a few paths to be used later on
        // PUBLIC_PATH defined on index.php
        define('ROOT_PATH', dirname(__DIR__));
        define('APP_URL', '//' . $_SERVER['HTTP_HOST']);

        // Set exception handler as soon as possible, so it can catch all errors
        $this->setExceptionHandlers();

        // Determine the environment from the web server setting
        // In case it's not set or is unknown/unsupported, default to production.
        $appEnv = strtolower(getenv('APPLICATION_ENVIRONMENT'));
        if (!$appEnv OR !in_array($appEnv, ['production', 'development'])) {
            $appEnv = 'production';
        }
        define('APP_ENV', $appEnv);

        // Register the autoloader
        spl_autoload_register(function ($className) {
            // Not quite working on Linux, so we need some bubble gum.
            $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            require(ROOT_PATH . '/' . $className . '.php');
        });
    }

    public function setErrorPage(string $path): void
    {
        if (!is_file(ROOT_PATH . '/' . $path . '.phtml')) {
            throw new InternalException('Invalid error page: ' . $path);
        }

        $this->errorPage = $path;
    }

    public function run(string $appName): void
    {
        $routes = require(ROOT_PATH . '/' . $appName . '/Config/UrlRoutes.php');

        $router = new Router();
        $router->setAppName($appName);
        $router->setRoutes($routes);

        $router->route($_SERVER['REQUEST_URI']);
    }

    protected function setEncoding(): void
    {
        // Set the encoding
        mb_internal_encoding('UTF-8');
        date_default_timezone_set('UTC');
    }

    protected function setExceptionHandlers(): void
    {
        set_exception_handler(function (\Throwable $e) {
            include(ROOT_PATH . '/' . $this->errorPage . '.phtml');

            error_log(get_class($e) . ': ' . $e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
            die();
        });

        register_shutdown_function(function() {
            $error = error_get_last();
            if($error !== NULL && $error['type'] === E_ERROR) {
                include(ROOT_PATH . '/' . $this->errorPage . '.phtml');
            }
        });
    }
}
