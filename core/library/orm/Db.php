<?php
/**
 * User: sethink
 */

namespace library\orm;

use library\orm\db\Query;
use library\orm\MysqlPool;
use Swoole\Coroutine;

/**
 * Class Db
 * @package \library\orm\db
 * @method Query startTransaction() static
 */
class Db
{
    protected static $DEFAULT_POOL = 'default';
    protected static $DEFAULT_CONNECTION = 'default';
    protected $connections = [];
    const SEPARATOR = '.';
    protected static $instance;

    private final function __clone()
    {
        // TODO: Implement __clone() method.
    }

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 初始化连接
     * @param string $name
     * @return mixed
     */
    protected function initConnection($name = null)
    {
        if (empty($name)) $name = self::$DEFAULT_CONNECTION;
        $arr = explode(self::SEPARATOR, $name);
        if (count($arr) == 1) {
            $name = self::$DEFAULT_POOL . self::SEPARATOR . $name;
            $pool = self::$DEFAULT_POOL;
        } else {
            $pool = $arr[1];
        }
        $cid = Coroutine::getCid();
        if (empty($this->connections[$cid][$name])) {
            $this->connections[$cid][$name] = MysqlPool::getInstance($pool)->get();

        }
        return $this->connections[$cid][$name];
    }

    public function getAllConnections()
    {
        return $this->connections;
    }

    /**
     * 连接
     * @param string $name
     * @return Query
     */
    public function connection($name)
    {
        if (empty($name)) $name = self::$DEFAULT_CONNECTION;
        $connection = $this->initConnection($name);
        return new Query($connection);
    }

    /**
     * 设置默认连接池
     * @param $pool
     * @return Db
     */
    public function setDefaultPool($pool)
    {
        self::$DEFAULT_POOL = $pool;
        return $this;
    }

    /**
     * 设置默认连接
     * @param $connection
     * @return Db
     */
    public function setDefaultConnection($connection)
    {
        self::$DEFAULT_CONNECTION = $connection;
        return $this;
    }


    /**
     * 当协程不存在时回收连接
     * @param $timer
     * @return
     */
    public function recycleConnection()
    {
        if (empty($this->connections)) return false;
        foreach ($this->connections as $cid => $connection) {
            if (!Coroutine::exists($cid)) {
                foreach ($connection as $name => $pdo) {
                    $name = explode(self::SEPARATOR, $name);
                    if (MysqlPool::hasInstance($name[0])) MysqlPool::getInstance($name[0])->put($pdo);
                }
                unset($this->connections[$cid]);
            }
        }
        return true;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([new Query($this->initConnection()), $name], $arguments);
    }
}