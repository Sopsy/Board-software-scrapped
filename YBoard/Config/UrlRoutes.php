<?php

// Routing rules for the router
// URL Regex => array(Controller name, Action name]
return [
    '#^/$#' => ['Index', 'index'],

    // Gold account
    '#^/gold$#' => ['GoldAccount', 'index'],

    // Info pages
    '#^/faq$#' => ['InfoPages', 'faq'],
    '#^/rules$#' => ['InfoPages', 'rules'],
    '#^/about$#' => ['InfoPages', 'about'],
    '#^/advertising$#' => ['InfoPages', 'advertising'],
    '#^/search$#' => ['Search', 'index'],

    // Preferences
    '#^/preferences$#' => ['Preferences', 'index'],
    '#^/scripts/preferences/save$#' => ['Preferences', 'save'],
    '#^/scripts/preferences/setthemevariation#' => ['Preferences', 'setThemeVariation'],
    '#^/scripts/preferences/togglehidesidebar#' => ['Preferences', 'toggleHideSidebar'],

    // User account related
    '#^/profile/(.+)?$#' => ['User', 'profile'],
    '#^/scripts/user/destroysession$#' => ['User', 'destroySession'],
    '#^/scripts/user/changename$#' => ['User', 'changeName'],
    '#^/scripts/user/changepassword$#' => ['User', 'changePassword'],
    '#^/scripts/user/delete$#' => ['User', 'delete'],

    // Notifications
    '#^/scripts/notifications/get$#' => ['Notifications', 'get'],
    '#^/scripts/notifications/markread$#' => ['Notifications', 'markRead'],
    '#^/scripts/notifications/markallread$#' => ['Notifications', 'markAllRead'],

    // Sign up, log in, log out
    '#^/scripts/session/signup$#' => ['Session', 'signUp'],
    '#^/scripts/session/login$#' => ['Session', 'logIn'],
    '#^/scripts/session/logout$#' => ['Session', 'logOut'],

    // Custom boards
    '#^/mythreads-?([2-9]|[1-9][0-9]+)?/$#' => ['CustomBoard', 'myThreads'],
    '#^/mythreads/catalog-?([2-9]|[1-9][0-9]+)?/$#' => ['CustomBoard', 'myThreadsCatalog'],
    '#^/repliedthreads-?([2-9]|[1-9][0-9]+)?/$#' => ['CustomBoard', 'repliedThreads'],
    '#^/repliedthreads/catalog-?([2-9]|[1-9][0-9]+)?/$#' => ['CustomBoard', 'repliedThreadsCatalog'],
    '#^/followedthreads-?([2-9]|[1-9][0-9]+)?/$#' => ['CustomBoard', 'followedThreads'],
    '#^/followedthreads/catalog-?([2-9]|[1-9][0-9]+)?/$#' => ['CustomBoard', 'followedThreadsCatalog'],
    '#^/hiddenthreads-?([2-9]|[1-9][0-9]+)?/$#' => ['CustomBoard', 'hiddenThreads'],
    '#^/hiddenthreads/catalog-?([2-9]|[1-9][0-9]+)?/$#' => ['CustomBoard', 'hiddenThreadsCatalog'],

    // Post/thread scripts
    '#^/scripts/posts/get$#' => ['Post', 'get'],
    '#^/scripts/posts/redirect/([0-9]+)$#' => ['Post', 'redirect'],
    '#^/scripts/posts/submit$#' => ['Post', 'submit'],
    '#^/scripts/posts/delete$#' => ['Post', 'delete'],
    '#^/scripts/posts/deletefile$#' => ['Post', 'deleteFile'],
    '#^/scripts/threads/getreplies$#' => ['Thread', 'getReplies'],
    '#^/scripts/threads/hide$#' => ['Thread', 'hide'],
    '#^/scripts/threads/restore$#' => ['Thread', 'restore'],
    '#^/scripts/threads/follow$#' => ['Thread', 'follow'],
    '#^/scripts/threads/unfollow$#' => ['Thread', 'unfollow'],
    '#^/scripts/files/upload#' => ['File', 'upload'],
    '#^/scripts/files/getmediaplayer$#' => ['File', 'getMediaPlayer'],
    '#^/scripts/report/getform$#' => ['PostReport', 'getForm'],
    '#^/scripts/report/submit#' => ['PostReport', 'submit'],

    // Boards
    // Checked at the end so other rules override
    '#^/([a-zA-Z0-9åäö]+)-?([2-9]|[1-9][0-9]+)?/$#' => ['Board', 'index'],
    '#^/([a-zA-Z0-9åäö]+)/catalog-?([2-9]|[1-9][0-9]+)?/$#' => ['Board', 'catalog'],
    '#^/([a-zA-Z0-9åäö]+)/([0-9]+)$#' => ['Thread', 'index'],

    // Boards without slash at end
    '#^/([a-zA-Z0-9åäö]+)-?([2-9]|[1-9][0-9]+)?/?(catalog)?-?([2-9]|[1-9][0-9]+)?$#' => ['Board', 'redirect'],

    // Everything else should just return a 404
    '#.*#' => ['Errors', 'notFound'],
];
