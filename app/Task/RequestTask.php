<?php

namespace App\Task;

use library\mswoole\task\TaskInterface;

class RequestTask implements TaskInterface
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function run(int $taskId, int $workerIndex)
    {
        return $this->request;
    }

    public function onException(\Throwable $throwable,int $taskId,int $workerIndex){
        var_dump($throwable->getMessage());
    }
}