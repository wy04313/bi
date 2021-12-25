<?php

namespace App\Task;

use EasySwoole\Task\AbstractInterface\TaskInterface;
use App\Model\WMesTaskModel;
use App\Model\WMesStationModel;
use EasySwoole\Mysqli\QueryBuilder;
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
            $this->getStationInfo();
        });

        go(function (){
            $this->getTotalIn();
        });
    }
    /*
        SELECT DISTINCT r.main_barcode, r.station_id,s.brief_desc FROM  w_mes_relation r LEFT JOIN w_mes_station s ON r.station_id = s.id  WHERE r.created > 1639065600 and r.effect = 1

        SELECT
            count(a.station_id) AS cnt,
            a.brief_desc
        FROM
            (
            SELECT DISTINCT r.main_barcode, r.station_id,s.brief_desc FROM  w_mes_relation r LEFT JOIN w_mes_station s ON r.station_id = s.id  WHERE r.created > 1639065600 and r.effect = 1
            ) a
        GROUP BY
            a.station_id
     */
    // 获取今天工位信息
    private function getStationInfo(){
        $res  = WMesTaskModel::create()->func(function ($builder){
            return $builder->raw("
                SELECT
                    count(a.station_id) AS value,
                    a.brief_desc name
                FROM
                    (
                    SELECT DISTINCT r.main_barcode, r.station_id,s.brief_desc FROM  w_mes_relation r LEFT JOIN w_mes_station s ON r.station_id = s.id  WHERE r.created > 1639065600 and r.effect = 1
                    ) a
                GROUP BY
                    a.station_id
            ");
        });

        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        $redis->set('stations', json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['page_name'] === 'total') {
                $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getTotalPageData('total','stations'), 'ok'));
            }
        }
    }

    // 入库总计 // SELECT ISNULL(sum(s.iQuantity), 0) as today_in FROM rdrecord10 r left join rdrecords10 s on r.id = s.ID   WHERE r.dDate > '2021-11-23 00:00:00'
    private function getTotalIn(){
        $today = date('Y-m-d 00:00:00');
        $year = date('Y-01-01 00:00:00');
        $field = 'r.dDate';

        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();

        $total_in_all = $this->getAllFromU8("SELECT sum(iQuantity) as cnt FROM rdrecords10");
        $total_in_all = (INT)$total_in_all['cnt'];

        $total_out_all = $this->getAllFromU8("SELECT ISNULL(sum(iQuantity), 0) as cnt FROM rdrecords32");
        $total_out_all = (INT)$total_out_all['cnt'];
        $total_out_all_title = "成品出库总量";

        $total_in_today = $this->getAllFromU8("
               SELECT s.cInvCode name,s.iQuantity value FROM rdrecord10 r left join rdrecords10 s on r.id = s.ID WHERE {$field} = '2021-04-07 00:00:00'
            ", true);
        $total_out_today = $this->getAllFromU8("
               SELECT s.cInvCode name,sum(s.iQuantity) value FROM rdrecord32 r left join rdrecords32 s on r.id = s.ID WHERE {$field} = '2021-04-22 00:00:00' GROUP BY s.cInvCode
            ", true);
        $redis->select(15);
        $total_in_today = $total_in_today ? $this->fmtColumn($total_in_today) : [];

        if($total_out_today) {
            $tmp1 = $this->fmtColumn($total_out_today);
            $tmp1['z'] = $total_out_today;
        } else {
            $tmp1 = '今日没有出库数据.';
        }
        $redis->set('total_in_todays', json_encode($total_in_today, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $redis->set('total_out_todays', json_encode($tmp1, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $redis->mSet(compact('total_in_all','total_out_all','total_out_all_title'));

        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['page_name'] === 'total') {
                $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getTotalPageData('total','block_data'), 'ok'));
            }
        }

    }

    // 整理柱形图数据
    private function fmtColumn($data){
        $arr = [];
        foreach ($data as $v) {
            $arr['x'][] = $v['name'];
            $arr['y'][] = (INT)$v['value'];
        }
        return $arr;
    }

    private function getAllFromU8($sql,$isArr = false){
        $conn = new \PDO("sqlsrv:server=10.0.6.218;database=UFDATA_102_2021","sa","abc@123");
        $res = $conn->query($sql);
        return $isArr === false ? $res->fetch(\PDO::FETCH_ASSOC) : $res->fetchAll(\PDO::FETCH_ASSOC);
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
