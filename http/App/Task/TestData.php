<?php

namespace App\Task;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;

class TestData extends AbstractCronTask
{
    public static function getRule(): string
    {
        // 定义执行规则 根据Crontab来定义
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        return 'TestData';
    }

    public function run(int $taskId, int $workerIndex)
    {
        echo date('Y-m-d H:i:s').PHP_EOL;
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
