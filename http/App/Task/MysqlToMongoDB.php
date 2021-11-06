<?php

namespace App\Task;

use EasySwoole\Task\AbstractInterface\TaskInterface;
// use EasySwoole\Mysqli\QueryBuilder;
use App\Model\WTest2021Model;
use App\MongoDb\Driver;
use App\MongoDb\MongoClient;

use EasySwoole\EasySwoole\ServerManager;
use App\Task\OnlineUser;
use App\Task\Mysql;

class MysqlToMongoDB implements TaskInterface
{
    public function run(int $taskId, int $workerIndex)
    {
        $data = WTest2021Model::create()
            ->field('id,station_tag,barcode,result,test_time,insert_time,op_user')
            ->where("sync = 0")
            ->limit(50) //没分钟执行,不要取太多值
            ->all();
        $ids = [];
        $insertData = [];
        $stationLog = [];
        $err = 0;
        foreach ($data as $v) {
            $ids[] = $v['id'];
            if($v['result']) $err++;
            if(!preg_match('/^TZ.*?$|^.*?test.*?$|^.*?\\..*?$|^\d*$/i', $v['barcode'])) { //非法条码,直接回写
                $stations = $this->getStationId(substr($v['station_tag'], 0,3));
                $insertData[] = [
                    'barcode' => strtoupper($v['barcode']),
                    'station_id' => $stations['station_id'],
                    'result' => (int)$v['result'],
                    'op_time' => new \MongoDB\BSON\UTCDateTime($v['test_time']*1000),
                    'op_user' => $v['op_user'],
                    'created' => date('Y-m-d H:i:s', $v['insert_time']),
                    'tb_name' => 'test2021',
                    'remark' => $stations['desc'].'(normal)',
                ];

                $stationLog[] = [
                    'barcode' => strtoupper($v['barcode']),
                    'station_id' => (string)$stations['station_id'],
                    'result' => (string)$v['result'],
                    'time' => new \MongoDB\BSON\UTCDateTime($v['test_time']*1000),
                    'data' => 'test2021,'.$v['id'],
                    'sync_new' => time(),
                ];

            }
        }

        if($err) {
            $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
            $redis->select(15);
            $redis->incrby('line_3305_block_b3', $err);
            \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

            $users = OnlineUser::getInstance()->table();
            $server = ServerManager::getInstance()->getSwooleServer();

            foreach ($users as $v) {
                if($v['page_name'] === 'line3305' || $v['page_name'] === 'total')
                    $server->push($v['fd'], $this->writeToJson(Mysql::getInstance()->getPageData($v['page_name']), 'ok'));
            }
        }
        if($insertData) {
            MongoClient::getInstance()->invoke()->callback(function (Driver $driver) use($insertData, $stationLog){
                $bulk1 = new \MongoDB\Driver\BulkWrite();
                $bulk2 = new \MongoDB\Driver\BulkWrite();

                foreach ($insertData as $k => $v) {
                    $bulk1->insert($v);
                    $bulk2->insert($stationLog[$k]);
                }
                $manager = $driver->getDb();
                $writeConcern1 = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 100);
                $writeConcern2 = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 100);
                $manager->executeBulkWrite('mes.testLog', $bulk1, $writeConcern1);
                $manager->executeBulkWrite('mes.stationLog', $bulk2, $writeConcern2);
            });
            WTest2021Model::create()->update(['sync'  => time()], $ids);
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
            case '903':
                $station_id = 903;
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
