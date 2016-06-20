<?php

// Routing rules for the router
// URL Regex => array(Controller name, Action name]
return [
    '#^/$#' => ['Index', 'index'],

    // Gold account
    '#^/gold$#' => ['GoldAccount', 'index'],

    // Info pages
    '#^/info/faq$#' => ['InfoPages', 'faq'],
    '#^/info/rules$#' => ['InfoPages', 'rules'],
    '#^/info/about$#' => ['InfoPages', 'about'],
    '#^/info/advertising$#' => ['InfoPages', 'advertising'],
    '#^/search$#' => ['Search', 'index'],

    // Preferences
    '#^/preferences$#' => ['Preferences', 'index'],

    // User account related
    '#^/profile/?(.+)?$#' => ['Profile', 'index'],

    // Custom boards
    '#^/((my|replied|followed|hidden)threads)(-([2-9]|[1-9][0-9]+))?/$#' => ['CustomBoard', 'index'],
    '#^/((my|replied|followed|hidden)threads)/catalog-?([2-9]|[1-9][0-9]+)?$#' => ['CustomBoard', 'catalog'],

    // Mod
    '#^/scripts/mod/banform$#' => ['Mod', 'banForm'],
    '#^/scripts/mod/addban$#' => ['Mod', 'addBan'],
    '#^/scripts/mod/reports/setchecked$#' => ['PostReport', 'setChecked'],
    '#^/mod/reports$#' => ['PostReport', 'uncheckedReports'],

    // Post reporting
    '#^/scripts/report/getform$#' => ['PostReport', 'getForm'],
    '#^/post-([0-9]+)$#' => ['Post', 'redirect'],

    // API
    //-----
    '#^/api/user/create$#' => ['Api\User', 'create'],
    '#^/api/user/delete$#' => ['Api\User', 'delete'],
    '#^/api/user/changename$#' => ['Api\User', 'changeName'],
    '#^/api/user/changepassword$#' => ['Api\User', 'changePassword'],
    '#^/api/user/preferences/set$#' => ['Api\UserPreferences', 'set'],

    // Sign up, log in, log out
    '#^/api/user/session/create$#' => ['Api\UserSession', 'create'],
    '#^/api/user/session/delete$#' => ['Api\UserSession', 'delete'],

    // User specific thread functions
    '#^/api/user/thread/follow/create$#' => ['Api\UserThreadFollow', 'create'],
    '#^/api/user/thread/follow/delete$#' => ['Api\UserThreadFollow', 'delete'],
    '#^/api/user/thread/follow/markread$#' => ['Api\UserThreadFollow', 'markRead'],
    '#^/api/user/thread/follow/markallread$#' => ['Api\UserThreadFollow', 'markAllRead'],
    '#^/api/user/thread/hide/create$#' => ['Api\UserThreadHide', 'create'],
    '#^/api/user/thread/hide/delete$#' => ['Api\UserThreadHide', 'delete'],

    // Notifications
    '#^/api/user/notification/getall$#' => ['Api\UserNotification', 'getAll'],
    '#^/api/user/notification/markread$#' => ['Api\UserNotification', 'markRead'],
    '#^/api/user/notification/markallread$#' => ['Api\UserNotification', 'markAllRead'],

    // File scripts
    '#^/api/file/create#' => ['Api\File', 'create'],
    '#^/api/file/delete$#' => ['Api\File', 'delete'],
    '#^/api/file/getmediaplayer$#' => ['Api\File', 'getMediaPlayer'],

    // Posts
    '#^/api/post/get$#' => ['Api\Post', 'get'],
    '#^/api/post/create$#' => ['Api\Post', 'create'],
    '#^/api/post/delete$#' => ['Api\Post', 'delete'],
    '#^/api/post/deletefile$#' => ['Api\Post', 'deleteFile'],
    '#^/api/post/report$#' => ['Api\Post', 'report'],

    // Threads
    '#^/api/thread/getreplies$#' => ['Api\Thread', 'getReplies'],
    '#^/api/thread/update$#' => ['Api\Thread', 'update'],

    // Boards
    //--------
    // Checked at the end so other rules override
    '#^/([a-zA-Z0-9åäö]+)(-?([2-9]|[1-9][0-9]+))?/$#' => ['Board', 'index'],
    '#^/([a-zA-Z0-9åäö]+)/catalog(-?([2-9]|[1-9][0-9]+))?$#' => ['Board', 'catalog'],
    '#^/([a-zA-Z0-9åäö]+)/([0-9]+)$#' => ['Thread', 'index'],

    // Boards without slash at end
    '#^/([a-zA-Z0-9åäö]+)(-?([2-9]|[1-9][0-9]+))?$#' => ['Board', 'redirect'],

    // Everything else should just return a 404
    '#.*#' => ['Errors', 'notFound'],
];
