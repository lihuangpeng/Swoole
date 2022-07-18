<?php

namespace library\mswoole\task;

interface TaskInterface
{
    /**
     * 运行主代码
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     */
    public function run(int $taskId, int $workerIndex);

    /**
     * 运行异常处理
     * @param \Throwable $throwable
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     */
    public function onException(\Throwable $throwable,int $taskId,int $workerIndex);
}