<?php

namespace library;

use library\exception\FileExistsException;

class Config
{
    protected static $_config = [];
    protected $_path = '';
    protected $_ext = '';
    protected $_prefix = 'app';


    public function __construct($path = '', $ext = '.php')
    {
        $this->_path = $path;
        $this->_ext = $ext;
    }

    public function set($name, $value = null)
    {
        if (is_string($name)) {
            if (strpos($name, '.') === false) {
                $name = $this->_prefix . '.' . $name;
            }
            $name = explode('.', $name, 3);
            if (count($name) == 2) {
                self::$_config[$name[0]][$name[1]] = $value;
            } else {
                self::$_config[$name[0]][$name[1]][$name[2]] = $value;
            }
            return $value;
        } elseif (is_array($name)) {
            if (!empty($value)) {
                if (isset(self::$_config[$value])) {
                    $result = array_merge(self::$_config[$value], $name);
                } else {
                    $result = self::$_config[$value] = $name;
                }
            } else {
                $result = self::$_config = array_merge(self::$_config, $name);
            }
        } else {
            $result = self::$_config;
        }
        return $result;
    }

    /**
     * 一级配置获取
     * @param $name
     * @return array|mixed
     */
    public function pull($name)
    {
        if (isset(self::$_config[$name])) {
            return self::$_config[$name];
        }
        return array();
    }

    /**
     * 配置移除
     * @param $name
     * @return void
     */
    public function remove($name)
    {
        if (is_array($name)) {
            foreach ($name as $value) {
                if (isset(self::$_config[$value])) {
                    unset(self::$_config[$value]);
                }
            }
        } elseif (is_string($name)) {
            if (strpos($name, '.') === false) {
                $name = $this->_prefix . '.' . $name;
            }
            $name = explode('.', $name, 3);
            if (count($name) == 2) {
                unset(self::$_config[$name[0]][$name[1]]);
            } else {
                unset(self::$_config[$name[0]][$name[1]][$name[2]]);
            }
        }
    }

    /**
     * 多级配置获取
     * @param $name
     * @param $default
     */
    public function get($name = null, $default = null)
    {
        if (empty($name)) {
            return self::$_config;
        }
        if (strpos($name, '.') === false) {
            $name = $this->_prefix . '.' . $name;
        }

        if (substr($name, -1) === '.') {
            return $this->pull(substr($name, 0, strlen($name) - 1));
        }
        $name = explode('.', $name);
        $config = self::$_config;

        foreach ($name as $value) {
            if (isset($config[$value])) {
                $config = $config[$value];
            } else {
                return $default;
            }
        }
        return $config;
    }

    /**
     * 加载内容进入配置
     * @param $file
     * @param null $name
     * @return array
     * @throws FileExistsException
     */
    public function load($file, $name = null)
    {
        if (!is_file($file)) {
            $file = $this->_path . DIRECTORY_SEPARATOR . $file . $this->_ext;
        }
        if (!file_exists($file)) {
            throw new FileExistsException('file not exists:' . $file);
        }
        return $this->loadFile($file, $name);
    }

    /**
     * 加载文件内容驱动
     * @param $file
     * @param null $name
     * @return array
     */
    protected function loadFile($file, $name = null)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (strtolower($ext) == 'php') {
            return $this->set(include $file, $name);
        } else {
            $obj = Loader::factory($ext, 'library\\config\\drive\\', $file);
            $config = $obj->parase();
            return $this->set($config, $name);
        }
    }

    /**
     * 加载配置
     * @param $dir
     */
    public function loadDir($dir)
    {
        $config_list = scandir($dir);
        foreach ($config_list as $file) {
            if ($file == '.' || $file == '..') continue;
            $name = pathinfo($file, PATHINFO_FILENAME);
            Container::get('config')->load($dir . DIRECTORY_SEPARATOR . $file, $name);
        }
    }

    /**
     * 判断是否有配置
     * @param $name
     */
    public function has($name)
    {
        if(empty($name)){
            return false;
        }
        return !is_null($this->get($name));
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }
}