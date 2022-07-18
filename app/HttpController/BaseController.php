<?php

namespace App\HttpController;

use library\Controller;
use library\Request;
use library\facade\Config;

class BaseController extends Controller
{
    /**
     * @param Request $request
     * @return array|mixed
     */
    public function onRequest($request)
    {
        //验证签名
        $secret = Config::get('app.http.secret');
        if ($secret && !check_sign($request->request(), $secret)) {
            return ['code' => 403, 'message' => 'sign auth fail', 'data' => []];
        }
        return parent::onRequest($request);
    }
}