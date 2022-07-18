<?php

namespace library\mswoole;

use library\Loader;
use library\facade\Config;

/**
 * 依据业务需求，通过php-fpm请求之后使用同步客户端
 * Class RpcClient
 * @package library\mswoole
 * @method \library\mswoole\rpc_client\Driver.php call
 */
class RpcClient
{
    protected $config = [
        'secret' => '',
        'host' => '127.0.0.1',
        'port' => '9502',
        'timeout' => 0.5,
        'recv_timeout' => 3,
        'setting' => [
            'open_length_check' => true,
            'package_max_length' => 81920,
            'package_length_type' => 'N', //see php pack()
            'package_length_offset' => 0,
            'package_body_offset' => 4,
        ]
    ];
    protected $client;
    protected $async;
    protected $class_name;
    protected $debug;

    public function __construct($class_name, $async = 0,$debug = false,$project = 'center')
    {
        $this->async = $async;
        $this->class_name = $class_name;
        $this->debug = $debug;
        if(Config::has('app.rpc.'.$project)){
            $this->config = Config::get('app.rpc.' . $project);
        }
        $this->init();
    }

    protected function init()
    {
        $this->client = Loader::factory(($this->async ? 'AsyncClient' : 'SyncClient'), '\\library\\mswoole\\rpc_client\\driver\\',$this->class_name,$this->config,$this->debug);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->client, $name], $arguments);
    }

}