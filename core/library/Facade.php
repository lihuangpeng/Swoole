<?php

namespace library;

class Facade
{
    protected static $_bind = [];
    protected static $_isNewInstance = false;

    public static function bind($name, $class = null)
    {
        if (is_array($name)) {
            static::$_bind = array_merge(static::$_bind, $name);
        } else {
            static::$_bind[$name] = $class;
        }
    }

    public static function createFacade()
    {
        $class = static::getFacadeClass();
        if (empty($class)) {
            throw new \RuntimeException('方法不存在');
        }
        if (isset(static::$_bind[$class])) {
            $class = static::$_bind[$class];
        }
        return Container::get($class, [], static::$_isNewInstance);
    }

    public static function getFacadeClass()
    {
    }

    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array([static::createFacade(), $method], $arguments);
    }

}