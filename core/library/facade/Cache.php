<?php

namespace library\facade;

use library\Facade;

class Cache extends Facade
{
    public static function getFacadeClass()
    {
        return 'cache';
    }
}