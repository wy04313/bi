<?php

namespace App\Crontab;

use EasySwoole\Component\Process\AbstractProcess;

class SecondCrontab extends AbstractProcess
{
    protected function run($arg)
    {
        while(1) {

            // 这里写执行逻辑
            // to do something.

            // 这里表示每秒打印一个日期时间字符串，仅供参考
            var_dump(date('Y-m-d H:i:s'));

            // 休息1秒
            \Co::sleep(2);
        }
    }
}
