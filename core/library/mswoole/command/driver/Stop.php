<?php

namespace library\mswoole\command\driver;


use library\mswoole\command\Driver;
use Config;

class Stop extends Driver
{
    public function handle()
    {
        $config = Config::get('swoole.');
        if(file_exists($config['setting']['pid_file'])){
            $sig = SIGTERM;//发送信号
            $pid = file_get_contents($config['setting']['pid_file']);
            if(!\swoole_process::kill($pid,0)){
                return "pid {$pid} process not running";
            }
            while (true){
                if(\swoole_process::kill($pid,0)){
                    \swoole_process::kill($pid,$sig);
                    echo 'send server stopping'.PHP_EOL;
                    sleep(2);
                }else{
                    break;
                }
            }
            return "send server stop command success at " . date("Y-m-d H:i:s");
        }else{
            return 'pid file not exists';
        }
    }
}