<?php

return [
    'app' => [
        'siteName' => 'Ylis taas vauhdis',
        'siteMotto' => 'Suomalaisen internetkulttuurin sanansaattaja',
        'baseUrl' => '//example.com',
        'staticUrl' => '//static.example.com',
        'hashKey' => 'somelongstringherepls-prefereablyover64chars!',
    ],
    'posts' => [
        'subjectMaxLength' => 60,
        'messageMaxLength' => 12000,
    ],
    'files' => [
        'maxSize' => 1048576,
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
