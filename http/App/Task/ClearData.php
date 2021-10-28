<?php

namespace App\Task;

use EasySwoole\Task\AbstractInterface\TaskInterface;
use EasySwoole\Mysqli\QueryBuilder;
// use App\Model\WTestModel;
// use App\Model\WTest2019Model;
// use App\Model\WTest2020Model;
// use App\Model\WTest2021Model;

class ClearData implements TaskInterface
{
    // BS2000-000000-1710 形如此忽略掉
    public function run(int $taskId, int $workerIndex)
    {
        // echo date('Y-m-d H:i:s').PHP_EOL;
        // $unDo = WTest2020Model::create()
        //     ->field('id,station_tag,barcode,result,test_time')
        //     ->where('sync_to_mongo = 0')
        //     ->get();
        // if($unDo) {
        //     if(is_numeric($unDo['barcode']) || preg_match('/^.*?test.*?$/i',$unDo['barcode']) || stripos($unDo['barcode'], ".") !== FALSE) {
        //         WTest2020Model::create()->update([
        //             'sync_to_mongo'  => 1,
        //         ], ['id' => $unDo['id']]);
        //     } else {
        //         $station_id = substr($unDo['station_tag'], 0,3);
        //         $old = WTestModel::create()
        //             ->field('id,last_time')
        //             ->where(['barcode' => $unDo['barcode'],'station_id' => intval($station_id)])
        //             ->get();
        //         if($old === NULL) {
        //             WTestModel::create()->data([
        //                     'barcode' => $unDo['barcode'],
        //                     'station_id' => $station_id,
        //                     'result' => $unDo['result'],
        //                     'false_times' => $unDo['result'] ? 1 : 0,
        //                     'last_time' => $unDo['test_time'],
        //                 ], false)->save();
        //         } else {
        //             if($unDo['test_time'] > $old['last_time']) {
        //                 if($unDo['result'])
        //                     WTestModel::create()
        //                         ->update([
        //                             'result' => 1,
        //                             'last_time' => $unDo['test_time'],
        //                             'false_times' => QueryBuilder::inc(1),
        //                     ], ['id' => $old['id']]);
        //                 else
        //                     WTestModel::create()
        //                         ->update([
        //                             'result' => 0,
        //                             'last_time' => $unDo['test_time'],
        //                     ], ['id' => $old['id']]);
        //             }
        //         }
        //         WTest2020Model::create()->update(['sync_to_mongo'  => 1], ['id' => $unDo['id']]);
        //     }
        // }
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // 异常处理,进入队列
        echo $throwable.PHP_EOL;
    }
}
