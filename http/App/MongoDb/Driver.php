<?php

namespace App\MongoDb;

use EasySwoole\EasySwoole\Trigger;
use EasySwoole\SyncInvoker\AbstractDriver;
use MongoDB\Client;

class Driver extends AbstractDriver
{
    private $db;

    // 【建议使用】
    // 使用 mongodb/mongodb composer组件包封装的 MongoDB 客户端调用类，作为客户端调用驱动
    // 【前提：需要先使用 `composer require mongodb/mongodb` 安装 mongodb/mongodb composer组件包】
    // function getDb(): Client
    // {
    //     if ($this->db == null) {
    //         // 这里为要连接的 mongodb 的服务端地址【前提是必须先有服务端，且安装 php-mongodb 扩展才可使用】
    //         $mongoUrl = "mongodb://127.0.0.1:27017";
    //         $this->db = new Client($mongoUrl);
    //     }
    //     return $this->db;
    // }

    // 仅使用 php-mongodb 扩展内置类(不使用composer组件包的)，作为客户端调用驱动

    function getDb(): \MongoDB\Driver\Manager
    {
        if ($this->db == null) {
            // 这里为要连接的 mongodb 的服务端地址【前提是必须先有服务端，且安装 php-mongodb 扩展才可使用】
            $mongoUrl = "mongodb://10.10.5.31:20000";
            $this->db = new \MongoDB\Driver\Manager($mongoUrl);

        }
        return $this->db;
    }


    protected function onException(\Throwable $throwable)
    {
        Trigger::getInstance()->throwable($throwable);
        return null;
    }
}
