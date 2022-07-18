<?php

namespace library\config\driver;

use library\config\Driver;

class Json extends Driver
{
    protected $_config;
    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function parse()
    {
        if(is_file($this->_config)){
            $this->_config = file_get_contents($this->_config);
        }
        return json_decode($this->_config,true);
    }
}