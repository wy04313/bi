<?php

namespace App\HttpController;

use App\Task\MyQueue;
use EasySwoole\Queue\Job;
use App\Model\WMesRelationModel;
use EasySwoole\EasySwoole\ServerManager;
use App\Task\PullProduct;
use App\Task\OnlineUser;

class Push extends Base
{
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

    public function clearSameName(){
        $request = $this->request();
        $data = $request->getRequestParam();
        if(isset($data['tags'])) {
            $online = OnlineUser::getInstance();
            $users = $online->table();
            $server = ServerManager::getInstance()->getSwooleServer();
            foreach ($users as $v) {
                if($v['tags'] === $data['tags']) {
                    $online->delete($v['fd']);
                    $server->push($v['fd'],json_encode([
                        'code' => 10302,
                        'msg' => '',
                        'data' => '/mes/index/lgout'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    break;
                }
            }
            // sess会覆盖,只删除fd
        }
    }

    public function dayData(){
        $a = new PullProduct();
        $start_date = '2021-03-13';
        $start = strtotime($start_date);
        for ($i=0; $i < 61; $i++) {
            $a->dayData(strtotime("+{$i} day", $start));
        }

    }

    // 以下为测试数据
    public function adminPush(){
        $data = [
            'cate' => 'admin',
            'full' => 1,
            'rec_tags' => 'MS001',
            'title' => '封志文',
            'content' => '封志文你是个大山炮还是个骚货?',
        ];
        $job = new Job();
        $job->setJobData($data);
        MyQueue::getInstance()->producer()->push($job);
        $this->writeJson();
    }


    public function inc(){
        $data = [
            'cate' => 'inc',
            'task_id' => 22,
            'cls' => 'relation_over', // 关联
            // 'cls' => 'over_num',         // 完工
        ];
        $job = new Job();
        $job->setJobData($data);
        MyQueue::getInstance()->producer()->push($job);
        $this->writeJson();
    }

    // 无法生效,暂时放弃
    public function full(){
        $data = [
            'cate' => 'full',
            'tags' => 'MS001',
        ];
        $job = new Job();
        $job->setJobData($data);
        MyQueue::getInstance()->producer()->push($job);
        $this->writeJson();
    }

    public function addRelation(){
        $data = [
            'cate' => 'relation_add',
            'task_id' => 22,
            'worker' => '猪志文',
            'relations' => [
                ['main_barcode' => 'P02S020000-C12X133'.date('s'),
                    'relate_barcode' => 'DD21079'.rand(10, 99),
                    'created' => date('m H:i'),
                    'station_id_cn' => '吃翔',
                    'worker_id_cn' => '封志文'
                ],
                ['main_barcode' => 'P02S020000-C124443'.rand(10, 99),
                    'relate_barcode' => '5635220'.date('s'),
                    'created' => date('m H:i'),
                    'station_id_cn' => '拉翔',
                    'worker_id_cn' => '王勇'
                ],
            ]
        ];
        $job = new Job();
        $job->setJobData($data);
        MyQueue::getInstance()->producer()->push($job);
        $this->writeJson();
    }
}
