<?php

namespace App\Task;

use EasySwoole\Task\AbstractInterface\TaskInterface;
use App\Model\WMesTaskModel;
use App\Model\WMesStationModel;
// use EasySwoole\Mysqli\QueryBuilder;
// use App\Model\WTest2021Model;
// use App\MongoDb\Driver;
// use App\MongoDb\MongoClient;

use EasySwoole\EasySwoole\ServerManager;
use App\Task\OnlineUser;
use App\Task\Mysql;

class MinuteTask implements TaskInterface
{
    public function run(int $taskId, int $workerIndex)
    {
        go(function (){
            $this->getNowLine(3302);
        });

        go(function (){
            $this->getNowLine(3305);
        });

        go(function (){
            $this->getNowLine(3307);
        });

        go(function (){
            $this->getTotalIn();
        });
    }

    // 入库总计 // SELECT ISNULL(sum(s.iQuantity), 0) as today_in FROM rdrecord10 r left join rdrecords10 s on r.id = s.ID   WHERE r.dDate > '2021-11-23 00:00:00'
    private function getTotalIn(){
        $today = date('Y-m-d 00:00:00');
        $year = date('Y-01-01 00:00:00');
        $field = 'r.dDate';

        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();

        $total_in_all = $this->getAllFromU8("SELECT sum(iQuantity) as cnt FROM rdrecords10");
        $total_in_all = (INT)$total_in_all['cnt'];

        $total_in_year = $this->getAllFromU8("
            SELECT ISNULL(sum(s.iQuantity), 0) as cnt FROM rdrecord10 r left join rdrecords10 s on r.id = s.ID WHERE {$field} > '{$year}'
            ");
        $total_in_year = (INT)$total_in_year['cnt'];
        $total_in_year_title = date('Y')."年度入库总量";

        $total_in_today = $this->getAllFromU8("
               SELECT s.cInvCode name,s.iQuantity val FROM rdrecord10 r left join rdrecords10 s on r.id = s.ID WHERE {$field} = '{$today}'
            ");

        $redis->select(15);
        $redis->set('total_in_todays', json_encode($total_in_today, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $redis->mSet(compact('total_in_all','total_in_year','total_in_year_title'));

        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['page_name'] === 'total') {
                $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getTotalPageData('total','block_data'), 'ok'));
            }
        }

    }

    private function getAllFromU8($sql){
        $conn = new \PDO("sqlsrv:server=10.0.6.218;database=UFDATA_102_2021","sa","abc@123");
        $res = $conn->query($sql);
        return $res->fetch(\PDO::FETCH_ASSOC);
    }

    private function getNowLine($cost_type){
        /*
        {"title":"非常2+75","data_x":["P板","K板","Q板","绝缘耐压","下线测试"],"data_y1":[500,500,500,500,500],"data_y2":[400,432,234,543,130],"data_y3":[22,45,45,264]}
         */

        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        $order = $redis->get("line_{$cost_type}_now_line_tag");
        if($order) {
            // $order = '102SC2110091';
            $task = WMesTaskModel::create()
                ->field('id,`order`,pro_id,cost_type,number,relation_over')
                ->where("`order` = '{$order}'")
                ->get();
            $task = $task->toArray();
            $task_id = $task['id'];
            $station = WMesStationModel::create()
                ->field('id,station_type,brief_desc')
                ->where("effect = 1 and pro_id = {$task['pro_id']}")
                ->all()->toArray();
            $relation_station = []; //关联的工位
            $data_x = []; //工艺
            $data_y1 = []; //计划数量
            $data_y2 = []; //关联数量
            $data_y3 = []; //完工数量 这个由另一个任务完成
            $res = WMesTaskModel::create()->func(function ($builder) use($task_id){
                // SELECT count(*) sl,m.station_id from (SELECT station_id from w_mes_relation WHERE task_id = 143 and effect = 1 GROUP BY main_barcode,station_id) m GROUP BY m.station_id
                return $builder->raw('SELECT count(*) sl,m.station_id from (SELECT station_id from w_mes_relation WHERE task_id = '.$task_id.' and effect = 1 GROUP BY main_barcode,station_id) m GROUP BY m.station_id');
            });

            // 每个工艺已经关联的数量,只看关联的
            foreach ($station as &$v) {
                $v['relation_over'] = 0;
                if(!empty($res[0]['sl'])) {
                    foreach ($res as $v1) {
                        if($v1['station_id'] == $v['id'])
                            $v['relation_over'] = $v1['sl'];
                    }
                }

                $data_x[] = $v['brief_desc'];
                $data_y1[] = $task['number'];
                $data_y2[] = $v['relation_over'];
            }
            $title = $order;
        } else {
            $title = '未生产数据展示案例';
            $data_x = ["P板","K板","Q板","绝缘耐压","下线测试"];
            $data_y1 = [500,500,500,500,500];
            $data_y2 = [400,500,350,480,260];
        }
        $data = compact('title','data_x','data_y1','data_y2');
        $redis->set("line_{$cost_type}_now_line", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

        $pushData['now_line'] = $data;
        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['page_name'] === 'line'.$cost_type) {
                $server->push($v['fd'], $this->writeToJson($pushData, 'ok'));
            }
        }
    }

    protected function writeToJson($data,$case = 'ok'){
        return json_encode(compact('case','data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function getStationId($station_id){
        switch ($station_id) {
            case '900':
            case '120':
            case '130':
                $station_id = 900;
                $desc = '下线';
                break;
            case '901':
                $station_id = 901;
                $desc = '绝缘耐压';
                break;
            case '902':
            case '100':
            case '110':
                $station_id = 902;
                $desc = '气密';
                break;
            case '800':
                $station_id = 800;
                $desc = '单板';
                break;
            default:
                $station_id = interval($station_id);
                $desc = 'unknow';
                break;
        }
        return compact('station_id','desc');
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // 异常处理,进入队列
        echo $throwable.PHP_EOL;
    }
}
