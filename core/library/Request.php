<?php

namespace library;

use Swoole\Coroutine;

class Request
{
    protected static $request;

    public function __construct()
    {
    }

    public function set(\Swoole\Http\Request $request)
    {
        $cid = Coroutine::getCid();
        if (empty(self::$request[$cid])) {
            self::$request[$cid] = $request;
        }
        return $this;
    }

    public function del()
    {
        $cid = Coroutine::getCid();
        unset(self::$request[$cid]);
    }

    public function getAllRequest()
    {
        return self::$request;
    }

    public function getSwooleRequest($cid)
    {
        if ((int)$cid <= 0) {
            return null;
        }
        if (empty(self::$request[$cid])) {
            return $this->getSwooleRequest(Coroutine::getPCid());
        } else {
            return self::$request[$cid];
        }
    }

    public function request($param = '', $default = '')
    {
        $cid = Coroutine::getCid();
        $request = $this->getSwooleRequest($cid);
        if (empty($param)) {
            return array_merge(empty($request->get) ? array() : $request->get, empty($request->post) ? array() : $request->post);
        }
        if (isset($request->post[$param])) {
            return $request->post[$param];
        } else if (isset($request->get[$param])) {
            return $request->get[$param];
        } else if (!empty($default)) {
            return $default;
        }
        return null;
    }

    public function action($name = '')
    {
        $cid = Coroutine::getCid();
        $request = $this->getSwooleRequest($cid);
        if(!empty($name)){
            $request->request['request_action'] = $name;
            return true;
        }else{
            if(isset($request->request['request_action'])){
                return $request->request['request_action'];
            }
            return null;
        }
    }

    public function controller($name = ''){
        $cid = Coroutine::getCid();
        $request = $this->getSwooleRequest($cid);
        if(!empty($name)){
            $request->request['request_controller'] = $name;
            return true;
        }else{
            if(isset($request->request['request_controller'])){
                return $request->request['request_controller'];
            }
            return null;
        }
    }

    public function get($param = '', $default = '')
    {
        $cid = Coroutine::getCid();
        $request = $this->getSwooleRequest($cid);
        if (empty($param)) {
            return $request->get;
        }
        if (isset($request->get[$param])) {
            return $request->get[$param];
        } else if (!empty($default)) {
            return $default;
        }
        return null;
    }

    public function post($param = '', $default = '')
    {
        $cid = Coroutine::getCid();
        $request = $this->getSwooleRequest($cid);
        if (empty($param)) {
            return $request->gpost;
        }
        if (isset($request->post[$param])) {
            return $request->post[$param];
        } else if (!empty($default)) {
            return $default;
        }
        return null;
    }

    public function server($param = '', $default = '')
    {
        $cid = Coroutine::getCid();
        $request = $this->getSwooleRequest($cid);
        if (empty($param)) {
            return $request->server;
        }
        if (isset($request->server[$param])) {
            return $request->server[$param];
        } else if (!empty($default)) {
            return $default;
        }
        return null;
    }

    public function recycleRequest()
    {
        if (empty(self::$request)) return false;
        foreach (self::$request as $cid => $request) {
            if (!Coroutine::exists($cid)) {
                unset(self::$request[$cid]);
            }
        }
        return true;
    }


}