<?php

namespace App\WebSocket\Controller;

use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Http\AbstractInterface\Controller;

use App\Task\OnlineUser;

/**
 * Class Index
 *
 * 此类是默认的 websocket 消息解析后访问的 控制器
 *
 * @package App\WebSocket
 */
class Index extends Base
{
    // 打开页面后,页面带着ip
    public function hello()
    {
        $params = $this->caller()->getArgs();
        $client = $this->caller()->getClient();
$fd = $client->getFd();
        echo $fd.PHP_EOL;
        if(empty($params['ip'])) {

        } else {

        }







        $this->response()->setMessage($this->whiteToJson(0,'OK333',[
                'title' => '注意!!!',
                'content' => '封志文就是个大骚货.?',
                'ip' => $params,
        ], 'http://www.baidu.com'));
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


    // 未找到对应控制器,需要 use EasySwoole\Http\AbstractInterface\Controller;
    public function actionNotFound(?string $action)
    {
        $this->response()->withStatus(404);
        $this->response()->withHeader("Content-type","text/html;charset=utf-8");
        $this->response()->write("对不起,页面找不到了");
    }

    // 心跳
    function heartbeat()
    {
        $fd = $this->caller()->getClient()->getFd();
        OnlineUser::getInstance()->update($fd);
        $this->response()->setMessage('PONG');
    }
}
