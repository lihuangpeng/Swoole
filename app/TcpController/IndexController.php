<?php

namespace App\TcpController;


use App\Model\TestModel;

class IndexController extends BaseController
{
    public function index($data)
    {
        $params = $data['params'];
        return TestModel::where('id','=',$params['id'])->select();
    }
}