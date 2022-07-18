<?php

namespace library;

if (PHP_SAPI !== 'cli') die('error');
define('MSwooleRoot',__DIR__);
require_once(__DIR__ . '/core/base.php');
use library\mswoole\Command;
$command = empty($argv[1]) ? 'start' : $argv[1];
$ret = Container::getInstance()->get(Command::class,compact('command'))->run();
if ($ret) {
    echo $ret . PHP_EOL;
}
