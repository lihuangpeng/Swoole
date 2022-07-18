<?php
return [
    'http' => [
        'secret' => '',
        'namespace' => '\\App\\HttpController\\',
        'postfix' => 'Controller',
        'default_controller' => 'index',
        'default_action' => 'index',
    ],
    'tcp' => [
        'secret' => '',
        'namespace' => '\\App\\TcpController\\',
        'postfix' => 'Controller',
        'default_controller' => 'index',
        'default_action' => 'index',
    ],
    'rpc' => [
        'center'=> [
            'secret' => '',
            'host' => '127.0.0.1',
            'port' => '9502',
            'timeout' => 0.5,
            'recv_timeout' => 3,
            'setting' => [
                'open_length_check' => true,
                'package_max_length' => 5 * 1024 * 1024,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
            ]
        ]
    ]
];