<?php

namespace library\mswoole\rpc_client;

abstract class Driver
{
    const DEFAULT_VALUE = null;
    const SIGN_KEY = 'sign';
    protected $client;
    protected $class_name;
    protected $sign_func;
    protected $sign_key;
    protected $debug = false;
    protected $config;
    protected $error_info = [];

    public function __construct($class_name, $debug, $config)
    {
        $this->class_name = $class_name;
        $this->debug = $debug;
        $config['try'] = isset($config['try']) && (int)$config['try'] > 1 ? (int)$config['try'] : 1;
        $this->config = $config;
    }

    public function setSignFuc($sign_func, $sign_key = self::SIGN_KEY)
    {
        $this->sign_func = $sign_func;
        $this->sign_key = $sign_key;
    }

    //请求
    public function call($action_name, $params, $default = self::DEFAULT_VALUE)
    {
        $data = array(
            'c' => $this->class_name,
            'a' => $action_name,
            'params' => $params
        );
        $result = $this->send($data, $default);
        return $result;
    }

    public function getErrorInfo()
    {
        return $this->error_info;
    }

    public function __call($name, $arguments)
    {
        $data = [
            'c' => $this->class_name,
            'a' => $name,
            'params' => $arguments
        ];
        return $this->send($data);
    }

    abstract protected function send($data, $default = self::DEFAULT_VALUE);
}