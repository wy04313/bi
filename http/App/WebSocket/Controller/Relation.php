<?php

namespace App\WebSocket\Controller;

use App\Model\WMesRelationModel;
use App\Model\WMesBarcodeModel;
use App\Model\WMesTaskModel;
use App\Tools\Redis;
use EasySwoole\Mysqli\QueryBuilder;
use App\Task\MyQueue;
use EasySwoole\Queue\Job;

class Relation extends Base
{
    private function addQueue($data, &$sess){
        $psData = [];
        if($data === 'station_over') {
            $psData = [
                'cate' => 'relation_over',
                'task_id' => $sess['task']['task_id'],
                'inv_name' => $sess['task']['inv_name'],
                'time' => time(),
            ];
        } else {
            $psData['cate'] = 'relation_add';
            $psData['task_id'] = $sess['task']['task_id'];
            foreach ($data as $v) {
                #操作工 工位 主码 关联码 (时间待定,任务id)
                $psData['relations'][] = [
                    'worker_id_cn' => $sess['task']['worker_id_cn'],
                    'station_id_cn' => $sess['task']['station_id_cn'],
                    'main_barcode' => $v['main_barcode'],
                    'relate_barcode' => $v['relate_barcode'],
                    'created' => date('m H:i', $v['created'])
                ];
            }
        }

        $job = new Job();
        $job->setJobData($psData);
        MyQueue::getInstance()->producer()->push($job);
    }

