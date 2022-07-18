<?php

namespace library;

use Prophecy\Exception\InvalidArgumentException;
use library\exception\ClassNotFindException;

class Loader
{
    /**
     * 类名映射信息
     * @var array
     */
    protected static $classMap = []; //classmap 指定类文件所在目录即可自动加载  例如你需要使用一个类，你只需要classmap指向类的目录即可

    /**
     * PSR-4
     * @var array
     */
    private static $prefixLengthsPsr4 = []; //psr4 前缀长度
    private static $prefixDirsPsr4 = []; //psr4 前缀关联数组

    /**
     * PSR-0
     * @var array
     */
    private static $prefixesPsr0 = []; //psr0 前缀关联数组

    /**
     * 自动加载的类的目录
     */
    private static $autoLoadDir = [];

    /**
     * 需要加载的文件
     * @var array
     */
    private static $files = []; //所需要加载的文件路径

    /**
     * 类别名
     * @var array
     */
    private static $classAlias = [];

    /**
     * Composer安装路径
     * @var string
     */
    private static $composerPath;

    //获得应用根目录
    static public function getRootPath()
    {
        if (PHP_SAPI == 'cli') {
            $scriptName = realpath($_SERVER['argv'][0]);
        } else {
            $scriptName = $_SERVER['SCRIPT_FILENAME'];
        }
        $rootPath = realpath(dirname(dirname($scriptName)));
        return $rootPath . DIRECTORY_SEPARATOR;
    }

    static public function register($autoload = '')
    {
        //自定义自动加载
        spl_autoload_register($autoload ? $autoload : __NAMESPACE__ . '\Loader::autoload', true, true);

        $rootPath = self::getRootPath();
        self::$composerPath = $rootPath . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR;
        //composer自动加载支持
        if (is_dir(self::$composerPath)) {
            if (file_exists(self::$composerPath . 'autoload_static.php')) {
                require_once(self::$composerPath . 'autoload_static.php');
                $declaredClass = get_declared_classes();//得到所有加载的类
                $declaredClass = array_pop($declaredClass);//取得最近加载的类的返回值
                $composerProp = ['prefixLengthsPsr4', 'prefixDirsPsr4', 'fallbackDirsPsr4', 'prefixesPsr0', 'fallbackDirsPsr0', 'files', 'classMap'];
                foreach ($composerProp as $attr) {
                    if (property_exists($declaredClass, $attr)) {
                        self::${$attr} = $declaredClass::${$attr};
                    }
                }
            } else {
                self::registerComposerLoader(self::$composerPath);
            }
        }
        //注册library功能包
        self::addNameSpace(
            ['library' => __DIR__]
        );
        //注册项目代码包
        self::addNameSpace(
            ['App' => dirname(dirname(__DIR__)) . '/app']
        );
        //注册extend空间
        self::addAutoLoadDir($rootPath . 'extend');
    }

    //注册composer自动加载
    static public function registerComposerLoader($composerPath)
    {
        if (is_file($composerPath . 'autoload_namespaces.php')) {
            $map = require_once($composerPath . 'autoload_namespaces.php');
            foreach ($map as $namespace => $path) {
                self::addPsr0($namespace, $path);
            }
        }

        if (is_file($composerPath . 'autoload_psr4.php')) {
            $map = require_once($composerPath . 'autoload_psr4.php');
            foreach ($map as $namespace => $path) {
                self::addPsr4($namespace, $path);
            }
        }

        if (is_file($composerPath . 'autoload_classmap.php')) {
            $classMap = require_once($composerPath . 'autoload_classmap.php');
            if ($classMap) {
                self::addClassMap($classMap);
            }
        }

        if (is_file($composerPath . 'autoload_files.php')) {
            self::$files = require_once($composerPath . 'autoload_files.php');
        }
    }

    /**
     * 添加psr0自动加载空间
     * @param $prefix string|null //使用的命名空间前缀
     * @param $path string|array  //新增路径
     * @param bool $prepend //新增空间放前面还是后面
     */
    static public function addPsr0($prefix, $path, $prepend = false)
    {
        if (!$prefix) {
            if ($prepend) {
                self::$autoLoadDir = array_merge(
                    (array)$path,
                    self::$autoLoadDir
                );
            } else {
                self::$autoLoadDir = array_merge(
                    self::$autoLoadDir,
                    (array)$path
                );
            }
            return true;
        }
        $first = $prefix[0];
        if (!isset(self::$prefixesPsr0[$first][$prefix])) {
            self::$prefixesPsr0[$first][$prefix] = (array)$path;
            return true;
        }
        if ($prepend) {
            self::$prefixesPsr0[$first][$prefix] = array_merge(
                (array)$path,
                self::$prefixesPsr0[$first][$prefix]
            );
        } else {
            self::$prefixesPsr0[$first][$prefix] = array_merge(
                self::$prefixesPsr0[$first][$prefix],
                (array)$path
            );
        }
        return true;
    }

