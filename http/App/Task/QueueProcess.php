<?php

namespace App\Task;

use EasySwoole\Component\Process\AbstractProcess;
use App\MongoDb\Driver;
use App\MongoDb\MongoClient;
use EasySwoole\Queue\Job;
// use EasySwoole\EasySwoole\ServerManager;
// use App\Task\OnlineUser;
// use App\Model\WDataModel;

class QueueProcess extends AbstractProcess
{

    protected function run($arg)
    {
        go(function (){

            $queue = MyQueue::getInstance();
// var_dump($queue).PHP_EOL;

        });
    }



    // 除了操作工当时生产的数据回送给自己一份(同时也放入队列中一份),其他的都需要加入队列
    // protected function run($arg)
    // {
    //     go(function (){
    //         MyQueue::getInstance()->consumer()->listen(function (Job $job){
    //             $que = $job->getJobData();
    //             $cate = empty($que['cate']) ? '' : $que['cate'];
    //             $users = OnlineUser::getInstance()->table();
    //             $server = ServerManager::getInstance()->getSwooleServer();

    //             switch ($cate) {
    //                 case 'lessMaterial':# 缺料全页面推送
    //                     foreach ($users as $v) {
    //                         $server->push($v['fd'], $this->whiteToJson($que['data'], 'ok'));
    //                     }
    //                     break;
    //                 case 'line3302':# line数据至页面
    //                 case 'line3305':
    //                 case 'line3306':
    //                 case 'line3307':
    //                     foreach ($users as $v) {
    //                         if($v['page_name'] === $cate)
    //                             $server->push($v['fd'], $this->whiteToJson($this->getLinePageData($cate), 'ok'));
    //                     }
    //                     break;
    //                 case 'jump':# 跳转
    //                     foreach ($users as $v) {
    //                         $server->push((INT)$que['data']['fd'], $this->whiteToJson(['url' => $que['data']['url']], 'jump'));
    //                     }
    //                     break;
    //             }
    //         });
    //     });
    // }


    private function whiteToJson($data,$case = 'ok'){
        return json_encode(compact('case','data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


}
