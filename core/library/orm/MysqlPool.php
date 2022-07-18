<?php

namespace library\orm;

use Swoole\Coroutine\Channel;
use Swoole\Timer;

class MysqlPool
{
    //配置
    protected $config = [
        //连接池最小数量
        'poolMin' => 5,
        //连接池最大数量
        'poolMax' => 20,
        //超时
        'timeout' => 5,
        //地址
        'host' => '',
        //端口
        'port' => '3306',
        //数据库
        'dbname' => '',
        //用户
        'user' => '',
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

    public function setDbName($dbname)
    {
        $this->config['dbname'] = $dbname;
        return $this;
    }

    public function setUser($user)
    {
        $this->config['user'] = $user;
        return $this;
    }

    public function setAuth($auth)
    {
        $this->config['auth'] = $auth;
        return $this;
    }

    public function setPrefix($prefix)
    {
        $this->config['prefix'] = $prefix;
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
     * @return MysqlPool
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
        foreach (['host', 'port', 'dbname', 'user', 'auth'] as $name) {
            $config[$name] = is_string($this->config[$name]) ? explode(',', $this->config[$name]) : $this->config[$name];
        }
        $return = [];
        $num = $this->pool->length() % count($config['host']); //根据长度轮询
        foreach (['host', 'port', 'dbname', 'user', 'auth'] as $name) {
            $return[$name] = $config[$name][$num];
        }
        unset($config);
        return $return;
    }

    /**
     * 入池
     * @param $pdo
     */
    public function put($pdo)
    {
        if ($pdo instanceof \PDO && $this->pool->length() < $this->config['poolMax'] && $this->ping_pdo($pdo)) {
            $this->pool->push($pdo);
        }
        $this->push_time = time();
    }

    /**
     * @return \Swoole\Coroutine\Channel;
     */
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
                    $pdo = $this->pool->pop(1);
                    unset($pdo);
                    echo date('Y-m-d H:i:s') . ' clear > poolMin MysqlPool:' . $this->name . PHP_EOL;
                }
                //连接池空闲多久，清空连接
                if (!empty($this->config['clearAll']) && time() - $this->push_time > $this->config['clearAll']) {
                    while (!$this->pool->isEmpty()) {
                        $pdo = $this->pool->pop(1);
                        unset($pdo);
                        echo date('Y-m-d H:i:s') . ' clearAll MysqlPool:' . $this->name . PHP_EOL;
                    }
                }
            });
        }
        return $this;
    }

    public function keepMinPool()
    {
        for ($i = 0; $i < $this->config['poolMin']; $i++) {
            $pdo = $this->createConnect();
            $this->put($pdo);
            unset($pdo);
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
            throw new \RuntimeException(date('Y-m-d H:i:s') . ':Mysql连接池没有启动');
        }
        if (!empty($this->pool) && $this->pool->length() > 0) {
            $pdo = $this->pool->pop($this->config['timeout']);
        } else {
            try {
                $pdo = $this->createConnect();
                $this->add_new_pool_time = time();
            } catch (\Throwable $e) {
                $pdo = null;
            }
        }
        if (empty($pdo) || !$this->ping_pdo($pdo)) {
            unset($pdo);
            if ($ret_i <= $this->config['poolMin']) {
                echo date('Y-m-d H:i:s') . ',Mysql尝试重连中，重连次数' . $ret_i . '次';
                goto back;
            } else {
                if ($this->name !== 'default' && isset(self::$instance['default']) && self::$instance['default']->check_is_alive()) {
                    //使用默认的主库尝试
                    $pdo = self::$instance['default']->get();
                } else {
                    throw new \RuntimeException('Mysql连接失败');
                }
            }
        }
        return $pdo;
    }

    /**
     * @param \PDO $pdo
     * @return bool
     */
    public function ping_pdo($pdo)
    {
        try {
            $pdo->getAttribute(\PDO::ATTR_SERVER_INFO);
        } catch (\PDOException $exception) {
            if (strpos($exception->getMessage(), 'MySQL server has gone away') !== false) {
                return false;
            }
        }
        return true;
    }

    //清空连接
    public function __destruct()
    {
        $this->destruct();
    }

    protected function createConnect($throw = false)
    {
        try {
            $config = $this->getConfig();
            $pdo = new \PDO("mysql:dbname={$config['dbname']};host={$config['host']};port={$config['port']}", $config['user'], $config['auth']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            unset($config);
        } catch (\Throwable $e) {
            if ($throw) throw new \RuntimeException($e->getMessage());
            $pdo = null;
        }
        return $pdo;
    }

    /**
     * 清空连接池
     */
    public function destruct()
    {
        $this->is_alive = false;
        while (!$this->pool->isEmpty()) {
            $pdo = $this->pool->pop();
            unset($pdo);
        }
    }
}