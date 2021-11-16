<?php

namespace App\HttpController;

use EasySwoole\EasySwoole\ServerManager;
use App\Task\OnlineUser;
use App\Task\Mysql;

class Index extends Base
{

    public function push(){
        $request = $this->request();
        $params = $request->getRequestParam();

        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        switch ($params['cate']) {
            case 'all':# 所有页面推送
                foreach ($users as $v) {
                    $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getPageData($v['page_name']), 'ok'));
                }
                break;
            /*
                'cate' => 'block_data_watt_meter',
                'cost_type' => $cost_type,
                有data表示有数据要存储,此时传入的key就是要存储的key
             */
            case 'block_data_watt_meter':# 推送温度和电表总表
                if(!empty(($params['data']))){
                    $data = json_decode($params['data'],true);
                    $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
                    $redis->select(15);
                    $redis->mSet($data);
                    \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
                }

                $page = 'line'.$params['cost_type'];
                foreach ($users as $v) {
                    if($v['page_name'] === $page)
                        $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getLinePageData($page,'block_data'), 'ok'));
                    else
                        $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getTotalPageData('total','dashboard,watt_meter_weeks'), 'ok'));

                }
                break;
            /*
                'cate' => 'watt_meter',
                'cost_type' => $cost_type,
                'data' => 33425.35;
             */
            case 'watt_meter':# 推送电表,写入redis后推送(电表度数和7日用量)
                $db = $params['data'];
                $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
                $redis->select(15);

                $redis->LINDEX('watt_meter_weeks', $db); //当前度数

                \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
                foreach ($users as $v) {
                    if($v['page_name'] === 'total')
                        $server->push($v['fd'],$this->writeToJson(Mysql::getInstance()->getTotalPageData('total','dashboard,watt_meter_weeks', 'ok')));
                }
                break;

            /*
                'cate' => 'test_error',
                'data' => [
                    '3302' => 0,
                    '3305' => 4
                ],
             */
            case 'test_error':# 测试警报推送
                $pages = [];
                foreach ($params['data'] as $k => $v) {
                    $pages[] = 'line'.$k;
                }
                foreach ($users as $v) {
                    if(in_array($v['page_name'], $pages))
                        $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getLinePageData($v['page_name'],'block_data'), 'ok'));
                }
                break;

            /*
                'cate' => 'jump',
                'fd' => $this->_P['fd'],
                'url' => $this->_P['url'],
             */
            case 'jump':# 推送温度
                $server->push((INT)$params['fd'], $this->writeToJson(['url' => $params['url']], 'jump'));
                break;


        }
        $this->writeJson();
    }



}
