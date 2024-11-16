<?php

return [
    // ການເຊື່ອມຕໍ່ເລີ່ມຕົ້ນ
    'default' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'computer_room_management',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'persistent' => false,
    ],

    // ຕົວຢ່າງການເຊື່ອມຕໍ່ອື່ນ
    'test' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'test_database',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'test_',
        'persistent' => false,
    ],
];
