<?php
namespace YFW\Library;

use YFW\Exception\RouterException;

class Router
{
    protected $routes = [];
    protected $appName;

    public function route(string $requestUrl): bool
    {
        if (empty($this->routes)) {
            throw new RouterException('No routes found.');
        }

        if (empty($this->appName)) {
            throw new RouterException('App name missing.');
        }

        // Remove query string from route
        $rawRequestUrl = preg_replace('/\?.*/i', '', $requestUrl);

        // Route the request
        foreach ($this->routes AS $routeUrl => $routeTo) {
            if (preg_match($routeUrl, rawurldecode($rawRequestUrl), $routeMatches)) {
                array_shift($routeMatches);
                $controller = '\\' . $this->appName . '\Controller\\' . $routeTo[0];
                $controller = new $controller();
                call_user_func_array([$controller, $routeTo[1]], $routeMatches);

                return true;
            }
        }

        // No route found
        throw new RouterException('No routes found for the requested URL: ' . $rawRequestUrl);
    }

    public function setAppName(string $appName): bool
    {
        $this->appName = $appName;

        return true;
    }

    public function setRoutes(array $routes): bool
    {
        $this->routes = $routes;

        return true;
    }
}