    public function add(){
        $params = $this->caller()->getArgs();
        if(empty($params['tag'])) return $this->response()->setMessage($this->toJson(10202, '', '/mes/index/lgout'));

        if(empty($params['barcode'])){
            return $this->response()->setMessage($this->toJson(10101, '操作错误提示', '请输入条码或扫码后提交'));
        } else {
            $barcode = strtoupper(trim($params['barcode']));
            // 实际逻辑应由fd提取sess_id,亲爱的傻逼,如果你看到了这里,请不要奇怪,公司的文化就是能跑通就行.
            $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
            $redis->select(15);
            $tag = $params['tag'];
            $user = $redis->get($tag);
            if($user) {
                $sess = json_decode($user, true);
                $code = 100;$msg = '';$data = [];
                $findOK = 0; //是否找到匹配的正则
                if($sess['task']['station_type'] === 7) { //普通关联
                    if($sess['now_station'][0]['do'] === '') { //普通关联必须先扫主码
                        if(preg_match($sess['now_station'][0]['preg'], $barcode)) {
                            if($sess['task']['pre_effect'] && $sess['task']['pre_station']) {
                                if($this->chkPreStation($barcode, $sess['task']['pre_station']) === NULL) {
                                    $code = 10105;
                                    $findOK = 1;
                                    $msg = '前置工位无记录';
                                    $data = '前置工位没有过站记录,请按序生产.';
                                } else {
                                    goto checkBarcode7;
                                }
                            } else {
                                checkBarcode7:
                                if($this->chkMainBarcode($barcode, $sess)) {
                                    $code = 10105;
                                    $findOK = 1;
                                    $msg = '主条码重复';
                                    $data = $barcode.' 已经存在';
                                } else {
                                    $sess['now_station'][0]['do'] = $barcode;
                                    $findOK = 1;
                                    $msg = '主码扫描成功';
                                    $data['content'] = $barcode;
                                    $data['local'] = 0;
                                }
                            }
                        } else {
                            $code = 10101;$msg = '操作失败提示';$data = '扫入条码不符合主码预设规则';
                        }
                    } else {
                        //匹配关联码

                        // 预定义单板测试的工位只能有一块板子条码正则
                        if($sess['task']['single']) {
                            if($this->chkSingleRelationBarcode($barcode, $sess)) {
                                $code = 10105;
                                $findOK = 1;
                                $msg = '单板测试错误';
                                $data = $barcode.' 单板测试未通过';
                            } else {
                                foreach ($sess['now_station'] as $k => &$v) {
                                    if($k !== 0) { //跳过主码
                                        if(preg_match($v['preg'], $barcode)) {
                                            if($this->chkRelationBarcode($barcode, $sess)) {
                                                $code = 10105;
                                                $findOK = 1;
                                                $msg = '关联条码重复';
                                                $data = $barcode.' 已经存在';
                                            } else {
                                                if($v['do'] === '') {
                                                    $v['do'] = $barcode;
                                                    $findOK = 1;
                                                    $msg = '关联码扫码成功';
                                                    $data['content'] = $barcode;
                                                    $data['local'] = $k;
                                                } else {
                                                    $findOK = 1;
                                                    $code = 10105;
                                                    $msg = '操作失败提示';
                                                    $data = '请不要重复扫同一条关联码';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            foreach ($sess['now_station'] as $k => &$v) {
                                if($k !== 0) { //跳过主码
                                    if(preg_match($v['preg'], $barcode)) {
                                        if($this->chkRelationBarcode($barcode, $sess)) {
                                            $code = 10105;
                                            $findOK = 1;
                                            $msg = '关联条码重复';
                                            $data = $barcode.' 已经存在';
                                        } else {
                                            if($v['do'] === '') {
                                                $v['do'] = $barcode;
                                                $findOK = 1;
                                                $msg = '关联码扫码成功';
                                                $data['content'] = $barcode;
                                                $data['local'] = $k;
                                            } else {
                                                $findOK = 1;
                                                $code = 10105;
                                                $msg = '操作失败提示';
                                                $data = '请不要重复扫同一条关联码';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if($sess['task']['station_type'] === 5) { //条码变更
                    if($sess['now_station'][0]['do'] === '') { //普通关联必须先扫主码
                        if(preg_match($sess['now_station'][0]['preg'], $barcode)) {
                            if($this->chkMainBarcode5($barcode, $sess)) {
                                $code = 10105;
                                $findOK = 1;
                                $msg = '主条码重复';
                                $data = $barcode.' 已经存在';
                            } else {
                                $sess['now_station'][0]['do'] = $barcode;
                                $findOK = 1;
                                $msg = '主码扫描成功';
                                $data['content'] = $barcode;
                                $data['local'] = 0;
                            }
                        } else {
                            $code = 10101;$msg = '操作失败提示';$data = '扫入条码不符合主码预设规则';
                        }
                    } else {
                        //匹配关联码
                        foreach ($sess['now_station'] as $k => &$v) {
                            if($k !== 0) { //跳过主码
                                if(preg_match($v['preg'], $barcode)) {
                                    if($this->chkRelationBarcode5($barcode, $sess)) {
                                        $code = 10105;
                                        $findOK = 1;
                                        $msg = '关联条码重复或找不到原始关联关系.';
                                        $data = $barcode.' 已经存在';
                                    } else {
                                        if($v['do'] === '') {
                                            $v['do'] = $barcode;
                                            $findOK = 1;
                                            $msg = '关联码扫码成功';
                                            $data['content'] = $barcode;
                                            $data['local'] = $k;
                                        } else {
                                            $findOK = 1;
                                            $code = 10105;
                                            $msg = '操作失败提示';
                                            $data = '请不要重复扫同一条关联码';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // 找到匹配的或全部扫完需要保存sess
                if($findOK) {
                    if($this->isOver($sess)) {
                        // 如果全部关联成功,设置了末位验证
                        if($sess['task']['check_preg']) {
                            if($this->chkRelationNumber($sess)) {
                                if($chkTask = $this->chkOverflow($sess['task']['task_id'])) {
                                    $this->clearSess($sess);
                                    $code = 10107;
                                    $msg = '操作失败提示';
                                    $data['content'] = '产量超限或生产任务已关闭';
                                    $data['local'] = 'ALL';
                                } else {
                                    $this->insert($sess); //先入库,再清sess
                                    $this->clearSess($sess);
                                    $msg = '入库成功';
                                    $data['content'] = '全部关联完成,请进行下一组扫码';
                                    $data['local'] = 'ALL';
                                }

                            } else {
                                $code = 10106;
                                $msg = '末位验证失败';
                                $data = '入库失败,末位验证未通过,请仔细检查';
                                $this->clearSess($sess);

                            }
                        } else {
                            if($chkTask = $this->chkOverflow($sess['task']['task_id'])) {
                                $this->clearSess($sess);
                                $code = 10107;
                                $msg = '操作失败提示';
                                $data['content'] = '产量超限或生产任务已关闭';
                                $data['local'] = 'ALL';
                            } else {
                                $this->insert($sess); //先入库,再清sess
                                $this->clearSess($sess);
                                $msg = '入库成功';
                                $data['content'] = '全部关联完成,请进行下一组扫码';
                                $data['local'] = 'ALL';
                            }
                        }
                    }
                    $redis->set($tag, $this->getEnArray($sess));
                } else {
                    $code = 10105;
                    $msg = '操作失败提示';
                    $data = '扫入条码不符合任一预设规则';
                }
                \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
                return $this->response()->setMessage($this->toJson($code, $msg, $data));

            } else {
                \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
                return $this->response()->setMessage($this->toJson(10302, '', '/mes/index/lgout'));
            }
        }
    }

    // 产量是否溢出
    private function chkOverflow($task_id){
        $task = WMesTaskModel::create()
            ->field(['relation_over','number','effect'])
            ->where(['id' => $task_id])
            ->get()
            ->toArray();
        // if($task['number'] > $task['relation_over'] && $task['effect']) {
        //     if($task['number'] - $task['relation_over'] === 1)
        //         WMesTaskModel::create()->update(['effect' => 0], ['id' => $task_id]);
        //     return 0;
        // } else {
        //     return 1;
        // }
        if($task['number'] === $task['relation_over']) {
            WMesTaskModel::create()->update(['effect' => 0], ['id' => $task_id]);
            return 1;
        } else {
            return 0;
        }
    }

    // 是否全部检测完
    private function isOver(&$sess){
        $res = 1;
         foreach ($sess['now_station'] as $k => $v) {
            if($k !== 0) {
                if($v['do'] === '')
                    $res = 0;
            }
        }
        return $res;
    }

    private function clearSess(&$sess){
        foreach ($sess['now_station'] as &$v) {
            $v['do'] = '';
        }
    }

    private function chkPreStation($barcode, $pre_station){
        $where = [
            'main_barcode' => $barcode,
            'station_id' => $pre_station,
            'effect' => 1,
        ];
        return WMesRelationModel::create()->where($where)->get();
    }

    // 检测主条码是否存在
    private function chkMainBarcode($barcode, &$sess){
        $where = [
            'main_barcode' => $barcode,
            'station_id' => $sess['task']['station_id'],
            'effect' => 1,
        ];
        if($sess['task']['rework_info']) $where['task_id'] = $sess['task']['task_id']; //翻新订单,只需要验证,在这个订单内不要重复
        return WMesRelationModel::create()->where($where)->get();
    }

    private function chkMainBarcode5($barcode, &$sess){
        $where = [
            'main_barcode' => $barcode,
            'task_id' => $sess['task']['task_id'],
            'station_id' => $sess['task']['station_id'],
            'effect' => 1,
        ];
        return WMesRelationModel::create()->where($where)->get();
    }

    // 检测关联条码是否存在
    private function chkRelationBarcode($barcode, &$sess){
        $where = [
            'relate_barcode' => $barcode,
            // 'station_id' => $sess['id'],
            'effect' => 1,
        ];
        if($sess['task']['rework_info']) $where['task_id'] = $sess['task']['task_id']; //翻新订单,只需要验证,在这个订单内不要重复
        return WMesRelationModel::create()->where($where)->get();
    }

    // 条码变更肯定是返修
    private function chkRelationBarcode5($barcode, &$sess){
        $res = 0;
        if(WMesRelationModel::create()->where([
            'relate_barcode' => $barcode,
            'station_id' => $sess['task']['station_id'],
            'task_id' => $sess['task']['task_id'],
            'effect' => 1,
        ])->get()) $res = 1;

        if(WMesRelationModel::create()->where([
            'main_barcode' => $barcode,
            'effect' => 1,
        ])->get()) $res = 1;

        return $res;
    }

    // 检测单板是否存在且成功, 通过返回0
    private function chkSingleRelationBarcode($barcode, &$sess){
        return WTestModel::create()->where([
            'barcode' => $barcode,
            'station_id' => 903,
            'result' => 0,
        ])->get() ? 0 : 0;
    }

    // 一定要先写入库,再清空sess, 此时,sess中可能没有主条码,切记;但arr[0] 一定是主条码
    private function insert($sess){
        $mySqlData = []; //mysql
        foreach ($sess['now_station'] as $k => $v) {
            if($k !== 0) {
                $mySqlData[] = [
                    'main_barcode' => $sess['now_station'][0]['do'],
                    'relate_barcode' => $v['do'],
                    'task_id' => $sess['task']['task_id'],
                    'station_id' => $sess['task']['station_id'],
                    'rework' => $sess['task']['rework_info'] ? 1 : 0,
                    'pro_id' => $sess['task']['pro_id'],
                    'line_id' => $sess['task']['line_id'],
                    'cost_type' => $sess['task']['cost_type'],
                    'worker_id' => $sess['task']['worker_id'],
                    'tag' => $sess['task']['tag'],
                    'created' => time(),
                ];
            }
        }

        $barcode_id = WMesBarcodeModel::create()->where([
            'main_barcode' => $sess['now_station'][0]['do'],
            'task_id' => $sess['task']['task_id']
        ])->val('id');

        if($barcode_id === NULL) {
            $barcode_id = WMesBarcodeModel::create()->data([
                'main_barcode' => $sess['now_station'][0]['do'],
                'pro_id' => $sess['task']['pro_id'],
                'task_id' => $sess['task']['task_id'],
                'rework' => $sess['task']['rework_info'] ? 1 : 0,
                'created' => time(),
                'create_user' => $sess['task']['worker_id_cn'],
            ], false)->save();
            foreach ($mySqlData as &$v) {
                $v['barcode_id'] = $barcode_id;
            }
        }
        WMesRelationModel::create()->saveAll($mySqlData);
        $this->addQueue($mySqlData,$sess);
        if($sess['task']['rework_info']) {
            $mustChk = isset($sess['task']['rework_info']['r']) ? $sess['task']['rework_info']['r'] : [];
        } else {
            $mustChk = [];
            foreach ($sess['stations'] as $v) {
                if($v['station_type'] === 7)
                    $mustChk[] = $v['id'];
            }
        }
        if(empty($mustChk)) {
            $upData = ['station_over' => 3, 'station_time' => time()];
        } else {
            $overStation = WMesRelationModel::create()->field(['id'])->where([
                'main_barcode' => $sess['now_station'][0]['do'],
                'task_id' => $sess['task']['task_id'],
                'effect' => 1,
            ])->group('station_id')->all()->toArray(false, false);

            if(count($overStation) >= count($mustChk)) {
                WMesTaskModel::create()->update(['relation_over' => QueryBuilder::inc(1)], ['id' => $sess['task']['task_id']]);
                $upData = ['station_over' => 3, 'station_time' => time()];
                $this->addQueue('station_over',$sess);
            } else {
                $upData = [];
            }

        }
        if($upData) WMesBarcodeModel::create()->update($upData, ['id' => $barcode_id]);


    }

    /* 如果是电机工位,需要验证后几位是否符合要求, 此时只能等全部码都扫入后才能开始验证

      "now_station" => array:6 [▼
        0 => array:4 [▼
          "preg" => "/^D02C060000-B00[A-Z0-9]{6,6}$/"
          "cn" => "D02C060000-B00[A-Z0-9]{6,6}"
          "do" => ""
          "check" => array:1 [▶]
        ]
        1 => array:4 [▼
          "preg" => "/^363MDJJ[A-Z0-9]{6,6}$/"
          "cn" => "363MDJJ[A-Z0-9]{6,6}"
          "do" => ""
          "check" => array:1 [▶]
        ]
        2 => array:4 [▼
          "preg" => "/^MDJJ[A-Z0-9]{6,6}$/"
          "cn" => "MDJJ[A-Z0-9]{6,6}"
          "do" => ""
          "check" => array:1 [▶]
        ]
        3 => array:4 [▼
          "preg" => "/^P02S020000-C00[A-Z0-9]{6,6}$/"
          "cn" => "P02S020000-C00[A-Z0-9]{6,6}"
          "do" => ""
          "check" => array:1 [▶]
        ]
        4 => array:4 [▼
          "preg" => "/^M02C060000-B00[A-Z0-9]{6,6}$/"
          "cn" => "M02C060000-B00[A-Z0-9]{6,6}"
          "do" => ""
          "check" => array:1 [▶]
        ]
        5 => array:4 [▼
          "preg" => "/^P02C020000-C00[A-Z0-9]{6,6}$/"
          "cn" => "P02C020000-C00[A-Z0-9]{6,6}"
          "do" => ""
          "check" => array:1 [▶]
        ]


        "check_preg" => array:2 [▼
          0 => array:2 [▼
            "chk" => "0,1,2,4"
            "num" => "6"
          ]
          1 => array:2 [▼
            "chk" => "3,5"
            "num" => "6"
          ]
        ]
        $arr 第0位为主码,1,2,3依次为关联码
        $check_preg 数据库中表字段, 012: 意为检测arr中第0,1,2的后6位是否相同
     */
    private function chkRelationNumber(&$sess){
        $res = 1;
        foreach ($sess['task']['check_preg'] as $v) {
            $tmp = [];
            $keys  = explode(',', $v['chk']);
            foreach ($keys as $v1) {
                $tmp[] = substr($sess['now_station'][$v1]['do'], -$v['num']);
            }

            if(count(array_unique($tmp)) !== 1) {
                $res = 0;
                break;
            }

        }
        return $res;
    }

}
