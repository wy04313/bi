<?php

namespace App\Task;

use EasySwoole\Component\Singleton;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use App\Task\OnlineUser;
use App\Task\Mysql;
use EasySwoole\EasySwoole\ServerManager;


class Hrm implements TaskInterface
{
    use Singleton;

    // 拉取上工人数和总人数
    public function run(int $taskId, int $workerIndex)
    {
        // 当天打卡人数
        /*

            Array
            (
                [0] => Array
                    (
                        [depart_id] => 00301
                        [sl] => 10
                    )

                [1] => Array
                    (
                        [depart_id] => 00302
                        [sl] => 21
                    )

                [2] => Array
                    (
                        [depart_id] => 00304
                        [sl] => 10
                    )

            )
            $redisData["user_total"] = 0;//应到总人数
            $redisData["user_{$v}_total"] = 0;//部门应到人数
            $redisData["user_{$v}_arrive"] = 0;//实到总人数

            3302 贴片
            3305 电控
            3306 电机定子
            3307 电机总装

            00301   D003_ASS    6   电控总装
            00302   D003_EM 4       电机总装
            00304   D003_SMT    5   电控贴片
         */
        $date = date('Y-m-d 05:00:00');
        $arrive = $this->getAllFromHrm("
            SELECT m.depart_id,count(*) sl from (
            SELECT DISTINCT(e.emp_fname),e.depart_id from PassTime p
            left join Employee e on p.emp_id = e.emp_id
            WHERE e.depart_id in('00301','00302','00304') and p.passTime > '{$date}') m GROUP BY m.depart_id
            ");
        $data = [];
        if($arrive) {
            foreach ($arrive as $v) {
                if($v['depart_id'] === '00301') {
                    $data["line_3305_block_b1"] = $v['sl'];
                    $data["line_3305_block_b1_last_updated"] = date('m/d H:i');
                } elseif ($v['depart_id'] === '00302') {
                    $data["line_3307_block_b1"] = $v['sl'];
                    $data["line_3307_block_b1_last_updated"] = date('m/d H:i');
                } elseif ($v['depart_id'] === '00304') {
                    $data["line_3302_block_b1"] = $v['sl'];
                    $data["line_3302_block_b1_last_updated"] = date('m/d H:i');
                }
            }
            $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
            $redis->select(15);
            $redis->mSet($data);

            \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

            $pushData['block_data'] = $data;
            $users = OnlineUser::getInstance()->table();
            $server = ServerManager::getInstance()->getSwooleServer();
            // 人员打开推送
            foreach ($users as $v) {
                // $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getLinePageData($v['page_name'],'block_data')));
                $server->push($v['fd'], $this->writeToJson($pushData,'block_data'));
            }
        }
    }

    private function getAllFromHrm($sql){
        $conn = new \PDO("sqlsrv:server=10.0.6.198;database=EastRiver","hrm","KQJhrm06#");
        $res = $conn->query($sql);
        return $res->fetchAll(\PDO::FETCH_ASSOC);
    }


    private function formatToJson($data){
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function writeToJson($data,$case = 'ok'){
        return json_encode(compact('case','data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // 异常处理,进入队列
        echo $throwable.PHP_EOL;
    }
}
