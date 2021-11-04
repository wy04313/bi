<?php

namespace App\HttpController;

use App\MongoDb\Driver;
use App\MongoDb\MongoClient;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Utility\Random;
use App\Model\WPagesOnlineModel;

class Index extends Controller
{
    public function index()
    {
$ips = WPagesOnlineModel::create()->where('ip', '10.10.12.101')->get();

var_dump($ips);
        echo time().PHP_EOL;
        // $res = WMesRelationModel::create()->update(['worker_id' => 90], ['id' => 1]);
//         $res = WMesRelationModel::create()->where('id < 3')->all();
// foreach($res as $one){
//     print_r($one->toArray());
}


//         $relation_num = WMesRelationModel::create()->field('sum(cost_type) as age, `main_barcode`')->group('station_id')->all(null);
// var_dump($relation_num);
        // 使用 mongodb/mongodb composer组件包【建议使用，需要先使用composer安装】
        // $ret = MongoClient::getInstance()->invoke()->callback(function (Driver $driver) {
        //     $ret = $driver->getDb()->user->list->insertOne([
        //         'name' => Random::character(8),
        //         'sex' => 'man',
        //     ]);
        //     if (!$ret) {
        //         $driver->response(false);
        //     }
        //     $driver->response($ret->getInsertedId());
        // });
        // var_dump($ret);

        // $ret = MongoClient::getInstance()->invoke()->callback(function (Driver $driver) {
        //     $ret = [];
        //     $collections = $driver->getDb()->user->listCollections();
        //     foreach ($collections as $collection) {
        //         $ret[] = (array)$collection;
        //     }
        //     $driver->response($ret);
        // });
        // var_dump($ret);
        /**
         * 输出结果：
         * object(MongoDB\BSON\ObjectId)#109 (1) {
             ["oid"]=>
             string(24) "600da377004c82305a02fb52"
           }
         * array(1) {
             [0]=>
             array(1) {
               ["MongoDB\Model\CollectionInfoinfo"]=>
               array(5) {
                 ["name"]=>
                 string(4) "list"
                 ["type"]=>
                 string(10) "collection"
                 ["options"]=>
                 array(0) {
                 }
                 ["info"]=>
                 array(2) {
                   ["readOnly"]=>
                   bool(false)
                   ["uuid"]=>
                   object(MongoDB\BSON\Binary)#110 (2) {
                     ["data"]=>
                     string(16) "EasySwoole"
                     ["type"]=>
                     int(4)
                   }
                 }
                 ["idIndex"]=>
                 array(4) {
                   ["v"]=>
                   int(2)
                   ["key"]=>
                   array(1) {
                     ["_id"]=>
                     int(1)
                   }
                   ["name"]=>
                   string(4) "_id_"
                   ["ns"]=>
                   string(9) "user.list"
                 }
               }
             }
           }
        */

        // 使用 php-mongodb 扩展时(不使用 mongodb/mongodb composer组件包)

        // 插入数据
        // $rets = MongoClient::getInstance()->invoke()->callback(function (Driver $driver) {
        //     $bulk = new \MongoDB\Driver\BulkWrite();

        //     $bulk->insert([
        //         'name' => Random::character(8),
        //         'sex' => 'man',
        //     ]);

        //     $bulk->insert(['_id' => 1, 'x' => 1]);
        //     $bulk->insert(['_id' => 2, 'x' => 2]);

        //     $bulk->update(['x' => 2], ['$set' => ['x' => 1]], ['multi' => false, 'upsert' => false]);
        //     $bulk->update(['x' => 3], ['$set' => ['x' => 3]], ['multi' => false, 'upsert' => true]);
        //     $bulk->update(['_id' => 3], ['$set' => ['x' => 3]], ['multi' => false, 'upsert' => true]);

        //     $bulk->insert(['_id' => 4, 'x' => 2]);

        //     $bulk->delete(['x' => 1], ['limit' => 1]);

        //     $manager = $driver->getDb();
        //     $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 100);
        //     // 查到 user 库的 list 集合中
        //     $ret = $manager->executeBulkWrite('user.list', $bulk, $writeConcern);

        //     printf("Inserted %d document(s)\n", $ret->getInsertedCount()); // 插入条数
        //     printf("Matched  %d document(s)\n", $ret->getMatchedCount()); // 匹配条数
        //     printf("Updated  %d document(s)\n", $ret->getModifiedCount()); // 修改条数
        //     printf("Upserted %d document(s)\n", $ret->getUpsertedCount()); // 修改插入条数
        //     printf("Deleted  %d document(s)\n", $ret->getDeletedCount()); // 删除条数

        //     foreach ($ret->getUpsertedIds() as $index => $id) {
        //         printf('upsertedId[%d]: ', $index);
        //         var_dump($id);
        //     }

        //     if (!$ret) {
        //         return false;
        //     }

        //     return true;
        // });



// $model = WMesRelationModel::create()->where("id < 5")->all();
// foreach($model as $one){
//     var_dump($one->toRawArray());
// }



// print_r($relation_num->toArray());








/*        $rets = MongoClient::getInstance()->invoke()->callback(function (Driver $driver) {
            $bulk = new \MongoDB\Driver\BulkWrite();

            $bulk->insert([
                'main' => '1111111111111111111',
                'rela' => '222222222222222222',
            ]);
            $bulk->insert([
                'main' => '33333333333333333',
                'rela' => '44444444444444444',
            ]);
            // $bulk->insert(['_id' => 1, 'x' => 1]);
            // $bulk->insert(['_id' => 2, 'x' => 2]);

            // $bulk->update(['x' => 2], ['$set' => ['x' => 1]], ['multi' => false, 'upsert' => false]);
            // $bulk->update(['x' => 3], ['$set' => ['x' => 3]], ['multi' => false, 'upsert' => true]);
            // $bulk->update(['_id' => 3], ['$set' => ['x' => 3]], ['multi' => false, 'upsert' => true]);

            // $bulk->insert(['_id' => 4, 'x' => 2]);

            // $bulk->delete(['x' => 1], ['limit' => 1]);

            $manager = $driver->getDb();
            $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 100);
            // 查到 user 库的 list 集合中
            $ret = $manager->executeBulkWrite('mes.relation', $bulk, $writeConcern);

            printf("Inserted %d document(s)\n", $ret->getInsertedCount()); // 插入条数
            // printf("Matched  %d document(s)\n", $ret->getMatchedCount()); // 匹配条数
            // printf("Updated  %d document(s)\n", $ret->getModifiedCount()); // 修改条数
            // printf("Upserted %d document(s)\n", $ret->getUpsertedCount()); // 修改插入条数
            // printf("Deleted  %d document(s)\n", $ret->getDeletedCount()); // 删除条数

            // foreach ($ret->getUpsertedIds() as $index => $id) {
            //     printf('upsertedId[%d]: ', $index);
            //     var_dump($id);
            // }

            if (!$ret) {
                return false;
            }

            return true;
        });

*/



















        // 查询数据
        // $rets = MongoClient::getInstance()->invoke()->callback(function (Driver $driver) {
        //     $filter = ['main' => 'C01S010000-C00201482'];
        //     $options = [
        //         // 'projection' => ['_id' => 0],
        //         'sort' => ['_id' => -1],
        //     ];

        //     // 查询数据
        //     $query = new \MongoDB\Driver\Query($filter, $options);
        //     $cursor = $driver->getDb()->executeQuery('mes.relation', $query)->toArray();
        //     print_r($cursor);

        // });


    }
}