    /**
     * 添加psr4自动加载空间
     * @param $prefix //使用的命名空间前缀
     * @param $path //新增路径
     * @param bool $prepend //新增空间放前面还是后面
     */
    static public function addPsr4($prefix, $path, $prepend = false)
    {
        if (!$prefix) {
            if ($prepend) {
                self::$autoLoadDir = array_merge(
                    (array)$path,
                    self::$autoLoadDir
                );
            } else {
                self::$autoLoadDir = array_merge(
                    self::$autoLoadDir,
                    (array)$path
                );
            }
            return true;
        } elseif (!isset(self::$prefixDirsPsr4[$prefix])) {
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new InvalidArgumentException('A non-empty PSR-4 prefix must end with a namespace separator.');
            }
            self::$prefixDirsPsr4[$prefix] = (array)$path;
            self::$prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
        } elseif ($prepend) {
            self::$prefixDirsPsr4[$prefix] = array_merge(
                (array)$path,
                self::$prefixDirsPsr4[$prefix]
            );
        } else {
            self::$prefixDirsPsr4[$prefix] = array_merge(
                self::$prefixDirsPsr4[$prefix],
                (array)$path
            );
        }
        return true;
    }

    /**
     * 添加类库映射自动加载空间
     * 类库加载
     * @param $classMap
     */
    static public function addClassMap($class, $map = '')
    {
        if (is_array($class)) {
            self::$classMap = array_merge(
                self::$classMap,
                $class
            );
        } else {
            self::$classMap[$class] = $map;
        }
    }

    static public function addClassAlias($alias, $class = null)
    {
        if (is_array($alias)) {
            self::$classAlias = array_merge(self::$classAlias, $alias);
        } else {
            self::$classAlias[$alias] = $class;
        }
    }

    // 注册自动加载类库目录
    static public function addAutoLoadDir($path)
    {
        self::$autoLoadDir[] = $path;
    }


    static public function addNameSpace($namespace, $path = '')
    {
        if (is_array($namespace)) {
            foreach ($namespace as $prefix => $paths) {
                self::addPsr4($prefix . '\\', $paths, true);
            }
        } else {
            self::addPsr4($namespace . '\\', $path, true);
        }
    }

    static public function addAutoFile($path)
    {
        if (file_exists($path)) {
            $unique_key = md5($path);
            if (empty(self::$files[$unique_key])) {
                self::$files[$unique_key] = $path;
            }
        }
        return false;
    }

    //自定义自动加载函数
    static protected function autoload($class)
    {
        if (isset(self::$classAlias[$class])) {
            $result = class_alias(self::$classAlias[$class], $class);
            return $result;
        }
        if ($file = self::findFile($class)) {
            //使用window环境也严格区分大小写 realpath表示获取到真实路径，区分大小写
            if (strpos(PHP_OS, 'WIN') !== false && pathinfo($file, PATHINFO_FILENAME) !== pathinfo(realpath($file), PATHINFO_FILENAME)) {
                return false;
            }
            _include_file($file);
            return true;
        }
        throw new ClassNotFindException($class . ' not find');
    }

    static public function loadComposerAutoloadFiles()
    {
        foreach (self::$files as $unique_key => $file) {
            if (file_exists($file) && empty($GLOBALS['__composer_autoload_files'][$file])) {
                _include_file($file);
                $GLOBALS['__composer_autoload_files'][$file] = true;
            }
        }
    }

    //根据自动加载保存属性加载
    static protected function findFile($class)
    {
        //类库映射
        if (!empty(self::$classMap[$class])) {
            return self::$classMap[$class];
        }
        $logicalPathPsr4 = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        $first = $class[0];
        //psr-4
        if (isset(self::$prefixLengthsPsr4[$first])) {
            foreach (self::$prefixLengthsPsr4[$first] as $prefix => $length) {
                if (strpos($class, $prefix) === 0) {
                    foreach (self::$prefixDirsPsr4[$prefix] as $dir) {
                        if (is_file($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }
        //autoDir 例如extend目录
        foreach (self::$autoLoadDir as $path) {
            if (is_file($file = $path . DIRECTORY_SEPARATOR . $logicalPathPsr4)) {
                return $file;
            }
        }

        // 查找 PSR-0
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            //将最后一个为\前面内容提取出来包括，将后面包括_部分也替换成\
            $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1)
                . strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR);
        } else {
            // PEAR-like class name
            $logicalPathPsr0 = strtr($class, '_', DIRECTORY_SEPARATOR) . '.php';
        }

        //psr-0
        if (isset(self::$prefixesPsr0[$first])) {
            foreach (self::$prefixesPsr0[$first] as $prefix => $prefixesPsr0Dir) {
                if (strpos($class, $prefix) === 0) {
                    foreach ($prefixesPsr0Dir as $key => $dir) {
                        if (is_file($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                            return $file;
                        }
                    }
                }
            }
        }

        return self::$classMap[$class] = false;
    }

    /**
     * 变量命名风格转化
     * @param $name
     * @param $type
     * @param bool $ucfirst
     */
    public static function parseName($name, $type = 0, $ucfirst = false)
    {
        //将user_name => userName/UserName
        if ($type) {
            $result = preg_replace('/_([a-z])/', strtoupper("\\1"), $name);
            return $ucfirst ? ucfirst($result) : lcfirst($result);
        }
        //将UserName => user_name
        return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
    }

    /**
     * 工厂类生成
     * @param $type
     * @param $path
     * @param $newInstance
     */
    public static function factory($type, $path = '', ...$args)
    {
        $class = strpos($type, '\\') !== false ? $type : $path . ucwords($type);
        if (class_exists($class)) {
            return Container::getInstance()->invokeClass($class, $args);
        } else {
            throw new ClassNotFindException('class not exists:' . $class);
        }
    }
}

//作用范围隔离
function _include_file($file)
{
    return include_once $file;
}

function _require_file($file)
{
    return require_once $file;
}