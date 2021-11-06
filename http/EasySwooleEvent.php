<?php

namespace EasySwoole\EasySwoole;

// use EasySwoole\Queue\Job;
use EasySwoole\Component\Timer;

// 队列
use App\Task\MyQueue;
use App\Task\QueueProcess;

// websockert
use App\WebSocket\WebSocketEvents;
use EasySwoole\Socket\Dispatcher;
use App\WebSocket\WebSocketParser;

// orm 用
// use EasySwoole\EasySwoole\Bridge\Exception;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\EasySwoole\Config;

use EasySwoole\EasySwoole\Crontab\Crontab;

use App\Task\OnlineUser;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');
        // MYSQL ORM 连接池
        $m1 = new \EasySwoole\ORM\Db\Config(Config::getInstance()->getConf("MES"));
        $m2 = new \EasySwoole\ORM\Db\Config(Config::getInstance()->getConf("WMS"));
        try{
            DbManager::getInstance()->addConnection(new Connection($m1),'mes');
            DbManager::getInstance()->addConnection(new Connection($m2),'wms');
        }catch (Exception $e){

        }

        // redis连接池
        $config = new \EasySwoole\Pool\Config();
        $redisConfig = new \EasySwoole\Redis\Config\RedisConfig(Config::getInstance()->getConf('REDIS'));
        \EasySwoole\Pool\Manager::getInstance()->register(new \App\Pool\RedisPool($config,$redisConfig),'redis');

    }

    public static function mainServerCreate(EventRegister $register)
    {

        // 配置 Invoker
        $invokerConfig = \App\MongoDb\MongoClient::getInstance()->getConfig();
        $invokerConfig->setDriver(new \App\MongoDb\Driver()); // 配置 MongoDB 客户端协程调用驱动

        // 以下这些配置都是可选的，可以使用组件默认的配置
        /*
        $invokerConfig->setMaxPackageSize(2 * 1024 * 1024); // 设置最大允许发送数据大小，默认为 2M【注意：当使用 MongoDB 客户端查询大于 2M 的数据时，可以修改此参数】
        $invokerConfig->setTimeout(3.0); // 设置 MongoDB 客户端操作超时时间，默认为 3.0 秒;
        */

        // 注册 Invoker
        \App\MongoDb\MongoClient::getInstance()->attachServer(ServerManager::getInstance()->getSwooleServer());



        // 收到用户消息时处理 websocket控制器 创建一个 Dispatcher 配置
        $conf = new \EasySwoole\Socket\Config();
        $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);// 设置 Dispatcher 为 WebSocket 模式
        $conf->setParser(new WebSocketParser());// 设置解析器对象
        $dispatch = new Dispatcher($conf);// 创建 Dispatcher 对象 并注入 config 对象
        // 给server 注册相关事件 在 WebSocket 模式下  on message 事件必须注册 并且交给 Dispatcher 对象处理
        $register->set(EventRegister::onMessage, function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($dispatch) {
            $dispatch->dispatch($server, $frame->data, $frame);
        });

        // 注册服务事件
        $register->add(EventRegister::onOpen, [WebSocketEvents::class, 'onOpen']);
        $register->add(EventRegister::onClose, [WebSocketEvents::class, 'onClose']);

        OnlineUser::getInstance(); //建表
        Crontab::getInstance()->addTask(\App\Task\InitTask::class);

        // 队列
        $redisConfig = new \EasySwoole\Redis\Config\RedisConfig(Config::getInstance()->getConf('MES_QUEUE'));
        // 配置 队列驱动器
        $driver = new \EasySwoole\Queue\Driver\RedisQueue($redisConfig, 'fuck_fzw');
        MyQueue::getInstance($driver);
        // 注册一个消费进程
        $processConfig = new \EasySwoole\Component\Process\Config([
            'processName' => 'QueueProcess', // 设置 自定义进程名称
            'processGroup' => 'Queue', // 设置 自定义进程组名称
            'enableCoroutine' => true, // 设置 自定义进程自动开启协程
        ]);
        \EasySwoole\Component\Process\Manager::getInstance()->addProcess(new QueueProcess($processConfig));

        $register->add(EventRegister::onWorkerStart, function (\swoole_server $server, $workerId) {
            if ($workerId == 0) {
                \EasySwoole\Component\Timer::getInstance()->loop(300 * 1000, function () {
                    $syncTask = \EasySwoole\EasySwoole\Task\TaskManager::getInstance();
                    $syncTask->async(new \App\Task\U8()); // 生产缺料
                    OnlineUser::getInstance()->heartbeatCheck(); //检查心跳
                });

                // \EasySwoole\Component\Timer::getInstance()->loop(60 * 1000, function () {
                //     // 在生产使用再打开
                //     go(function (){
                //         $syncTask = \EasySwoole\EasySwoole\Task\TaskManager::getInstance();
                //         $syncTask->sync(new \App\Task\MysqlToMongoDB()); // vb到mysql的数据导入到mongodb
                //     });
                // });
            }
        });
    }



}
