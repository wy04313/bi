<?php

namespace App\WebSocket\Controller;

use App\Model\WMesTaskModel;
use EasySwoole\Mysqli\QueryBuilder;

class Task extends Base
{
    public function getUseNumber(){
        $params = $this->caller()->getArgs();
        if(empty($params['task_id'])) return $this->response()->setMessage($this->toJson(10202, '', '/mes/index/lgout'));

        $task_id = intval($params['task_id']);
        $task = WMesTaskModel::create()
            ->field(['relation_over','number', 'over_num','effect'])
            ->where(['id' => $task_id])
            ->get()
            ->toArray();
        if(!$task['effect'])
            return $this->response()->setMessage($this->toJson(10107,'操作失败提示', [
                'content' => '生产任务已关闭.不可再生产.',
                'local' => 'ALL',
            ]));
        $count = $task['number'] - $task['relation_over'];
        if($count > 0) {
            return $this->response()->setMessage($this->toJson(10108,'远程服务器连接成功', [
                'content' => '此任务还可再生产 '.$count.' 套',
                'over_num' => $task['over_num'],
                'relation_over' => $task['relation_over'],
            ]));
        } else {
            return $this->response()->setMessage($this->toJson(10107,'操作失败提示', [
                'content' => '产量已超限,不可再生产.',
                'over_num' => $task['over_num'],
                'relation_over' => $task['relation_over'],
            ]));
        }
    }

}
