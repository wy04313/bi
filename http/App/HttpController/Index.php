<?php

namespace App\HttpController;

use EasySwoole\EasySwoole\ServerManager;
use App\Task\OnlineUser;
use App\Task\Mysql;

class Index extends Base
{

    /*
     */
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
                'cate' => 'block_data',
                'cost_type' => $cost_type,
             */
            case 'block_data':# 推送温度
                $page = 'line'.$params['cost_type'];
                foreach ($users as $v) {
                    if($v['page_name'] === $page)
                        $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getPageData($page), 'ok'));
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
