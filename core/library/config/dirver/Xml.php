<?php

namespace library\config\driver;

use library\config\Driver;

class Xml extends Driver
{
    protected $_config;
    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function parse()
    {
        if(is_file($this->_config)){
            $result = simplexml_load_file($this->_config);
        }else{
            $result = simplexml_load_string($this->_config);
        }
        return $result;
    }
}