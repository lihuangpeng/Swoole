<?php

return [
    'host' => '0.0.0.0',
    'port' => '9501',
    'setting' => [
        'worker_num' => 1,
        'task_worker_num' => 2,
        'daemonize' => false,
        'reload_async' => true,
        'max_waiting_time' => 20,
        'max_request_time' => 20,
        'pid_file' => __DIR__ . '/server.pid',
        'dispatch_mode' => 1, //worker进程调度方式
        'enable_coroutine' => true,
        'task_enable_coroutine' => true, //OnTask回调内部自动创建协程和协程容器
    ],
    'tcp_host' => '0.0.0.0',
    'tcp_port' => '9502',
    'tcp_setting' => [
        'open_length_check' => true,
        'package_max_length' => 5 * 1024 * 1024,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
    ],
    'cache' => [
        'type' => 'complex',
        'default' => [
            'type' => 'redis'
        ],
        'prefix' => 'swoole:',
        'redis' => [
            'default' => [
                'host' => '127.0.0.1',
                'port' => '6379',
                'auth' => '123456',
                'timeout' => '2',
            ],
        ],
        'memcached' => [
            'host' => '127.0.0.1',
            'port' => '11211',
            'user' => '',
            'password' => '',
            'timeout' => '2',
        ]
    ],
    'mysql' => [
        'default' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'user' => 'root',
            'auth' => '123456',
            'db' => 'test',
        ]
    ]
];