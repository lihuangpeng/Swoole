<?php

namespace library\mswoole\command\driver;


use library\facade\Config;
use library\facade\Core;
use library\mswoole\command\Driver;
use library\mswoole\ServerManager;
use library\Container;

class Start extends Driver
{
    public function handle()
    {
        $config = Config::get('swoole.');
        $pid_file = $config['setting']['pid_file'];
        if(file_exists($pid_file) && \swoole_process::kill(file_get_contents($pid_file),0)){
            return 'server already running';
        }
        Core::globalInitialize()->createServer();
        return "send server start command success at " . date("Y-m-d H:i:s");
    }
}