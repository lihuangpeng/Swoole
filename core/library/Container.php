<?php

namespace library;

use library\exception\ClassNotFindException;

/**
 * 容器类
 * Class Container
 */
class Container
{
    /**
     * 容器对象实例
     * @var Container
     */
    protected static $instance;

    /**
     * 容器中的对象实例
     * @var array
     */
    protected $instances = [];

    /**
     * 别名与类名关联关系
     * @var array
     */
    protected $bind = [
        'core' => Core::class,
        'log' => Log::class,
        'config' => Config::class,
        'cache' => Cache::class,
        'request' => Request::class
    ];

    private function __construct()
    {

    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public static function setInstance($instance)
    {
        self::$instance = $instance;
    }

    /**
     * 绑定一个类、闭包、实例、接口实现到容器
     * @param $alias
     * @param null $concrete
     */
    public static function set($alias, $concrete = null)
    {
        self::getInstance()->bindTo($alias, $concrete);
    }

    /**
     * 获取对象
     * @param $alias 类名/别名
     * @param array $vars 实例化构造函数参数
     * @param bool $newInstance 是否永远创建新对象，默认单例
     * @return object
     */
    public static function get($alias, $vars = [], $newInstance = false)
    {
        return self::getInstance()->make($alias, $vars, $newInstance);
    }

    /**
     * 绑定一个类、闭包、实例、接口实现到容器
     * @param $alias 类标识
     * @param null $concrete 要绑定的类、闭包或者实例
     */
    public function bindTo($alias, $concrete = null)
    {
        if (is_array($alias)) {
            $this->bind = array_merge($this->bind, $alias);
        } else if ($concrete instanceof \Closure) {
            $this->bind[$alias] = $concrete;
        } else if (is_object($concrete)) {
            if (isset($this->bind[$alias])) {
                $alias = $this->bind[$alias];
            }
            $this->instances[$alias] = $concrete;
        } else {
            $this->bind[$alias] = $concrete;
        }
    }

    /**
     * 通过类名或者别名获取对象
     * @param $alias 类名/别名
     * @param array $vars 参数
     * @param bool $newInstance 是否每次都使用新的对象
     * @return object
     */
    public function make($alias, $vars = [], $newInstance = false)
    {
        if ($vars === true) {
            $newInstance = true;
            $vars = [];
        }

        $alias = isset($this->bind[$alias]) ? $this->bind[$alias] : $alias;

        if (isset($this->instances[$alias]) && !$newInstance) {
            return $this->instances[$alias];
        }

        if (isset($this->bind[$alias])) {
            if ($this->bind[$alias] instanceof \Closure) {
                $object = $this->invokeFunction($this->bind[$alias], $vars);
            } else {
                return $this->make($this->bind[$alias], $vars, $newInstance);
            }
        } else {
            $object = $this->invokeClass($alias, $vars);
        }

        //使用新对象，不影响之前存在的对象
        if (!$newInstance) {
            $this->instances[$alias] = $object;
        }
        return $object;
    }

    /**
     * 根据类名获取对象
     * @param $alias
     * @param array $vars
     * @return mixed
     * @throws ClassNotFindException
     */
    public function invokeClass($alias, $vars = [])
    {
        try {
            $reflect = new \ReflectionClass($alias);
            $construct = $reflect->getConstructor();
            $args = $construct ? $this->_bindParams($construct, $vars) : [];
            return $reflect->newInstanceArgs($args);
        } catch (\ReflectionException $e) {
            throw new ClassNotFindException('class not exists:' . $alias);
        }
    }

    /**
     * 通过闭包获取对象
     * @param $function
     * @param array $vars
     * @return mixed
     * @throws \Exception
     */
    public function invokeFunction($function, $vars = [])
    {
        try {
            $reflect = new \ReflectionFunction($function);
            $args = $this->_bindParams($reflect, $vars);
            return call_user_func_array($function, $args);
        } catch (\ReflectionException $e) {
            throw new \Exception('function not exists:' . $function . '()');
        }
    }

    /**
     * 调用方法
     * @param $method
     * @param array $vars
     */
    public function invokeMethod($method, $vars = [])
    {
        try {
            if (is_array($method)) {
                if (is_object($method[0])) {
                    $object = $method[0];
                } else {
                    $object = $this->invokeClass($method[0]);
                }
                $reflect = new \ReflectionMethod($object, $method[1]);
            } else {
                $reflect = new \ReflectionMethod($method);
            }
            $args = $this->_bindParams($reflect, $vars);
            return $reflect->invokeArgs(isset($object) ? $object : null, $args);
        } catch (\ReflectionException $e) {
            $method = is_array($method) ? $method[1] : $method;
            throw new \Exception('method not exists:' . $method);
        }
    }

    /**
     * 构造函数参数设置
     * @param $construct \ReflectionMethod|\ReflectionFunction
     * @param $vars array
     * @return array
     */
    protected function _bindParams($construct, $vars)
    {
        if ($construct->getNumberOfParameters() == 0) {
            return [];
        }
        //重置指针
        reset($vars);
        //索引数组
        $i_vars = array_values($vars);
        $type = key($vars) === 0 ? 1 : 0;
        $args = [];
        $params = $construct->getParameters();
        foreach ($params as $key => $param) {
            $name = $param->getName();
            $class = $param->getClass(); //获取参数的反射类
            $tolowername = Loader::parseName($name);
            if ($class) {
                //获取对象类型的参数值
                $var = isset($vars[$name]) ? $vars[$name] : (isset($vars[$tolowername]) ? $vars[$tolowername] : (isset($i_vars[$key]) ? $i_vars[$key] : null) );
                $args[] = $this->_getObjectParam($class->getName(), isset($var) ? $var : null);
            } else if ($type === 1 && isset($i_vars[$key])) {
                $args[] = $i_vars[$key];
            } else if (isset($vars[$tolowername])) {
                $args[] = isset($vars[$tolowername]) ? $vars[$tolowername] : $i_vars[$key];
            } else if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException('method param miss :' . $name);
            }
        }
        return $args;
    }

    protected function _getObjectParam($class_name, $object)
    {
        if ($object instanceof $class_name) {
            return $object;
        }
        return $this->make($class_name);
    }

    public function delete($alias)
    {
        if(isset($this->instances[$alias])){
            unset($this->instances[$alias]);
        }
        if(isset($this->bind[$alias])){
            if(isset($this->instances[$this->bind[$alias]])){
                unset($this->instances[$this->bind[$alias]]);
            }
            unset($this->bind[$alias]);
        }
    }
}