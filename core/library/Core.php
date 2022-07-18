<?php

namespace library;

use library\mswoole\ServerManager;
use SwooleEvent;
use Config;
use library\facade\Request;
use library\facade\Log;

class Core
{

    protected $is_dev = true;

    public function __construct()
    {

    }

    public function setDev($dev = true)
    {
        if ($dev !== true) {
            $this->is_dev = false;
        } else {
            $this->is_dev = true;
        }
        return $this;
    }

    public function getDev()
    {
        return $this->is_dev;
    }

    public function run()
    {
        if (!$this->getDev()) {
            $config = require_once MSwooleRoot . '/product.php';
        } else {
            $config = require_once MSwooleRoot . '/dev.php';
        }
        Config::set($config, 'swoole');
        require_once dirname(dirname(__DIR__)) . '/SwooleEvent.php';
    }

    public function globalInitialize()
    {
        SwooleEvent::initialize();
        return $this;
    }

    //创建服务
    public function createServer()
    {
        return ServerManager::getInstance()->start();
    }

    //路由切换
    public function dispatcher($data, $type = 'http')
    {
        return Container::getInstance()->invokeMethod([$this, $type . 'Dispatcher'], [$data]);
    }

    //http请求处理
    public function httpDispatcher($request)
    {
        $config = Config::get('app.http');
        $uri = Request::server('path_info');
        $arr = explode('/', trim($uri,'/'));
        $controller = !empty($arr[0]) ? $arr[0] : $config['default_controller'];
        $action = !empty($arr[1]) ? $arr[1] : $config['default_action'];
        Request::controller($controller);
        Request::action($action);
        try {
            $result = Container::getInstance()->invokeMethod([$config['namespace'] . strtoupper($controller) . $config['postfix'], 'onRequest'], [Container::get('request')]);
            Log::info('http_'.$controller.'_'.$action, ['params'=>$request,'result'=>$result], 'INFO',['request_uri'=>$uri,'ip'=>get_client_ip(Request::server())]);
            return $result;
        } catch (\Throwable $exception) {
            //加入日志
            Log::info('http_error', ['exception' => $exception->getMessage(),'params'=>$request], 'ERROR',['request_uri'=>$uri,'ip'=>get_client_ip(Request::server())]);
            throw $exception;
        }
    }

    //tcp请求处理
    public function tcpDispatcher($data)
    {
        $config = Config::get('app.tcp');
        $data['c'] = !empty($data['c']) ? $data['c'] : $config['default_controller'];
        $data['a'] = !empty($data['a']) ? $data['a'] : $config['default_action'];
        try {
            $result = Container::getInstance()->invokeMethod([$config['namespace'] . strtoupper($data['c']) . $config['postfix'], 'onRequest'], [$data]);
            Log::info('tcp_'.$data['c'].'_'.$data['a'], ['params'=>$data,'result'=>$result], 'INFO');
            return $result;
        } catch (\Throwable $exception) {
            //加入日志
            Log::info('tcp_error', ['exception' => $exception->getMessage(),'params'=>$data], 'ERROR');
            throw $exception;
        }
    }

    public function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        $label = (null === $label) ? '' : rtrim($label) . ':';
        if ($var instanceof Model || $var instanceof ModelCollection) {
            $var = $var->toArray();
        }

        ob_start();
        var_dump($var);

        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $label . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, $flags);
            }
            $output = '<pre>' . $label . $output . '</pre>';
        }
        if ($echo) {
            echo($output);
            return;
        }
        return $output;
    }
}
