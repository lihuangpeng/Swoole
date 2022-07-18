<?php
/*
 * 框架入口文件，加载需要的内容
 */

namespace library;
use library\facade\Core;
require_once __DIR__ . "/library/Loader.php";
date_default_timezone_set('Asia/shanghai');

//自定义注册自动加载（可以自己增加别名等其它功能信息,以及自己注册autoLoadDir和psr-4）
Loader::register();

//自定义异常处理
Error::register();

//钩子
Facade::bind([
    facade\Log::class => Log::class
]);

//绑定别名，将类名与别名关联起来，使用别名相当于加载到类名
Loader::addClassAlias([
    'Log' => facade\Log::class,
    'Cache' => facade\Cache::class,
    'Core' => facade\Core::class,
    'Config' => facade\Config::class,
]);
Core::setDev(empty($argv[2]) || $argv[2] === 'dev' ? true : false)->run();









