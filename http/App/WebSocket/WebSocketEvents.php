<?php

namespace App\WebSocket;

use \swoole_server;
use \swoole_websocket_server;
use \swoole_http_request;
use \Exception;
use App\Task\OnlineUser;

class WebSocketEvents {
    /**
     * 关闭事件
     *
     * @param \swoole_server $server
     * @param int            $fd
     * @param int            $reactorId
     */
    static function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        // $fd = $request->fd;

        // $getParm = $request->get; //所有get参数
        // $server->push($fd, json_encode([
        //     'code' => 0,
        //     'msg' => '连接成功.',
        //     'data' => '',
        //     'cate' => 'init_ok'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // $online = OnlineUser::getInstance();

        // if(!empty($getParm['type'])) {
        //     $prefix = substr($getParm['type'], 0, 2);
        //     $task_id = empty($getParm['task_id']) ? 0 : $getParm['task_id'];
        //     $users = $online->table();
        //     foreach ($users as $v) {
        //         if($v['tags'] === $getParm['type']) {
        //             $users->delete($v['fd']);
        //             break;
        //         }
        //     }

        //     $online->set($fd, $prefix, $getParm['type'], $task_id);
        //     if($prefix === 'MS') {
        //         $online->online($getParm['type']);
        //         $welcome = ['code' => 0,'msg' => '远程服务器连接成功','data' => '现在可以扫码了......'];
        //     } else {
        //         $welcome = ['code' => 0,'msg' => '连接成功.','data' => ''];
        //     }
        //     $server->push($fd, json_encode($welcome, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        // }

    }

    static public function onClose(\swoole_server $server, int $fd, int $reactorId)
    {
        go(function ()use($fd){
            $online = OnlineUser::getInstance()->delete($fd);
        });
    }

}
