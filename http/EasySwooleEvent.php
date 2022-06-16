<?php

namespace EasySwoole\EasySwoole;

use App\Crontab\SecondCrontab;
use EasySwoole\Component\Process\Manager;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');

        ###### 注册秒级定时任务 ######
        $process = new SecondCrontab(new \EasySwoole\Component\Process\Config([
            'enableCoroutine' => true
        ]));
        Manager::getInstance()->addProcess($process);


    }

    public static function mainServerCreate(EventRegister $register)
    {

    }

}
