<?php

namespace App\Process;

use library\mswoole\process\AbstractProcess;
use library\mswoole\ProcessManager;

class QueueProcess extends AbstractProcess
{
    public function run($args)
    {
        var_dump($args);
    }
}