<?php

return [
    'view' => [
        'siteName' => 'Ylis taas vauhdis',
        'siteMotto' => 'Suomalaisen internetkulttuurin sanansaattaja',
        'baseUrl' => '//example.com',
        'staticUrl' => '//static.example.com',
        'uploadMaxSize' => 10485760,
        'maxPages' => 100,
        'maxCatalogPages' => 5,
        'previewPosts' => 3,
        'subjectMaxLength' => 60,
        'messageMaxLength' => 12000,
        'acceptedFileTypes' => '.jpg,.png,.gif,.mp3,.mp4,.webm',
        'gCsePartnerPub' => 'partner-pub-1234567890:1234567890', // Google CSE
        'usernameMaxLength' => 30,
    ],
    'posts' => [
        'replyIntervalLimit' => 5,
        'threadIntervalLimit' => 30,
    ],
    'files' => [
        'savePath' => ROOT_PATH . '/static/files',
        'diskMinFree' => 1073741824,
        'maxSize' => 10485760,
        'maxPixelCount' => 50000000,
        'imgMaxWidth' => 1920,
        'imgMaxHeight' => 1920,
        'thumbMaxWidth' => 240,
        'thumbMaxHeight' => 240,
    ],
    'i18n' => [
        'defaultLocale' => 'fi_FI.UTF-8', // Used as a fallback if autodetect fails
        'defaultTimezone' => 'Europe/Helsinki',
    ],
    'reCaptcha' => [
        'publicKey' => 'xxx',
        'privateKey' => 'xxx',
    ],
];
