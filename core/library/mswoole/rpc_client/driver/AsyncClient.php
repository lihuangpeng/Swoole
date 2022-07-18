<?php

namespace library\mswoole\rpc_client\driver;

use library\mswoole\rpc_client\Driver;
use library\mswoole\rpc_client\Packet;
use Swoole\Coroutine\Client;

class AsyncClient extends Driver
{
    public function __construct($class_name, $config, $debug = false)
    {
        parent::__construct($class_name,$debug,$config);
        $this->client = new Client(SWOOLE_SOCK_TCP);//保持
        if (!empty($this->config['setting'])) $this->client->set($this->config['setting']);
        //尝试重试
        $try_num = 0;
        while ($try_num < $this->config['try']) {
            $try_num++;
            if ($this->client->connect($this->config['host'], $this->config['port'], $this->config['timeout']) === false) {
                $this->client->close();
                continue;
            }
            break;
        }
    }

    protected function send($data, $default = self::DEFAULT_VALUE)
    {
        if (!$this->client->isConnected()) {
            $this->client->close();
            return $default;
        }
        if (!empty($this->setSignFuc)) {
            $data['params'][$this->sign_key] = call_user_func($this->sign_func,$data['params']);
        }

        $data = Packet::length_encode($data);
        $result = $this->client->send($data);
        if($result === false){
            $this->error_info['errCode'] = $this->client->errCode;
            $response = $default;
        }else{
            $response = $this->client->recv($this->config['recv_timeout']);
            if($this->debug) var_dump($response);
            if($response === false){
                $this->error_info['errCode'] = $this->client->errCode;
                $response = $default;
            }else{
                $response = Packet::length_decode($response);
            }
        }
        if($this->debug)  var_dump($this->error_info);
        return $response;
    }
}