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

        $dept = [3302,3305,3306,3307];

        // 部门人数统计
        $sql = "
            SELECT
                            count(*) sl,
                    t2.CN_U8Department cost_type

            FROM
                    AAAAC.EmployeeBasic t1
                    INNER JOIN AAAAC.Organizational t2 ON t1.OrganizationalID= t2.OrganizationalID
            WHERE
                    t1.EmpStatus= 'Inservice' and t2.CN_U8Department in(".implode(',', $dept).")

                        GROUP BY t2.CN_U8Department
        ";

        $deptUsers = $this->getFromHrm($sql);
        $data = [];
        foreach ($dept as $v) {
            foreach ($deptUsers as $v1) {
                if($v == $v1['cost_type']) {
                    $data["line_'.$v.'_block_b1"] = $v1['sl']; // 部门实到人数
                    $data["line_'.$v.'_block_b1_last_updated"] = date('m/d H:i');
                }
            }
            $data['line_'.$v.'_block_b6'] = 0; //今日不良品清空
            $data['line_'.$v.'_block_b6_last_updated'] = date('m/d H:i');

            $data['line_'.$v.'_block_b3'] = 0; //今日警报
            $data['line_'.$v.'_block_b3_last_updated'] = date('m/d H:i');

        }
        $data['user_total'] = array_sum(array_column($data, 'sl'));

        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        $redis->mSet($data);
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
