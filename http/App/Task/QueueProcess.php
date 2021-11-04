<?php

namespace App\Task;

use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Queue\Job;
use App\Task\Push;
use EasySwoole\EasySwoole\ServerManager;

class QueueProcess extends AbstractProcess
{
    // 除了操作工当时生产的数据回送给自己一份(同时也放入队列中一份),其他的都需要加入队列
    protected function run($arg)
    {
        go(function (){
            MyQueue::getInstance()->consumer()->listen(function (Job $job){
                $que = $job->getJobData();
                $cate = empty($que['cate']) ? '' : $que['cate'];
        // print_r($que);
                switch ($cate) {
                    case 'station_log':# 将mongodb中的 数据,重复的删除
                        $this->stationLog($que['data']);
                        break;
                    // case 'today_task':# 管理推送
                    //     $this->todayTask($que['cate'],$que['data']);
                    //     break;
                    case 'line':# 推送line数据至页面
                        Push::getInstance()->pushData('line','line');
                        break;

                }
            });
        });
    }

    // 临时用,废弃stationLag表后可删除
    protected function stationLog($data){

    }

    protected function whiteToJson($code = 0, $msg = '操作成功!', $data = [],$case = ''){
        return json_encode(compact('code','msg','data','case'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


}
