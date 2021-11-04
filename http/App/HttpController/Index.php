<?php

namespace App\HttpController;

use App\MongoDb\Driver;
use App\MongoDb\MongoClient;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Utility\Random;
use App\Model\WTest2021Model;

class Index extends Controller
{
    public function index()
    {
        // WPagesOnlineModel::create()->update(['type' => 90], ['id' => 1]);
        $ips = WTest2021Model::create()->where([1,2,3])->all();
        print_r($ips);

    }
}
