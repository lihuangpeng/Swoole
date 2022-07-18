<?php

namespace library;

abstract class Controller
{
    //http请求处理
    public function onRequest($request)
    {
        if($request instanceof Request){
            $action = $request->action();
            return Container::getInstance()->invokeMethod([$this, $action], $request->request());
        }else{
            return Container::getInstance()->invokeMethod([$this, $request['a']], [$request]);
        }
    }
}