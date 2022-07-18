<?php

namespace library;

/**
 * Class Cache
 * @package sethink\swooleRedis
 * @method Cache init(string $server) static 初始化，加入server
 * @method Cache instance();
 * @method Cache put(object $redis)
 * @method Cache setDefer(bool $bool = true)
 *
 * //base
 * @method Cache ping()
 * @method Cache echo (string $string)
 * @method Cache randomKey()
 * @method Cache select(int $tableId)
 * @method Cache move(string $key, int $tableId)
 * @method Cache rename(string $srcKey, String $newKey)
 * @method Cache renameNx(string $srcKey, string $newKey)
 * @method Cache expire(string $key, int $ttl)
 * @method Cache pexpire(string $key, int $ttl)
 * @method Cache expireAt(string $key, int $timestamp)
 * @method Cache pexpireAt(string $key, int $timestamp)
 * @method Cache keys(string $key)
 * @method Cache dbSize()
 * @method Cache object(string $key, string $object)
 * @method Cache save()
 * @method Cache bgsave()
 * @method Cache lastSave()
 * @method Cache type(string $type)
 * @method Cache flushDB()
 * @method Cache flushAll()
 * @method Cache sort(string $key, $value)
 * @method Cache info()
 * @method Cache resetStat()
 * @method Cache ttl(string $key)
 * @method Cache pttl(string $key)
 * @method Cache persist(string $key)
 * @method Cache eval(string $luaScript)
 * @method Cache evalSha(string $luaScriptSha)
 * @method Cache script()
 * @method Cache getLastError()
 * @method Cache _prefix(string $key)
 * @method Cache _unserialize(string $serialized)
 * @method Cache dump(string $key)
 * @method Cache restore(string $key, int $ttl, string $value)
 * @method Cache time()
 *
 *
 * //string
 * @method Cache get(string $key)
 * @method Cache set(string $key, string $value, int $timeout = 0)
 * @method Cache setex(string $key, int $ttl, string $value)
 * @method Cache psetex(string $key, int $expire, string $value)
 * @method Cache setnx(string $key, string $value)
 * @method Cache del(string ... $key)
 * @method Cache delete(string ... $key)
 * @method Cache getSet(string $key, string $value)
 * @method Cache exists(string $key)
 * @method Cache incr(string $key)
 * @method Cache incrBy(string $key, int $increment)
 * @method Cache incrByFloat(string $key, float $increment)
 * @method Cache decr(string $key)
 * @method Cache decrBy(string $key, int $increment)
 * @method Cache mget(array ... $keys)
 * @method Cache append(string $key, string $value)
 * @method Cache getRange(string $key, int $start, int $end)
 * @method Cache setRange(string $key, int $offset, string $value)
 * @method Cache strlen(string $key)
 * @method Cache getBit(string $key, int $offset)
 * @method Cache setBit(string $key, int $offset, bool $bool)
 * @method Cache mset(array $keyValue)
 *
 * //list
 * @method Cache lPush(string $key, string $value)
 * @method Cache rPush(string $key, string $value)
 * @method Cache lPushx(string $key, string $value)
 * @method Cache rPushx(string $key, string $value)
 * @method Cache lPop(string $key)
 * @method Cache rPop(string $key)
 * @method Cache blpop(array $keys, int $timeout)
 * @method Cache brpop(array $keys, int $timeout)
 * @method Cache lSize(string $key)
 * @method Cache lGet(string $key, int $index)
 * @method Cache lSet(string $key, int $index, string $value)
 * @method Cache IRange(string $key, int $start, int $end)
 * @method Cache lTrim(string $key, int $start, int $end)
 * @method Cache lRem(string $key, string $value, int $count)
 * @method Cache rpoplpush(string $srcKey, string $dstKey)
 * @method Cache brpoplpush(string $srcKey, string $detKey, int $timeout)
 *
 * //set
 * @method Cache sAdd(string $key, string $value)
 * @method Cache sRem(string $key, string $value)
 * @method Cache sMove(string $srcKey, string $dstKey, string $value)
 * @method Cache sIsMember(string $key, string $value)
 * @method Cache sCard(string $key)
 * @method Cache sPop(string $key)
 * @method Cache sRandMember(string $key)
 * @method Cache sInter(string ... $keys)
 * @method Cache sInterStore(string $dstKey, string ... $srcKey)
 * @method Cache sUnion(string ... $keys)
 * @method Cache sUnionStore(string $dstKey, string ... $srcKey)
 * @method Cache sDiff(string ... $keys)
 * @method Cache sDiffStore(string $dstKey, string ... $srcKey)
 * @method Cache sMembers(string $key)
 *
 * //zset
 * @method Cache zAdd(string $key, double $score, string $value)
 * @method Cache zRange(string $key, int $start, int $end)
 * @method Cache zDelete(string $key, string $value)
 * @method Cache zRevRange(string $key, int $start, int $end)
 * @method Cache zRangeByScore(string $key, int $start, int $end, array $options = [])
 * @method Cache zCount(string $key, int $start, int $end)
 * @method Cache zRemRangeByScore(string $key, int $start, int $end)
 * @method Cache zRemRangeByRank(string $key, int $start, int $end)
 * @method Cache zSize(string $key)
 * @method Cache zScore(string $key, string $value)
 * @method Cache zRank(string $key, string $value)
 * @method Cache zRevRank(string $key, string $value)
 * @method Cache zIncrBy(string $key, double $score, string $value)
 *
 * //hash
 * @method Cache hSet(string $key, string $hashKey, string $value)
 * @method Cache hSetNx(string $key, string $hashKey, string $value)
 * @method Cache hGet(string $key, string $hashKey)
 * @method Cache hLen(string $key)
 * @method Cache hDel(string $key, string $hashKey)
 * @method Cache hKeys(string $key)
 * @method Cache hVals(string $key)
 * @method Cache hGetAll(string $key)
 * @method Cache hExists(string $key, string $hashKey)
 * @method Cache hIncrBy(string $key, string $hashKey, int $value)
 * @method Cache hIncrByFloat(string $key, string $hashKey, float $value)
 * @method Cache hMset(string $key, array $keyValue)
 * @method Cache hMGet(string $key, array $hashKeys)
 */
use Config;

class Cache
{
    protected $_config = [];
    protected static $_instance = [];
    protected $_handle = null;

    public function __construct()
    {
        $this->_config = Config::get('swoole.cache');
        $this->init();
    }

    public function init($config = array())
    {
        if(is_null($this->_handle)){
            $config = empty($config) ? $this->_config : $config;
            if($config['type'] == 'complex'){
                $default = $config['default'];
                $config = isset($config[$default['type']]) ? $config[$default['type']] : $default;
                $config['type'] = $default['type'];
            }
            $this->_handle = $this->connect($config);
            unset($config);
        }
        return $this->_handle;
    }

    public function connect($config,$pool_name = 'default')
    {
        $name = $config['type'].'_'.$pool_name;
        if (!isset(self::$_instance[$name])) {
            self::$_instance[$name] = call_user_func_array([ '\\library\\cache\\driver\\'.$config['type'],'getInstance'],[$pool_name]);
        }
        unset($config);
        return self::$_instance[$name];
    }

    /**
     * @param string $type
     * @param string $pool_name
     * @return \Redis | \Memcached
     */
    public function store($type = '',$pool_name = 'default'){
        if($type !== '' && $this->_config['type'] == 'complex' && isset($this->_config[$type])){
            $config = $this->_config[$type];
            $config['type'] = $type;
            return $this->connect($config,$pool_name);
        }
        return $this->init();
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->_handle,$name],$arguments);
    }
}