<?php

namespace library\cache;

abstract class Driver
{
    protected $handle;

    protected $method_arr = [];

    abstract public function query($method, $args);

    public function __call($name, $arguments)
    {
    }
}