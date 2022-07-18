<?php

namespace library\facade;

use library\Facade;

Class Config extends Facade
{
    public static function getFacadeClass()
    {
        return 'config';
    }
}