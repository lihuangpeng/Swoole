<?php

namespace library\cache\driver;

use library\cache\Driver;
use library\cache\pool\RedisPool;
use Swoole\Coroutine;

class Redis extends Driver
{
    protected $string_method = [
        'get',
        'set',
        'setex',
        'psetex',
        'setnx',
        'del',
        'delete',
        'getSet',
        'exists',
        'incr',
        'incrBy',
        'incrByFloat',
        'decr',
        'decrBy',
        'mget',
        'append',
        'getRange',
        'setRange',
        'strlen',
        'getBit',
        'setBit',
        'mset'
    ];

    protected $base_array = [
        'ping',
        'echo',
        'randomKey',
        'select',
        'move',
        'rename',
        'renameNx',
        'expire',
        'pexpire',
        'expireAt',
        'pexpireAt',
        'keys',
        'dbSize',
        'object',
        'save',
        'bgsave',
        'lastSave',
        'type',
        'flushDB',
        'flushAll',
        'sort',
        'info',
        'resetStat',
        'ttl',
        'pttl',
        'persist',
        'eval',
        'evalSha',
        'script',
        'getLastError',
        '_prefix',
        '_unseriablize',
        'dump',
        'restore',
        'time'
    ];

    protected $hash_method = [
        'hSet',
        'hSetNx',
        'hGet',
        'hLen',
        'hDel',
        'hKeys',
        'hVals',
        'hGetAll',
        'hExists',
        'hIncrBy',
        'hIncrByFloat',
        'hMset',
        'hMGet'
    ];

    protected $set_method = [
        'sAdd',
        'sRem',
        'sMove',
        'sInMember',
        'sCard',
        'sPop',
        'sRandMember',
        'sInter',
        'sInterStore',
        'sUnion',
        'sUnionStore',
        'sDiff',
        'sDiffStore',
        'sMembers'
    ];

    protected $sort_set_method = [
        'zAdd',
        'zRange',
        'zDelete',
        'zRevRange',
        'zRangeByScore',
        'zCount',
        'zRemRangeByScore',
        'zRemRangeByRank',
        'zSize',
        'zScore',
        'zRank',
        'zRevRank',
        'zIncrBy'
    ];

    protected $list_method = [
        'lPush',
        'rPush',
        'lPushx',
        'rPushx',
        'lPop',
        'rPop',
        'blpop',
        'brpop',
        'lLen',
        'IRange',
        'lTrim',
        'lRem',
        'rpoplpush',
        'brpoplush'
    ];

    protected $method_arr = [];
    protected $pool;
    protected static $instance = [];

    private function __construct($name = 'default')
    {
        $this->method_arr = array_merge($this->string_method, $this->hash_method, $this->set_method, $this->sort_set_method, $this->list_method,$this->base_array);
        if(RedisPool::hasInstance($name)){
            $this->pool = RedisPool::getInstance($name);
        }else{
            throw new \RuntimeException('Redis Pool ',$name.' not exists');
        }
    }

    private final function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function getInstance($name = 'default')
    {
        if(empty(self::$instance[$name])){
            self::$instance[$name] = new self($name);
        }
        return self::$instance[$name];
    }

    public function query($method, $args)
    {
        if (!in_array($method,$this->method_arr)) {
            throw new \RuntimeException("Redis Method {$method} not exists");
        }
        $chan = new \Chan(1);
        Coroutine::create(function () use ($args, $method, $chan) {
            $redis = null;
            try {
                $redis = $this->pool->get();
                $res = call_user_func_array([$redis, $method], $args);
            } catch (\Throwable $e) {
                //可以记录日志
                $res = null;
            }
            $chan->push($res);
            //使用完成放入连接池
            $this->pool->put($redis);
            unset($res);
            unset($redis);
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