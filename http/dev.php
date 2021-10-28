<?php
return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 9900,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SOCKET_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 8,
            'reload_async' => true,
            'heartbeat_check_interval'=> 60, // 该参数项为心跳检测，严格参考swoole 配置说明
            'max_wait_time'=>3
        ],

        'TASK'=>[
            'workerNum'=>4,
            'maxRunningNum'=>128,
            'timeout'=>15
        ]
    ],
    'TEMP_DIR' => null,
    'LOG_DIR' => null,

    'MES' => [
        //数据库配置
        'host'                 => '10.10.5.23',//数据库连接ip
        'user'                 => 'jhgcdb',//数据库用户名
        'password'             => 'AdminJHGC@2109',//数据库密码
        'database'             => 'jhgcdb',//数据库
        'port'                 => '3306',//端口
        'timeout'              => '30',//超时时间
        'connect_timeout'      => '5',//连接超时时间
        'charset'              => 'utf8mb4',//字符编码
        'strict_type'          => false, //开启严格模式，返回的字段将自动转为数字类型
        'fetch_mode'           => false,//开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行或获取全部结果集(4.0版本以上)
        'alias'                => '',//子查询别名
        'isSubQuery'           => false,//是否为子查询
        'max_reconnect_times ' => '3',//最大重连次数
    ],

    'WMS' => [
        //数据库配置
        'host'                 => '10.0.4.217',//数据库连接ip
        'user'                 => 'wms',//数据库用户名
        'password'             => 'wmssql',//数据库密码
        'database'             => 'cq_factory',//数据库
        'port'                 => '3306',//端口
        'timeout'              => '30',//超时时间
        'connect_timeout'      => '5',//连接超时时间
        'charset'              => 'utf8mb4',//字符编码
        'strict_type'          => false, //开启严格模式，返回的字段将自动转为数字类型
        'fetch_mode'           => true,//开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行或获取全部结果集(4.0版本以上)
        'alias'                => '',//子查询别名
        'isSubQuery'           => false,//是否为子查询
        'max_reconnect_times ' => '3',//最大重连次数
    ],

    'REDIS' => [
        //数据库配置
        'host'                 => '127.0.0.1',//数据库连接ip
        'port'                 => '6379',//端口
        'auth'                 => 'sb_fzw',//数据库密码
        'serialize'            => \EasySwoole\Redis\Config\RedisConfig::SERIALIZE_NONE,

        'POOL_MAX_NUM'  => '6',
        'POOL_TIME_OUT' => '0.1',

        'minObjectNum' => 10, // 连接池最小连接数
        'maxObjectNum' => 30, // 连接池最大连接数
    ],

    'MES_QUEUE' => [
        //数据库配置
        'host'                 => '127.0.0.1',//数据库连接ip
        'port'                 => '6379',//端口
        'auth'                 => 'sb_fzw',//数据库密码
        'db'                   => '15', // 0 缓存, 15 队列
    ],

];
