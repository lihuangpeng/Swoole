<?php

namespace library\mswoole\command\driver;


use library\mswoole\command\Driver;
use Config;

class Reload extends Driver
{
    public function handle()
    {
        $config = Config::get('swoole.');
        if(file_exists($config['setting']['pid_file'])){
            $sig = SIGUSR1;//发送信号
            $pid = file_get_contents($config['setting']['pid_file']);
            if(!\swoole_process::kill($pid,0)){
                echo "pid {$pid} process not running";
            }
            $result = \swoole_process::kill($pid,$sig);
            if($result){
                self::opCacheClear();
            }
            return "send server reload command at " . date("Y-m-d H:i:s").',result:'.$result;
        }else{
            return 'pid file not exists';
        }
    }

    public static function opCacheClear()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}