<?php

// Routing rules for the router
// URL Regex => array(Controller name, Action name]
return [
    '#^/$#' => ['Index', 'index'],

    '#^/login$#' => ['LogInOut', 'login'],
    '#^/logout$#' => ['LogInOut', 'logout'],

    '#^/([a-z0-9]+)-?([2-9]|[1-9][0-9]+)?/$#' => ['Board', 'index'],
    '#^/([a-z0-9]+)$#' => ['InfoPages', 'index'],

    '#.*#' => ['Errors', 'notFound'], // Everything else should just return a 404
];
