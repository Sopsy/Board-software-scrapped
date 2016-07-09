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

    // Preferences
    '#^/preferences#' => ['Preferences', 'index'],
    '#^/scripts/preferences/save#' => ['Preferences', 'save'],
    '#^/scripts/preferences/toggledarktheme#' => ['Preferences', 'toggleDarkTheme'],

    // Log in/-out
    '#^/scripts/user/login$#' => ['LogInOut', 'login'],
    '#^/scripts/user/logout$#' => ['LogInOut', 'logout'],

    // Post/thread scripts
    '#^/scripts/posts/get#' => ['Post', 'get'],
    '#^/scripts/posts/redirect/([0-9]+)#' => ['Post', 'redirect'],
    '#^/scripts/posts/submit$#' => ['Post', 'submit'],
    '#^/scripts/posts/delete$#' => ['Post', 'delete'],
    '#^/scripts/threads/getreplies#' => ['Thread', 'getReplies'],

    // Boards
    // Checked at the end so other rules override
    '#^/([a-zA-Z0-9åäö]+)-?([2-9]|[1-9][0-9]+)?/$#' => ['Board', 'index'],
    '#^/([a-zA-Z0-9åäö]+)/([0-9]+)$#' => ['Thread', 'index'],

    // Boards without slash at end
    '#^/([a-zA-Z0-9åäö]+)-?([2-9]|[1-9][0-9]+)?$#' => ['Board', 'redirect'],

    // Everything else should just return a 404
    '#.*#' => ['Errors', 'notFound'],
];
