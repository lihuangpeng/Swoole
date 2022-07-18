<?php

namespace library\mswoole;

use library\Container;
use library\facade\Log;
use library\facade\Request;
use library\mswoole\task\TaskInterface;
use Swoole\Coroutine;
use Swoole\Timer;
use SwooleEvent;
use Swoole\Http\Server;
use library\facade\Config;
use library\Loader;
use library\orm\Db;
use Core;

class ServerManager
{
    protected $_config = [];
    protected $_server;
    protected static $_instance = [];

    private function __construct($config)
    {
        try {
            $this->_config = $config;
            $this->_server = new Server($this->_config['host'], $this->_config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
            $this->_server->set($this->_config['setting']);
            $this->_server->on('Start', [$this, 'onStart']);
            $this->_server->on('WorkerStart', [$this, 'onWorkerStart']);
            $this->_server->on('WorkerExit', [$this, 'onWorkerExit']);
            $this->_server->on('Request', [$this, 'onRequest']);
            $this->_server->on('Task', [$this, 'onTask']);
            $this->_server->on('Finish', [$this, 'onFinish']);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private final function __clone()
    {
    }

    public final function start()
    {
        $this->_server->start();
    }

    /**
     * @param array $config
     * @return ServerManager
     */
    public static function getInstance($config = array())
    {
        if (empty($config)) {
            $config = Config::get('swoole.');
        }
        $unique = md5(serialize($config));
        if (empty(self::$_instance[$unique])) {
            self::$_instance[$unique] = new self($config);
        }
        return self::$_instance[$unique];
    }

    /**
     * 获取server对象
     * @return \Swoole\Server
     */
    public function getCreateServer()
    {
        return $this->_server;
    }

    /****************************Swoole回调函数********************************/
    /**
     * master进程启动
     * 与workerStart并发进行
     * @param \Swoole\Server $server
     */
    public final function onStart(\Swoole\Server $server)
    {
        //设置进程名方便监听
        swoole_set_process_name('swoole' . $this->_config['port']);
        //携程相关配置
        \Swoole\Runtime::enableCoroutine($flags = SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL); //全部携程化
    }

    /**
     * worker进程启动,,回调内部会创建协程容器
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public final function onWorkerStart(\Swoole\Server $server, int $worker_id)
    {
        //加载配置文件
        Config::loadDir(dirname(dirname(dirname(__DIR__))) . '/config');
        //添加函数文件
        Loader::addAutoFile(dirname(dirname(__DIR__)) . '/helper.php');
        //加载文件
        Loader::loadComposerAutoloadFiles();
        //回收资源
        $this->workerClearInter();
        SwooleEvent::onWorkerStart($server, $worker_id);
    }

    protected final function workerClearInter()
    {
        //每三秒回收协程关闭的连接
        \Swoole\Timer::tick(3000, function () {
            Db::getInstance()->recycleConnection();
        });

        //每一秒回收request数据
        \Swoole\Timer::tick(1000, function () {
            Request::recycleRequest();
        });
    }

    public final function onWorkerExit(Server $server, int $worker_id)
    {
        Timer::clearAll();
    }

    /**
     * 接收http请求回调,回调内部会创建协程容器
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public final function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        //防止瀏覽器二次請求
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->end();
            return;
        }
        //跨域options請求
        if ($request->server['request_method'] == 'OPTIONS') {
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'POST,GET,PUT,PATCH,DELETE,OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Authorization, User-Agent, Keep-Alive, Content-Type, X-Requested-With');
            $response->status(http_response_code());
            $response->end();
            return;
        }
        $response->header('Content-Type', "application/json; charset=utf-8");
        //保存当前协程request数据
        Request::set($request);
        SwooleEvent::onRequest($response);
    }

    /**
     * 投递给task进程处理回调
     * @param \Swoole\Server $server
     * @param int $task_id
     * @param int $worker_id
     * @param mixed $data
     */
    public final function onTask(\Swoole\Server $server, \Swoole\Server\Task $task)
    {
        $result = false;
        try {
            if ($task->data instanceof \Closure) {
                $result = Container::getInstance()->invokeFunction($task->data, [$task->id, $task->worker_id]);
            } else if ($task->data instanceof TaskInterface) {
                $result = Container::getInstance()->invokeMethod([$task->data, 'run'], [$task->id, $task->worker_id]);
            }
        } catch (\Throwable $e) {
            Log::info('task_error', ['task_id' => $task->id, 'worker_id' => $task->worker_id, 'data' => $task->data, 'exception' => $e->getMessage()], 'ERROR');
            if ($task->data instanceof TaskInterface) {
                $result = $task->data->onException($e, $task->id, $task->worker_id);
            } else {
                $result = SwooleEvent::onTaskException($e, $task->id, $task->worker_id);
            }
        }
        //taskwait方法会得到返回结果,taskwait不会再worker进程中执行onFinish
        $task->finish($result);
    }

    /**
     * Task执行完成后,worker进程根据onTask返回的结果或者手动调用finish方法执行
     * @param \Swoole\Server $server
     * @param int $task_id
     * @param $data
     * @return string $data
     */
    public final function onFinish(\Swoole\Server $server, int $task_id, $data)
    {

    }
}