<?php
namespace  App\HttpController\Learn;
use EasySwoole\Http\AbstractInterface\Controller;

/**
 * Created by PhpStorm.
 * User: zq2020
 * Date: 20-2-27
 * Time: 下午3:2
 * */
class  Redis extends  Controller{


    public function index()
    {

        $redis=\EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        if(!$res=$redis->get("name")){
            $redis->set("name","zq");
            $redis->expire("name",400);
        }

        $res=$redis->get("name");
        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

        return $this->writeJson(200,$res);

    }


    public function  push(){
        $redis=\EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
         $redis->lpush("list",time());
        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
        return $this->writeJson(0,"push list success");
    }

    public function  pop(){
        $redis=\EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $result=$redis->rpop("list");
        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
        return $this->writeJson(0,$result);
    }
}
