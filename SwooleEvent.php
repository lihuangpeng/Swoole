<?php

use library\facade\Request;
use Swoole\Http\Response;
use library\cache\pool\RedisPool;
use library\facade\Config;
use library\cache\pool\MemcachedPool;
use library\orm\MysqlPool;
use library\mswoole\ServerManager;
use library\mswoole\rpc_client\Packet;

class SwooleEvent
{
    //程序全局，服务启动前加载的内容
    public static function initialize()
    {
        //数据库ORM
        self::initMysql();
        //注册redis连接池
        self::initRedis();
        //注册Memcached连接池
        self::initMemcached();
        //添加tcp服务
        self::addTcpServer();
    }

    //workerStart
    public static function onWorkerStart($server, $worker_id)
    {
        //增加连接池清理定时任务
        $configs = Config::get('swoole.');
        foreach ($configs['cache']['redis'] as $name => $config) {
            RedisPool::getInstance($name)->keepMinPool()->clearInterval();
        }
        MemcachedPool::getInstance()->keepMinPool()->clearInterval();
        foreach ($configs['mysql'] as $name => $config) {
            MysqlPool::getInstance($name)->keepMinPool()->clearInterval();
        }
        unset($configs);
    }

    /**
     * Task失败异常处理
     * @param Throwable $throwable
     * @param int $taskId
     * @param int $workerIndex
     */
    public static function onTaskException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

    //http请求
    public static function onRequest(Response $response)
    {
        try {
            $content = Core::dispatcher(Request::request());
        } catch (\Throwable $e) {
            $content = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
        if (is_array($content)) $content = json_encode($content);
        $response->end($content);
    }

    //tcp请求
    public static function onReceive(Swoole\Server $server, int $fd, int $reactorId, string $data)
    {
        try {
            $data = Packet::length_decode($data);
            $content = Core::dispatcher($data,'tcp');
        } catch (\Throwable $e) {
            $content = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
        if (is_array($content)) $content = json_encode($content);
        $content = Packet::length_encode($content);
        $server->send($fd, $content);
    }

    public static function initMysql()
    {
        $configs = Config::get('swoole.mysql');
        foreach ($configs as $name => $config) {
            MysqlPool::getInstance($name)->setAuth($config['auth'])->setUser($config['user'])
                ->setHost($config['host'])->setPort($config['port'])->setDbName($config['db'])
                ->setPoolMin(5)->setPoolMax(20)->start();
        }
        unset($configs);
    }


    public static function initRedis()
    {
        //创建连接池
        $configs = Config::get('swoole.cache.redis');
        foreach ($configs as $name => $config) {
            RedisPool::getInstance($name)
                ->setHost($config['host'])
                ->setPort($config['port'])
                ->setAuth($config['auth'])
                ->setPoolMin(5)->setPoolMax(20)->start();
        }
        unset($configs);
    }

    public static function initMemcached()
    {
        $config = Config::get('swoole.cache.memcached');
        MemcachedPool::getInstance()
            ->setHost($config['host'])
            ->setPort($config['port'])
            ->setPoolMin(5)->setPoolMax(20)->start();
        unset($config);
    }


    public static function addTcpServer()
    {
        $config = Config::get('swoole.');
        $tcp_server = ServerManager::getInstance()->getCreateServer()->addListener($config['tcp_host'], $config['tcp_port'], SWOOLE_SOCK_TCP);
        $tcp_server->set($config['setting']);
        $tcp_server->on('Receive', [self::class, 'onReceive']);
    }


}