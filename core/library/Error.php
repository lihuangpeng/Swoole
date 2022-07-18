<?php

namespace library;

/**
 * 异常错误注册类
 * Class Error
 * @package library
 */
use Core;
class Error
{
    static public function register()
    {
        register_shutdown_function([__CLASS__,'appShutDown']);
    }

    //注册终止停止类
    static public function appShutDown(...$args)
    {
        if(!is_null(error_get_last())){
            Core::dump('shutdown:'.PHP_EOL);
            Core::dump(error_get_last());
        }
    }
}