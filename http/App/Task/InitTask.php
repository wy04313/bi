<?php

namespace App\Task;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;
use App\Model\WMesTaskModel;
use App\Model\WDataModel;
use EasySwoole\Mysqli\QueryBuilder;

class InitTask extends AbstractCronTask
{
    public static function getRule(): string
    {
        // 每天跑一次
        return '1 0 * * *';
    }

    public static function getTaskName(): string
    {
        return 'InitTask';
    }

    public function run(int $taskId, int $workerIndex)
    {
        WMesTaskModel::create()->func(function ($builder){
            $builder->raw('UPDATE w_mes_task set effect = 0 WHERE number = relation_over and effect = 1');
            return true;
        });
        WDataModel::create()->data(['date' => date('Ymd')], false)->save();
        $today = (int)date('Ymd');
        if(WDataModel::create()->where('date', $today)->get() === null)
            WDataModel::create()->data(['date' => $today], false)->save();

        // 异步任务
        // TaskManager::getInstance()->async(function (){
        // });
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
