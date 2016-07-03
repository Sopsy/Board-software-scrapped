<?php

return [
    'view' => [
        'siteName' => 'Ylis taas vauhdis',
        'siteMotto' => 'Suomalaisen internetkulttuurin sanansaattaja',
        'baseUrl' => '//example.com',
        'staticUrl' => '//static.example.com',
        'uploadMaxSize' => 1048576,
    ],
    'posts' => [
        'subjectMaxLength' => 60,
        'messageMaxLength' => 12000,
    ],
    'files' => [
        'savePath' => ROOT_PATH . '/static/files',
        'diskMinFree' => 1073741824,
        'maxSize' => 1048576,
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
