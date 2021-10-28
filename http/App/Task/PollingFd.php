<?php

namespace App\Task;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;

use App\Task\OnlineUser;

class PollingFd extends AbstractCronTask
{
    public static function getRule(): string
    {
        // 定义执行规则 根据Crontab来定义
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        return 'PollingFd';
    }

    public function run(int $taskId, int $workerIndex)
    {
        TaskManager::getInstance()->async(function (){
            OnlineUser::getInstance()->heartbeatCheck();
        });
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
