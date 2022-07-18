<?php

namespace library\config\driver;

use library\config\Driver;

class Ini extends Driver
{
    protected $_config;
    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function parse()
    {
        if(is_file($this->_config)){
            return parse_ini_file($this->_config);
        }else{
            return parse_ini_string($this->_config);
        }
    }
}