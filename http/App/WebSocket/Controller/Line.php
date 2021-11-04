<?php

namespace App\WebSocket\Controller;

use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Http\AbstractInterface\Controller;

use App\Model\WPagesOnlineModel;
use App\Task\OnlineUser;

class Line extends Base
{
    // line 页面上方块数据
    public function getBlockData()
    {
        $params = $this->caller()->getArgs();
        $where[] = empty($params['cost_type']) ? [] : explode(',', $params['cost_type']);

        $b1 = 105; //上工人数
        $b2 = 88.33; //温度
        $b3 = 33; //湿度
        $b4 = 345; //完工
        $b5 = 3432;// 已关联
        $b6 = 3; //不良品
        $this->response()->setMessage($this->whiteToJson(0,'OK',compact('b1','b2','b3','b4','b5','b6'),  'block_data'));
    }

    // 只获取最后一次即可
    public function todayTask(){
        $title = "HVB(20pcs),VPU2.3(50pcs)";
        $per = 0;
        $this->response()->setMessage($this->whiteToJson(0,'OK',compact('title','per'),  'today_task'));
    }

    public function nowLine(){
        // {title:'102SC2108024(CDU233A)',data_x:['P板','K板','Q板','绝缘耐压测试','下线测试'],data_y1:[300,300,300,300,300],data_y2:[300,95,0,118,2],data_y3:[11,44,16,0,80]}
        $title = "102SC2108024(CDU233A)";
        $data_x = ['P板','K板','Q板','绝缘耐压测试','下线测试'];
        $data_y1 = [400,400,400,400,400];
        $data_y2 = [300,95,0,118,2];
        $data_y3 = [11,44,16,55,80];
        $this->response()->setMessage($this->whiteToJson(0,'OK',compact('title','data_x','data_y1','data_y2','data_y3'),  'now_line'));
    }
}
