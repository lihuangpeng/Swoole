<?php

namespace library\facade;

use library\Facade;

class Request extends Facade
{
    public static function getFacadeClass()
    {
        return 'request';
    }
}