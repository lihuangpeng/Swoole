<?php

namespace library\mswoole;

use library\mswoole\task\TaskInterface;

class TaskManager
{
    protected $server = null;
    protected static $instance = null;

    private function __construct()
    {
        $this->server = ServerManager::getInstance()->getCreateServer();
    }

    private final function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 异步投递
     * @param $task
     * @param null $callFinishBack
     * @param null $taskWorkerId
     * @return false|int
     */
    public function async($task, $callFinishBack = null, $taskWorkerId = null)
    {
        if (!($task instanceof \Closure) && !($task instanceof TaskInterface)) {
            throw new \RuntimeException('task操作不存在');
            return false;
        }

        return $this->server->task($task, $taskWorkerId, $callFinishBack);
    }

    /**
     * 同步投递
     * @param $task
     * @param float $timeout
     * @param null $taskWorkerId
     * @return string
     */
    public function sync($task, $timeout = 0.5, $taskWorkerId = null)
    {
        if (!($task instanceof \Closure) && !($task instanceof TaskInterface)) {
            throw new \RuntimeException('task操作不存在');
            return false;
        }
        return $this->server->taskwait($task, $timeout, $taskWorkerId);
    }
}