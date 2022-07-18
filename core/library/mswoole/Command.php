<?php

namespace library\mswoole;

use library\Container;
use library\Loader;

class Command
{
    protected $handle;

    public function __construct($command)
    {
        $this->handle = Loader::factory($command,'\\library\\mswoole\\command\\driver\\');
    }

    public function run()
    {
        return Container::getInstance()->invokeMethod([$this->handle,'handle']);
    }
}