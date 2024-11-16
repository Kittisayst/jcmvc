<?php
return [
    'default' => 'file',
    'prefix' => 'jc_',
    'ttl' => 3600,
    'path' => 'cache',
    'serializer' => 'php',
    'compression' => false,
    'gc_probability' => 2,
    'gc_divisor' => 100,
    'file_permission' => 0644,
    'dir_permission' => 0755,
    'umask' => 0000,
    'throw_exceptions' => true,
    'file_extensions' => [
        'data' => '.cache',
        'temp' => '.tmp'
    ],
    'drivers' => [
        'file' => [
            'path' => 'cache/data'
        ],
        'redis' => [
            'host' => 'localhost',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'timeout' => 0,
            'prefix' => 'jc_cache:'
        ]
    ]
];
