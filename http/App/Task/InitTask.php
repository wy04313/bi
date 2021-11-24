<?php

namespace App\Task;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;
use App\Model\WMesTaskModel;
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

        $dept = [3302,3305,3307];

        // 部门人数统计
        $sql = "
            SELECT t1.EmpCode,t2.CN_U8Department cost_type
            FROM AAAAC.EmployeeBasic t1 INNER JOIN AAAAC.Organizational t2
            ON t1.OrganizationalID= t2.OrganizationalID
            WHERE t1.EmpStatus= 'Inservice' and t2.CN_U8Department in(3302,3305,3307)
        ";
        /*
        J00024  3305
        J00025  3305
        J00021  3305
        J00022  3305
        J00062  3305
        J00085  3305

        b0 应到  b1 实到
         */
        $yd3302 = [];
        $yd3305 = [];
        $yd3307 = [];
        $deptUsers = $this->getFromHrm($sql);
        foreach ($deptUsers as $v) {
            if($v['cost_type'] === '3302')
                $yd3302[] = "'{$v['EmpCode']}'";
            elseif ($v['cost_type'] === '3305')
                $yd3305[] = "'{$v['EmpCode']}'";
            elseif ($v['cost_type'] === '3307')
                $yd3307[] = "'{$v['EmpCode']}'";
        }

        $data = [];
        foreach ($dept as $v) {
            $data["line_{$v}_block_b0"] = count(${'yd'.$v});
            $data["line_{$v}_block_b0_last_updated"] = date('m/d H:i');

            $data['line_'.$v.'_block_b6'] = 0; //今日不良品清空
            $data['line_'.$v.'_block_b6_last_updated'] = date('m/d H:i');

            $data['line_'.$v.'_block_b3'] = 0; //今日警报
            $data['line_'.$v.'_block_b3_last_updated'] = date('m/d H:i');
        }


        //总人数
        $data['user_total'] = count($yd3302) + count($yd3305) + count($yd3307);
        $data['user_total_emp_code'] = implode(',', array_merge($yd3302,$yd3305,$yd3307));

        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        $redis->mSet($data);

        // 电表统计,将昨天最后一次抄表写入,
        $today = date('Ymd');
        if($redis->LINDEX('weeks', 0) !== $today) {
            $redis->LPUSH('weeks', $today);
            $redis->LPUSH('watt_meter_weeks', $redis->LINDEX('watt_meter_weeks', 0)); //今日电表默认值取昨天最后一次
            $redis->LPUSH('total_in_todays', $redis->LINDEX('total_in_todays', 0, 0)); //今日入库默认0
            $redis->LTRIM('weeks',0,6);
            $redis->LTRIM('watt_meter_weeks',0,7); //需要作差,多留一天
            $redis->LTRIM('total_in_todays',0,6); //7天
        }

        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

        // 异步任务
        // TaskManager::getInstance()->async(function (){
        // });
    }

    private function getFromHrm($sql){
        $conn = new \PDO("sqlsrv:server=10.0.6.218;database=vxTalent","sa","abc@123");
        $res = $conn->query($sql);
        return $res->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
