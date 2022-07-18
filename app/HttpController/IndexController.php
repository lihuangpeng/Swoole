<?php

namespace App\HttpController;


use App\Model\TestModel;
use App\Task\RequestTask;
use library\Container;
use library\mswoole\RpcClient;
use library\mswoole\ServerManager;
use library\mswoole\TaskManager;
use library\orm\MysqlPool;
use library\Request;
use Swoole\Coroutine;

class IndexController extends BaseController
{
    public function index(Request $request)
    {
        $rpc = new RpcClient('index',1);
        $result = $rpc->call('index',$request->request());
        return $result;
    }
}