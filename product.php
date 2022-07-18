<?php

return [
    'host' => '0.0.0.0',
    'port' => '9501',
    'setting' => [
        'worker_num' => 1,
        'task_worker_num' => 1,
        'daemonize' => false,
        'reload_async' => true,
        'max_waiting_time' => 5,
        'pid_file' => __DIR__.'/server.pid'
    ],
    'cache' => [
        'type' => 'complex',
        'default' => [
            'type' => 'redis'
        ],
        'redis' => [
            'type' => 'redis',
            'host' => '127.0.0.1',
            'port' => '6379',
            'auth' => '123456',
            'timeout' => '2',
        ],
        'memcached' => [
            'type' => 'memcached',
            'host' => '127.0.0.1',
            'port' => '11211',
            'user' => '',
            'password' => '',
            'timeout' => '3',
        ]
    ]
];