<?php
/**
 * Created by PhpStorm.
 * User: Apple
 * Date: 2018/11/1 0001
 * Time: 14:42
 */
namespace App\WebSocket\Controller;

use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Task\TaskManager;


/**
 * Class Index
 *
 * 此类是默认的 websocket 消息解析后访问的 控制器
 *
 * @package App\WebSocket
 */
class Index extends Base
{

    private function tmpArray(){
        return [
            ['main_barcode' => 'C01S010000-C00008740','relate_barcode' => 'C01S012001-200007-DC2007870','station_id' => '装备车间哦','created' => '17:35', 'worker_id' => '王勇']
            ,['main_barcode' => 'C01S010000-C00008675','relate_barcode' => 'C01S012001-200007-DC2007868','station_id' => '在总装车间','created' => '22:45', 'worker_id' => '冯智恩']
            ,['main_barcode' => 'C01S010000-C00008690','relate_barcode' => 'C01S012001-200007-DC2007801','station_id' => '自己的二工位','created' => '03:22', 'worker_id' => '马火升']
            ,['main_barcode' => 'C01S010000-C00008696','relate_barcode' => 'C01S012001-200007-DC2007792','station_id' => '卫生间坑位','created' => '13:42', 'worker_id' => '封志文']
            ,['main_barcode' => 'C01S010000-C00008694','relate_barcode' => 'C01S012001-200007-DC2007802','station_id' => '祖国华椴','created' => '05:33', 'worker_id' => '霍建国']
            ,['main_barcode' => 'C01S010000-C00008679','relate_barcode' => 'C01S012001-200007-DC2007872','station_id' => '民族党的人','created' => '15:03', 'worker_id' => '人民的名字']
            ,['main_barcode' => 'C01S010000-C00008675','relate_barcode' => 'C01S012001-200007-DC2007868','station_id' => '在总装车间','created' => '22:45', 'worker_id' => '冯智恩']
            ,['main_barcode' => 'C01S010000-C00008690','relate_barcode' => 'C01S012001-200007-DC2007801','station_id' => '自己的二工位','created' => '03:22', 'worker_id' => '马火升']
            ,['main_barcode' => 'C01S010000-C00008696','relate_barcode' => 'C01S012001-200007-DC2007792','station_id' => '卫生间坑位','created' => '13:42', 'worker_id' => '封志文']
            ,['main_barcode' => 'C01S010000-C00008694','relate_barcode' => 'C01S012001-200007-DC2007802','station_id' => '祖国华椴','created' => '05:33', 'worker_id' => '霍建国']
            ,['main_barcode' => 'C01S010000-C00008679','relate_barcode' => 'C01S012001-200007-DC2007872','station_id' => '民族党的人','created' => '15:03', 'worker_id' => '人民的名字']
            ,['main_barcode' => 'C01S010000-C00008675','relate_barcode' => 'C01S012001-200007-DC2007868','station_id' => '在总装车间','created' => '22:45', 'worker_id' => '冯智恩']
            ,['main_barcode' => 'C01S010000-C00008690','relate_barcode' => 'C01S012001-200007-DC2007801','station_id' => '自己的二工位','created' => '03:22', 'worker_id' => '马火升']
            ,['main_barcode' => 'C01S010000-C00008696','relate_barcode' => 'C01S012001-200007-DC2007792','station_id' => '卫生间坑位','created' => '13:42', 'worker_id' => '封志文']
            ,['main_barcode' => 'C01S010000-C00008694','relate_barcode' => 'C01S012001-200007-DC2007802','station_id' => '祖国华椴','created' => '05:33', 'worker_id' => '霍建国']
            ,['main_barcode' => 'C01S010000-C00008679','relate_barcode' => 'C01S012001-200007-DC2007872','station_id' => '民族党的人','created' => '15:03', 'worker_id' => '人民的名字']
            ,['main_barcode' => 'C01S010000-C00008675','relate_barcode' => 'C01S012001-200007-DC2007868','station_id' => '在总装车间','created' => '22:45', 'worker_id' => '冯智恩']
            ,['main_barcode' => 'C01S010000-C00008690','relate_barcode' => 'C01S012001-200007-DC2007801','station_id' => '自己的二工位','created' => '03:22', 'worker_id' => '马火升']
            ,['main_barcode' => 'C01S010000-C00008696','relate_barcode' => 'C01S012001-200007-DC2007792','station_id' => '卫生间坑位','created' => '13:42', 'worker_id' => '封志文']
            ,['main_barcode' => 'C01S010000-C00008694','relate_barcode' => 'C01S012001-200007-DC2007802','station_id' => '祖国华椴','created' => '05:33', 'worker_id' => '霍建国']
            ,['main_barcode' => 'C01S010000-C00008679','relate_barcode' => 'C01S012001-200007-DC2007872','station_id' => '民族党的人','created' => '15:03', 'worker_id' => '人民的名字']
        ];
    }

    public function hello()
    {
        $tmp = $this->tmpArray();
        $keys = array_rand($tmp,2);
        $data = [];
        foreach ($keys as $v) {
            $data[] = $tmp[$v];
        }

        $data = [
            'type' => 'add_relation',
            'msg' => '封志文你是个大山炮还是个骚货',
            'data' => $data,
            'request' => $this->caller()->getArgs(),
        ];
        return $this->response()->setMessage(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }


    public function who(){
        $this->response()->setMessage('your fd is '. $this->caller()->getClient()->getFd());
    }

    function delay()
    {
        $this->response()->setMessage('this is delay action');
        $client = $this->caller()->getClient();

        // 异步推送, 这里直接 use fd也是可以的
        TaskManager::getInstance()->async(function () use ($client){
            $server = ServerManager::getInstance()->getSwooleServer();
            $i = 0;
            while ($i < 5) {
                sleep(1);
                $server->push($client->getFd(),'push in http at '. date('H:i:s'));
                $i++;
            }
        });
    }

    // 心跳
    function heartbeat()
    {
/*        $num = rand(1,5);// 速记取出
        $tmp = $this->tmpArray();
        $keys = array_rand($tmp, $num);
        $data = [];

        if($num == 1) {
            $data[] = $tmp[$keys];
        } else {
            foreach ($keys as $v) {
                $data[] = $tmp[$v];
            }
        }

        $data = [
            'change' => ['add_relation','today_over'],
            'msg' => '封志文你是个大山炮还是个骚货',
            'add_relation' => $data,
            'today_over' => $this->randomFloat(33.33,99.99),
            'request' => '没有参数',
        ];


        $conf = \EasySwoole\EasySwoole\Config::getInstance()->getConf('REDIS');
        $redis = new \EasySwoole\Redis\Redis(new \EasySwoole\Redis\Config\RedisConfig($conf));
        $redis->select(1);
        $fds = $redis->keys('*'); //这里取出来的是key
        if($fds) {
            $server = ServerManager::getInstance()->getSwooleServer();
            foreach ($fds as $v) {
                $server->push($v, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }*/




        // $client = $this->caller()->getClient();
        // $fd = $client->getFd();
        $this->response()->setMessage('PONG');
    }


    // 测试用,随机数字,带有小数
    public function randomFloat($min = 0, $max = 1) {
        $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return sprintf("%.2f",$num);  //控制小数后几位
    }


    // 生产任务完成率
    public function overTaskPer(){
        $task = \EasySwoole\EasySwoole\Task\TaskManager::getInstance();
        $task->async(new \App\Task\CliSync('over_task_per'));
    }
}
