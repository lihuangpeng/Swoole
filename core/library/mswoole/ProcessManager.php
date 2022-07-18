<?php

namespace library\mswoole;

use library\mswoole\process\AbstractProcess;
use Swoole\Server;
use Swoole\Table;

class ProcessManager
{

    protected $table;
    protected $process = [];
    protected $max_process_num = 1024;
    protected static $instance;

    private final function __clone()
    {
        // TODO: Implement __clone() method.
    }

    private function __construct()
    {
        $this->table = new Table($this->max_process_num);
        $this->table->column('process_id', Table::TYPE_INT, 11); //进程id
        $this->table->column('process_name', Table::TYPE_STRING, 64); //进程名
        $this->table->column('process_group', Table::TYPE_STRING, 64);//进程组
        $this->table->column('memory_usage', Table::TYPE_INT, 11); //当前进程已使用内存
        $this->table->column('memory_peak_usage', Table::TYPE_INT, 11); //当前进程分配的最高峰值内存（包括未使用）
        $this->table->create();
    }

    public function addProcess($process)
    {
        if (count($this->process) >= $this->max_process_num) {
            throw new \RuntimeException('当前已达到最大进程设置数');
        }
        if ($process instanceof AbstractProcess) {
            $this->process[] = $process;
        }
        return $this;
    }

    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    //将进程添加进服务
    public function attachToServer(Server $server)
    {
        foreach ($this->process as $process) {
            $process = $process->getSwooleProcess();
            $server->addProcess($process);
        }
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAllProcessInfo()
    {
        $info = [];
        foreach ($this->process as $process){
            $process = $process->getSwooleProcess();
            $info[$process->pid] = $this->table->get($process->pid);
        }
        return $info;
    }

}