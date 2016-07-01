<?php

// Routing rules for the router
// URL Regex => array(Controller name, Action name]
return [
    '#^/$#' => ['Index', 'index'],

    // Gold account
    '#^/gold#' => ['GoldAccount', 'index'],

    // Info pages
    '#^/faq#' => ['InfoPages', 'faq'],
    '#^/rules#' => ['InfoPages', 'rules'],
    '#^/about#' => ['InfoPages', 'about'],
    '#^/advertising#' => ['InfoPages', 'advertising'],

    // Log in/-out
    '#^/login$#' => ['LogInOut', 'login'],
    '#^/logout$#' => ['LogInOut', 'logout'],

    // Scripts
    '#^/scripts/post$#' => ['Posts', 'submit'],

    // Boards
    // Checked at the end so other rules override
    '#^/([a-z0-9]+)-?([2-9]|[1-9][0-9]+)?/$#' => ['Board', 'index'],
    '#^/([a-z0-9]+)/([0-9]+)$#' => ['Thread', 'index'],

    // Everything else should just return a 404
    '#.*#' => ['Errors', 'notFound'],
];
