<?php

namespace library\cache\pool;

use Swoole\Coroutine\Channel;
use Swoole\Timer;

class MemcachedPool
{
    //配置
    protected $config = [
        //连接池最小数量
        'poolMin' => 5,
        //连接池最大数量
        'poolMax' => 20,
        //超时
        'timeout' => 10,
        //地址
        'host' => '',
        //端口
        'port' => '',
        //密码
        'auth' => '',
        //定时清理连接池
        'clearTime' => '60000',
        //空闲多久清理所有连接池
        'clearAll' => '300'
    ];
    protected $is_alive = false;
    protected $add_new_pool_time = 0;
    protected $push_time = 0;

    //池
    protected $name = 'default';
    protected $pool = null;

    protected static $instance;

    private final function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public function setPoolMin($poolMin)
    {
        $this->config['poolMin'] = $poolMin;
        return $this;
    }

    public function setPoolMax($poolMax)
    {
        $this->config['poolMax'] = $poolMax;
        return $this;
    }

    public function setHost($host)
    {
        $this->config['host'] = $host;
        return $this;
    }

    public function setPort($port)
    {
        $this->config['port'] = $port;
        return $this;
    }

    public function setAuth($auth)
    {
        $this->config['auth'] = $auth;
        return $this;
    }

    public function check_is_alive()
    {
        return $this->is_alive;
    }

    private function __construct($name = 'default')
    {
        $this->name = $name;
    }

    /**
     * 启动连接池
     */
    public function start()
    {
        $this->pool = new Channel($this->config['poolMax']);
        $this->is_alive = true;
    }

    public static function hasInstance($name)
    {
        return isset(self::$instance[$name]) ? true : false;
    }

    /**
     * 获取实例
     * @param string $name
     * @return RedisPool
     */
    public static function getInstance($name = 'default')
    {
        if (empty(self::$instance[$name])) {
            self::$instance[$name] = new self($name);
        }
        return self::$instance[$name];
    }

    /**
     * 入池
     * @param $memcached
     */
    public function put($memcached)
    {
        if ($memcached instanceof \Memcached && $this->pool->length() < $this->config['poolMax']) {
            $this->pool->push($memcached);
        }
        $this->push_time = time();
    }

    public function getPool()
    {
        return $this->pool;
    }

    public function clearInterval()
    {
        if (!empty($this->config['clearTime'])) {
            Timer::tick($this->config['clearTime'], function () {
                //5秒没有新连接创建，清理超出连接
                while ($this->pool->length() > $this->config['poolMin'] && time() - 5 > $this->add_new_pool_time) {
                    $memcached = $this->pool->pop(1);
                    $memcached->quit();
                    unset($memcached);
                    echo date('Y-m-d H:i:s') . ' clear > poolMin MemcachedPool:' . $this->name.PHP_EOL;
                }
                //连接池空闲多久，清空连接
                if (!empty($this->config['clearAll']) && time() - $this->push_time > $this->config['clearAll']) {
                    while (!$this->pool->isEmpty()) {
                        $memcached = $this->pool->pop(1);
                        $memcached->quit();
                        unset($memcached);
                        echo date('Y-m-d H:i:s') . ' clearAll MemcachedPool:' . $this->name.PHP_EOL;
                    }
                }
            });
        }
        return $this;
    }

    public function keepMinPool()
    {
        for ($i = 0; $i < $this->config['poolMin']; $i++) {
            $memcached = $this->createConnect(true);
            $this->put($memcached);
            unset($memcached);
        }
        return $this;
    }

    //出池
    public function get()
    {
        //重试次数
        $ret_i = -1;
        //定位
        back:
        $ret_i++;
        if (!$this->check_is_alive()) {
            throw new \RuntimeException(date('Y-m-d H:i:s') . 'Memcached连接池没有启动');
        }
        if (!empty($this->pool) && $this->pool->length() > 0) {
            $memcached = $this->pool->pop($this->config['timeout']);
        } else {
            $memcached = $this->createConnect();
            $this->add_new_pool_time = time();
        }
        if (!method_exists($memcached, 'getStats') || empty($memcached->getStats())) {
            if (method_exists($memcached, 'quit')) $memcached->quit();
            unset($memcached);
            if ($ret_i <= $this->config['poolMin']) {
                echo date('Y-m-d H:i:s') . ',Memcached尝试重连中，重连次数' . $ret_i . '次';
                goto back;
            } else {
                throw new \RuntimeException('Memcached连接失败');
            }
        }
        return $memcached;
    }

    //清空连接
    public function __destruct()
    {
        $this->destruct();
    }

    protected function createConnect($throw = false)
    {
        try {
            $memcached = new \Memcached();
            if (!empty($this->config['options'])) {
                $memcached->setOptions($this->config['options']);
            }
            if (!empty($this->config['timeout'])) {
                $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $this->config['timeout']);
            }
            $server = [];
            $hosts = is_string($this->config['host']) ? explode(',', $this->config['host']) : $this->config['host'];
            $ports = is_string($this->config['port']) ? explode(',', $this->config['port']) : $this->config['port'];
            foreach ($hosts as $key => $host) {
                $server[] = [$host, isset($ports[$key]) ? $ports[$key] : 11211];
            }
            $memcached->addServers($server);
            if ($this->config['auth'] != '') {
                //使用sasl验证必须使用
                $this->_linkID->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
                $this->_linkID->setSaslAuthData($this->config['user'], $this->config['auth']);
            }
            unset($server, $hosts, $ports);
        } catch (\Throwable $e) {
            if($throw){
                throw new \RuntimeException($e->getMessage());
            }
            //添加失败日志
            $memcached = null;
        }
        return $memcached;
    }

    /**
     * 清空连接池
     */
    public function destruct()
    {
        $this->is_alive = false;
        while (!$this->pool->isEmpty()) {
            $memcached = $this->pool->pop();
            $memcached->quit();
            unset($memcached);
        }
    }
}