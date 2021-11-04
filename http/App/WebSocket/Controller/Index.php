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
        $title = '';$url = '';
        $params = $this->caller()->getArgs();
        if($res = $this->getIpCof($params)) {
            $title = $res['title'];
            $url = $res['url'];
        }
        $case = $res ? 'jump' : 'Do_nothing';
        $this->response()->setMessage($this->whiteToJson(0,'OK',compact('title','url'),  $case));

    }

    private function getIpCof($params){
        $res = null;
        $fIp = trim($params['ip']);
        $ips = WDataPagesModel::create()->where('ip', $fIp)->get();
        if($ips) {
            $ips = $ips->toArray(); //傻逼框架,没有数据时是null,有数据 需要转成数组

            $fd = $this->caller()->getClient()->getFd();
            OnlineUser::getInstance()->set($fd, $fIp, $ips['url']);

            $res = [
                'title' => $ips['title'],
                'url' => $ips['url']
            ];
        } else {
            WDataPagesModel::create()->data(['ip' => $fIp], false)->save();
        }
        return $res;
    }

    // 由导航进入,这里一定是配置过IP页面的,或是由推送跳转的
    public function getData(){
        $params = $this->caller()->getArgs();
        $fIp = trim($params['ip']);
        $page = WDataPagesModel::create()->field('title,url')->where('ip', $fIp)->get();
        if($page === null) {
            WDataPagesModel::create()->data(['ip' => $fIp], false)->save();
            $title = '未配置的页面,请联系IT.';$url = '';
            $this->response()->setMessage($this->whiteToJson(0,'OK',compact('title','url'), 'Do_nothing'));
        } else {
            $page = $page->toArray();
            // 只有配置过的才写入table
            $fd = $this->caller()->getClient()->getFd();
            OnlineUser::getInstance()->set($fd, $fIp, $page['url']);

            $data = Push::getInstance()->getMysqlData('line');
            $data['title'] = $page['title'];
            $this->response()->setMessage($this->whiteToJson(0,'OK',$data, 'ok'));
        }

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
