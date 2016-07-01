<?php

// Routing rules for the router
// URL Regex => array(Controller name, Action name]
return [
    '#^/$#' => ['Index', 'index'],
    '#^/faq#' => ['InfoPages', 'faq'],
    '#^/rules#' => ['InfoPages', 'rules'],
    '#^/about#' => ['InfoPages', 'about'],
    '#^/advertising#' => ['InfoPages', 'advertising'],

    '#^/login$#' => ['LogInOut', 'login'],
    '#^/logout$#' => ['LogInOut', 'logout'],

    '#^/([a-z0-9]+)-?([2-9]|[1-9][0-9]+)?/$#' => ['Board', 'index'],

    '#.*#' => ['Errors', 'notFound'], // Everything else should just return a 404
];
