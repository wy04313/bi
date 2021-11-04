<?php

namespace App\HttpController;

use App\Task\MyQueue;
use EasySwoole\Queue\Job;
use App\Task\Push;

class PushData extends Base
{
    // 使用队列推送
    public function addQueue(){
        $request = $this->request();
        $data = $request->getRequestParam();
        if(empty($data['cate'])){
            $this->writeJson(404);
        } else {
            $job = new Job();
            $job->setJobData($data);
            MyQueue::getInstance()->producer()->push($job);
            $this->writeJson();
        }

    }

    // 使用进程推送
    public function push(){
        $request = $this->request();
        $data = $request->getRequestParam();
        $data = [
            'page' => 'line', //推送的页面,目前有line total
            'field' => 'line', //推送的字段 总成line
        ];

        go(function() use($data){
            Push::getInstance()->pushData($data['field'],$data['page']);
        });

        $this->writeJson();
    }

    public function pushUrl(){
        $request = $this->request();
        $data = $request->getRequestParam();
        go(function() use($data){
            Push::getInstance()->pushUrl($data['ip'],$data['url']);
        });
        $this->writeJson();
    }


    // 以下为测试推送数据
    public function todayTask(){
        $job = new Job();
        $job->setJobData([
            'cate' => 'today_task', //这个值必须和前端case 中的相同
            'data' => [
                'title' => '封志文你是个大山炮还是个骚货?'.date('H:i:s'),
                'per' => rand(1, 89),
            ],
        ]);
        MyQueue::getInstance()->producer()->push($job);
        $this->writeJson();
    }
}
