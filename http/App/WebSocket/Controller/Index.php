<?php

namespace App\WebSocket\Controller;

use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Http\AbstractInterface\Controller;

use App\Task\OnlineUser;
use App\Task\Push;
use App\Model\WDataPagesModel;


/**
 * Class Index
 *
 * 此类是默认的 websocket 消息解析后访问的 控制器
 *
 * @package App\WebSocket
 */
class Index extends Base
{
    // 打开页面后,页面带着ip来访,这里做验证
    public function hello()
    {
        $params = $this->caller()->getArgs();
        $res = $this->getPage($params['created']);
        $res['fd'] = $this->caller()->getClient()->getFd();
        $case = $res['url'] ? 'jump' : 'Do_nothing';
        $this->response()->setMessage($this->whiteToJson(0,'OK',$res,  $case));

    }

    private function getPage($created){
        $res = [
            'title' => '',
            'url' => '',
        ];
        $page = WDataPagesModel::create()->where('created', (int)$created)->get();
        if($page) {
            $page = $page->toArray(); //傻逼框架,没有数据时是null,有数据 需要转成数组
            $res = [
                'title' => $page['title'],
                'url' => $page['url']
            ];
        } else {
            WDataPagesModel::create()->data(['created' => $created], false)->save();
        }
        return $res;
    }

    // 由导航进入,这里一定是配置过IP页面的,或是由推送跳转的
    public function getData(){
        $params = $this->caller()->getArgs();
        $created = $params['created'];
        $page_name = $params['page_name'];
        $res = $this->getPage($created);

        $fd = $this->caller()->getClient()->getFd();
        OnlineUser::getInstance()->set($fd, (int)$created);

        if($page_name === 'line3305') {
            $data = Push::getInstance()->getMysqlData('line');
            $data['title'] = $res['title'];
        }
        $this->response()->setMessage($this->whiteToJson(0,'OK',$data, 'ok'));

    }

    function delay()
    {
        $this->response()->setMessage('this is delay action');
        $client = $this->caller()->getClient();

        // 异步推送, 这里直接 use fd也是可以的
        TaskManager::getInstance()->async(function () use ($client){
            $server = ServerManager::getInstance()->getSwooleServer();
            $i = 0;
            while ($i < 5) {
                sleep(1);
                $server->push($client->getFd(),'push in http at '. date('H:i:s'));
                $i++;
            }
        });
    }

    // 心跳
    function heartbeat()
    {
// echo 'PONG'.PHP_EOL;
        $fd = $this->caller()->getClient()->getFd();
        OnlineUser::getInstance()->update($fd);
        $this->response()->setMessage('PONG');
    }
}
