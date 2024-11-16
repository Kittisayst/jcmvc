<?php
return [
    'cache' => [
        'default' => 'file',
        'prefix' => 'jc_',
        'drivers' => [
            'file' => [
                'class' => 'FileCache',
                'path' => 'cache',
                'ttl' => 3600
            ],
            'redis' => [
                'host' => 'localhost',
                'port' => 6379,
                'timeout' => 0,
                'prefix' => 'jc_cache:'
            ]
        ]
    ],
    'session' => [
        'driver' => 'file',
        'lifetime' => 7200,
        'path' => 'sessions'
    ],
    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => null,
        'password' => null,
        'from' => ['address' => null, 'name' => null]
    ],
    'logger' => [
        'path' => 'logs',
        'default' => 'single',
        'channels' => [
            'single' => [
                'driver' => 'single',
                'path' => 'logs/app.log',
                'level' => 'debug'
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => 'logs/app.log',
                'days' => 14,
                'level' => 'debug'
            ]
        ]
    ]
];
