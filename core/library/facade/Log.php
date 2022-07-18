<?php

namespace library\facade;

use library\Facade;

class Log extends Facade
{
    public static function getFacadeClass()
    {
        return 'log';
    }
}