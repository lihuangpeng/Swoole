<?php

namespace library\cache\pool;

use Swoole\Coroutine\Channel;
use Swoole\Timer;

class RedisPool
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

    protected function getConfig()
    {
        $config = [];
        foreach (['host', 'port', 'auth'] as $name) {
            $config[$name] = is_string($this->config[$name]) ? explode(',', $this->config[$name]) : $this->config[$name];
        }
        $return = [];
        $num = $this->pool->length() % count($config['host']); //根据长度轮询
        foreach (['host', 'port', 'auth'] as $name) {
            $return[$name] = $config[$name][$num];
        }
        return $return;
    }

    /**
     * 入池
     * @param $redis
     */
    public function put($redis)
    {
        if ($redis instanceof \Redis && $this->pool->length() < $this->config['poolMax']) {
            $this->pool->push($redis);
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
                    $redis = $this->pool->pop(1);
                    $redis->close();
                    unset($redis);
                    echo date('Y-m-d H:i:s') . ' clear > poolMin RedisPool:' . $this->name.PHP_EOL;
                }
                //连接池空闲多久，清空连接
                if (!empty($this->config['clearAll']) && time() - $this->push_time > $this->config['clearAll']) {
                    while (!$this->pool->isEmpty()) {
                        $redis = $this->pool->pop(1);
                        $redis->close();
                        unset($redis);
                        echo date('Y-m-d H:i:s') . ' clearAll RedisPool:' . $this->name.PHP_EOL;
                    }
                }
            });
        }
        return $this;
    }

    public function keepMinPool()
    {
        for ($i = 0; $i < $this->config['poolMin']; $i++) {
            $redis = $this->createConnect();
            $this->put($redis);
            unset($redis);
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
            throw new \RuntimeException(date('Y-m-d H:i:s') . 'Redis连接池没有启动');
        }
        if (!empty($this->pool) && $this->pool->length() > 0) {
            $redis = $this->pool->pop($this->config['timeout']);
        } else {
            try {
                $redis = $this->createConnect();
                $this->add_new_pool_time = time();
            } catch (\Throwable $e) {
                $redis = null;
            }
        }
        if (!method_exists($redis, 'ping') || $redis->ping() != '+PONG') {
            if (method_exists($redis, 'close')) $redis->close();
            unset($redis);
            if ($ret_i <= $this->config['poolMin']) {
                echo date('Y-m-d H:i:s') . ',Redis尝试重连中，重连次数' . $ret_i . '次';
                goto back;
            } else {
                if ($this->name !== 'default' && isset(self::$instance['default']) && self::$instance['default']->check_is_alive()) {
                    //使用默认的尝试
                    $redis = self::$instance['default']->get();
                } else {
                    throw new \RuntimeException('Redis连接失败');
                }
            }
        }
        return $redis;
    }

    //清空连接
    public function __destruct()
    {
        $this->destruct();
    }

    protected function createConnect()
    {
        $config = $this->getConfig();
        $redis = new \Redis();
        $config['port'] = empty($config['port']) ? '6379' : $config['port'];
        $redis->connect($config['host'], $config['port'], $this->config['timeout']);
        if (!empty($config['auth'])) $redis->auth($config['auth']);
        unset($config);
        return $redis;
    }

    /**
     * 清空连接池
     */
    public function destruct()
    {
        $this->is_alive = false;
        while (!$this->pool->isEmpty()) {
            $redis = $this->pool->pop();
            $redis->close();
            unset($redis);
        }
    }
}