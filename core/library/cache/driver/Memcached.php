<?php

namespace library\cache\driver;

use library\cache\Driver;
use library\cache\pool\MemcachedPool;
use Swoole\Coroutine;

class Memcached extends Driver
{
    protected $method_arr = [
        'set',
        'get',
        'incr',
        'decr',
        'replace',
        'add',
        'append',
        'prepend'
    ];
    protected $pool;
    protected static $instance = [];

    private function __construct($name = 'default')
    {
        if (MemcachedPool::hasInstance($name)) {
            $this->pool = MemcachedPool::getInstance($name);
        } else {
            throw new \RuntimeException('Memcached Pool ', $name . ' not exists');
        }
    }

    private final function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function getInstance($name = 'default')
    {
        if (empty(self::$instance[$name])) {
            self::$instance[$name] = new self($name);
        }
        return self::$instance[$name];
    }

    public function query($method, $args)
    {
        if (!in_array($method, $this->method_arr)) {
            throw new \RuntimeException("Redis Method {$method} not exists");
        }
        $chan = new \Chan(1);
        Coroutine::create(function () use ($args, $method, $chan) {
            $memcached = null;
            try {
                $memcached = $this->pool->get();
                $res = call_user_func_array([$memcached, $method], $args);
            } catch (\Throwable $e) {
                //可以记录日志
                $res = null;
            }
            $chan->push($res);
            //使用完成放入连接池
            $this->pool->put($memcached);
            unset($res);
            unset($memcached);
        });
        $res = $chan->pop(); //等待结果返回
        $chan->close();
        unset($chan);
        return $res;
    }

    public function __call($method, $args)
    {
        return $this->query($method, $args);
    }
}
