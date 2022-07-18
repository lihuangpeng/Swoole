<?php

namespace library\mswoole\process;

use library\mswoole\ProcessManager;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Process;

abstract class AbstractProcess
{
    //pipe_type
    const PIPE_TYPE_NONE = 0;
    const PIPE_TYPE_SOCK_STREAM = 1;
    const PIPE_TYPE_SOCK_DGRAM = 2;

    //swoole进程对象
    protected $swooleProcess;
    //进程配置
    protected $config = [
        'process_group' => '',
        'process_name' => '',
        'pipe_type' => self::PIPE_TYPE_SOCK_DGRAM, //unixSocket 进程通信类型
        'redirect_stdin_stdout' => false, //重定向子进程输入输出
        'enable_coroutine' => true, //进程内部自动创建协程
        'args' => []
    ];
    //进程执行结果最大等待时间
    protected $max_wait_time = 3;

    public function __construct(...$args)
    {
        $arg0 = array_shift($args);
        if (is_array($arg0)) {
            $this->config = array_merge($this->config, $arg0);
        } else {
            $this->setProcessName($arg0);
            $this->setArgs($args[0]);
            if(isset($args[1])) $this->setRedirectStdinStdout((bool)$args[1] ? true : false);
            if(isset($args[2])) $this->setPieType($args[2]);
            if(isset($args[3])) $this->enableCoroutine($args[3]);
        }
        $this->swooleProcess = new Process(function (Process $process){
            $this->__start($process);
        }, $this->config['redirect_stdin_stdout'], $this->getPieType(),$this->isEnableCoroutine());
        $this->swooleProcess->name($this->config['process_name']);
    }

    public function setProcessName($name)
    {
        $this->config['process_name'] = $name;
    }

    public function getProcessName()
    {
        return $this->config['process_name'];
    }

    public function setProcessGroup($group)
    {
        $this->config['process_group'] = $group;
    }

    public function getProcessGroup()
    {
        return $this->config['process_group'];
    }

    public function setArgs($args)
    {
        $this->config['args'] = $args;
    }

    public function getArgs()
    {
        return $this->config['args'];
    }

    public function setPieType($pie_type)
    {
        $this->config['pipe_type'] = $pie_type;
    }

    public function getPieType()
    {
        return $this->config['pipe_type'];
    }

    public function setRedirectStdinStdout($redirect_stdin_stdout)
    {
        $this->config['redirect_stdin_stdout'] = $redirect_stdin_stdout;
    }

    public function getRedirectStdinStdout()
    {
        return $this->config['redirect_stdin_stdout'];
    }

    public function enableCoroutine($enable_coroutine)
    {
        $this->config['enable_coroutine'] = (bool)$enable_coroutine ? true : false;
    }

    public function isEnableCoroutine()
    {
        return $this->config['enable_coroutine'];
    }

    public function getSwooleProcess()
    {
        return $this->swooleProcess;
    }

    protected function __start(Process $process)
    {
        $table = ProcessManager::getInstance()->getTable();
        $table->set($process->pid, [
            'process_id' => $process->pid,
            'process_name' => $this->config['process_name'],
            'process_group' => $this->config['process_group']
        ]);
        //定时监听,异步io（协程化使用epoll_wait形式,事件循环不会退出进程）
        \Swoole\Timer::tick(1000, function () use ($table,$process) {
            $table->set($process->pid, [
                'memory_usage' => memory_get_usage(),
                'memory_peak_usage' => memory_get_peak_usage(true)
            ]);
        });
        //进程通信事件监听
        Event::add($this->swooleProcess->pipe, function () {
            try {
                $this->onPipeReadEvent($this->swooleProcess);
            } catch (\Throwable $e) {
                $this->onException($e);
            }
        });
        //进程关闭信号监听
        $this->swooleProcess->signal(SIGTERM,function ()use ($table){
            \Swoole\Timer::clearAll(); //关闭定时器
            swoole_event_del($this->swooleProcess->pipe);//删除event事件监听
            $table->del($this->swooleProcess->pid); //删除该进程共享内存行
            $this->swooleProcess->exit();//退出进程
            $this->swooleProcess->wait(false); //回收进程资源，防止僵尸进程
        });
        //进程执行过程退出后
        register_shutdown_function(function($e)use ($table){
            \Swoole\Timer::clearAll();
            swoole_event_del($this->swooleProcess->pipe);
            $table->del($this->swooleProcess->pid);
            $this->swooleProcess->wait(false);
        });
        return $this->run($this->getArgs());
    }

    public function onException(\Throwable $e)
    {
        throw $e;
    }

    public function onPipeReadEvent(\Swoole\Process $process)
    {
        $process->read();
    }

    abstract public function run($args);
}