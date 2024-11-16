<?php
return [
    'driver' => 'file',
    'lifetime' => 7200,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => 'sessions',
    'connection' => null,
    'table' => 'sessions',
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => [
        'name' => 'JCSESSID',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax'
    ],
    'path' => 'sessions',
    'gc_maxlifetime' => 7200,
    'gc_probability' => 2,
    'gc_divisor' => 100
];
