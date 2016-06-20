<?php

return [
    'app' => [
        'name' => 'YBoard',
        'motto' => 'On speed again',
        'logo' => '/img/norppa_ylilauta.svg', // relative to staticUrl
    ],
    'url' => [
        // no trailing slash
        'public' => '//localhost:9001',
        'static' => '//localhost:9001/static',
        'files' => '//localhost:9001/files',
    ],
    'view' => [
        'maxPages' => 100,
        'maxCatalogPages' => 10,
        'previewPosts' => 3,
        'defaultTheme' => 'ylilauta',
    ],
    'themes' => [
        'ylilauta' => [
            'name' => 'Ylilauta',
            'light' => '/css/ylilauta.css', // relative to staticUrl
            'dark' => '/css/ylilauta_dark.css', // relative to staticUrl
        ],
    ],
    'user' => [
        'usernameMaxLength' => 30,
    ],
    'post' => [
        'maxNewlines' => 100,
        'subjectMaxLength' => 60,
        'messageMaxLength' => 12000,
        'replyIntervalLimit' => 5,
        'threadIntervalLimit' => 30,
    ],
    'file' => [
        'savePath' => ROOT_PATH . '/files', // no trailing slash
        'diskMinFree' => 1073741824,
        'maxSize' => 10485760,
        'maxPixelCount' => 50000000,
        'imgMaxWidth' => 1920,
        'imgMaxHeight' => 1920,
        'thumbMaxWidth' => 240,
        'thumbMaxHeight' => 240,
        'acceptedTypes' => '.jpg,.png,.gif,.m4a,.aac,.mp3,.mp4,.webm',
    ],
    'i18n' => [
        'defaultLocale' => 'fi_FI.UTF-8', // Used as a fallback if autodetect fails
    ],
    'search' => [
        'enabled' => false,
        'gCsePartnerPub' => 'partner-pub-1234567890:1234567890', // Google CSE
    ],
    'captcha' => [
        'enabled' => false,
        'requiredPosts' => 1, // Required posts to disable captcha, int or true
        'publicKey' => 'xxx',
        'privateKey' => 'xxx',
    ],
    'ip2location' => [
        'enabled' => true,
        'apiFile' => ROOT_PATH . '/YBoard/Vendor/IP2Location/ip2location.php',
        'v4Database' => ROOT_PATH . '/YBoard/Vendor/IP2Location/IP2LOCATION-LITE-DB1.BIN',
        'v6Database' => ROOT_PATH . '/YBoard/Vendor/IP2Location/IP2LOCATION-LITE-DB1.IPV6.BIN',
    ],
];
