<?php

namespace App\TcpController;

use library\Controller;
use library\facade\Config;

class BaseController extends Controller
{
    public function onRequest($request)
    {
        //验证签名
        $secret = Config::get('app.tcp.secret');
        if ($secret && !check_sign($request['params'], $secret)) {
            return ['code' => 403, 'message' => 'sign auth fail', 'data' => []];
        }
        return parent::onRequest($request); // TODO: Change the autogenerated stub
    }
}