<?php

namespace App\Task;

use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Queue\Job;
use App\Task\OnlineUser;
use EasySwoole\EasySwoole\ServerManager;

class QueueProcess extends AbstractProcess
{
    // 除了操作工当时生产的数据回送给自己一份(同时也放入队列中一份),其他的都需要加入队列
    protected function run($arg)
    {
        go(function (){
            MyQueue::getInstance()->consumer()->listen(function (Job $job){
                $que = $job->getJobData();
// print_r($que);
                $cate = empty($que['cate']) ? '' : $que['cate'];
                switch ($cate) {
                    case 'relation_add':
                        $this->addRelation($que);
                        break;
                    case 'inc':
                        # relation_over(一个产品的关联工位都完成)
                        # add_over 完工
                        $this->inc($que);
                        break;
                    case 'trycatch':
                        # 程序异常
                        break;
                    case 'online':
                    case 'offline':
                        # 上线
                        $this->onOffPush($que);
                        break;
                    case 'sub_fix':
                        # 设备报修
                        break;
                    case 'full':
                        # 全屏推送
                        $this->full($que);
                        break;
                    case 'admin':
                        # 管理推送
                        $this->adminPush($que);
                        break;
                }
            });
        });
    }

    // 客户端上下线推送
    private function onOffPush($data){
        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['tag'] === 'MD') {
                $server->push($v['fd'], $this->getEnArray(['code' => 10111, 'data' => $data]));
                break;
            }
        }
    }

    private function adminPush($data){
        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        if($data['rec_tags'] === 'ALL') {
            foreach ($users as $v) {
                if($v['tag'] === 'MS') {
                    $server->push($v['fd'], $this->getEnArray(['code' => 10112, 'data' => $data]));
                }
            }
        } else {
            $rec = explode(',', $data['rec_tags']);
            foreach ($users as $v) {
                if(in_array($v['tags'], $rec)) {
                    $server->push($v['fd'], $this->getEnArray(['code' => 10112, 'data' => $data]));
                }
            }
        }
    }

    private function inc($data){
        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['task_id'] === $data['task_id']) {
                $server->push($v['fd'], $this->getEnArray(['code' => 10111, 'data' => $data]));
            }
        }
    }

    private function getEnArray($data){
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


    // 无法生效,暂时放弃
    private function full($data){
        $data['tags'] = 'MD004';
        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['tags'] === $data['tags']) {
                $server->push($v['fd'], $this->getEnArray(['code' => 10110, 'data' => $data]));
                break;
            }
        }
    }

    private function addRelation($data){
        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['task_id'] === $data['task_id'] || $v['tag'] === 'MD') {
                $server->push($v['fd'], $this->getEnArray(['code' => 10109, 'data' => $data]));
            }
        }
    }
}
