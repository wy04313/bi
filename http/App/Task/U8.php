<?php

namespace App\Task;

use EasySwoole\Component\Singleton;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use App\Task\OnlineUser;
use App\Model\WDataModel;
use EasySwoole\EasySwoole\ServerManager;


class U8 implements TaskInterface
{
    use Singleton;

    // 缺料和未完工订单
    public function run(int $taskId, int $workerIndex)
    {
        /*
            3302 贴片
            3305 电控
            3306 电机定子
            3307 电机总装
         */
        $dept = [
            '3302' => ['MoCode' => [],'InvCode' => [], 'expQty' => 0, 'inQty' => 0],
            '3305' => ['MoCode' => [],'InvCode' => [], 'expQty' => 0, 'inQty' => 0],
            '3306' => ['MoCode' => [],'InvCode' => [], 'expQty' => 0, 'inQty' => 0],
            '3307' => ['MoCode' => [],'InvCode' => [], 'expQty' => 0, 'inQty' => 0],
        ];
        $data = $this->getAllFromU8("
                select o.MoCode,d.InvCode,d.Qty expQty,d.QualifiedInQty inQty,d.MDeptCode from mom_orderdetail d
                left join mom_order o on d.MoId = o.MoId
                WHERE d.status = 3
                and o.mocode not like '%MRP%'
                and o.mocode not like '%PK%'
                and d.MDeptCode in(3302,3305,3306,3307)
                and d.QualifiedInQty < d.Qty
                ORDER BY d.modid desc
            ");

        $redisData = [];
        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);

        if($data) {
            foreach ($data as $v) {
                $dept[$v['MDeptCode']]['MoCode'][] = [
                    'order' => $v['MoCode'],
                    'over' => round($v['inQty']),
                    'unover' => round(($v['expQty'] - $v['inQty']))
                ];
                $dept[$v['MDeptCode']]['InvCode'][] = $v['InvCode'];
                $dept[$v['MDeptCode']]['expQty'] = $dept[$v['MDeptCode']]['expQty'] + $v['expQty'];
                $dept[$v['MDeptCode']]['inQty'] = $dept[$v['MDeptCode']]['inQty'] + $v['inQty'];
            }
            foreach ($dept as $k => $v) {
                $redisData["line_{$k}_today_task"] = $this->formatToJson($v);
                $redisData["line_{$k}_today_task_last_updated"] = date('m/d H:i:s');
            }
            $redis->mSet($redisData);
        }


        // 生产缺料推送 滚动数据最少拉取10条

        $rollList = $this->getAllFromU8("
            select tba.*,isnull(tbb.qty,0) as qty,isnull(tbb.qty,0)-tba.reqQty as isQue from (
            select c.InvCode,sum(c.qty - c.baseqtyn/c.baseqtyd*e.inQty) as reqQty --剩余的真实需用量
            from mom_order as a
            left join mom_orderdetail as b on a.moid=b.moid
            left join mom_moallocate as c on c.modid = b.modid
            left join (select b.modid,sum(isnull(d.iQuantity,0)) as inQty
            from  mom_orderdetail as b
            left join rdrecords10 as d on d.iMPoIds =b.modid
            where b.status=3 group by b.modid) as e on e.modid=b.modid
            where b.status=3
            group by c.InvCode having c.InvCode is not null
            ) as tba left join
            (select cinvcode,sum(iQuantity) as qty from CurrentStock where cWhCode in ('C01','C02','C03','C71','C72','C73')
            group by cinvcode) as tbb on tba.InvCode=tbb.cinvcode
            where isnull(tbb.qty,0)-tba.reqQty < 0
        ");

        if($rollList) {
            foreach($rollList as &$v) {
                $v['reqQty'] = round($v['reqQty'],2);
                $v['isQue'] = round($v['isQue'],2);
                $v['qty'] = (int)$v['isQue'];
                $v['level'] = $this->getLevel($v);
            }

            $updateData = [
                'list'  => $rollList,
                'list_updated' => date('m/d H:i:s')
            ];
            $redis->set('less_material', $this->formatToJson($updateData));
        }
        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
        if($data || $rollList) {
            $users = OnlineUser::getInstance()->table();
            $server = ServerManager::getInstance()->getSwooleServer();
            // 缺料全页面推送
            foreach ($users as $v) {
                if(preg_match('/^line(\d{4})$/', $v['page_name'], $cost_type)) {
                    $pd['roll'] = $updateData;
                    $pd['today_task'] = $this->formatLineData($dept[$cost_type[1]]);
                    $pd['over_order'] = $this->formatOverOrder($dept[$cost_type[1]]['MoCode']);
                } else {
                    $pd['roll'] = $updateData;
                }
                $server->push($v['fd'], $this->writeToJson($pd));
            }
        }
    }
    /*
    Array
        (
            [0] => Array
                (
                    [order] => 102SC2111029
                    [over] => .000000
                    [unover] => 32
                )

            [1] => Array
                (
                    [order] => 102SC2111028
                    [over] => .000000
                    [unover] => 19
                )

            [2] => Array
                (
                    [order] => 102SC2111020
                    [over] => .000000
                    [unover] => 43
                )
        )
     */
    private function formatOverOrder($data){
        $order = [];
        $over = [];
        $unover = [];
        foreach ($data as $v) {
            $order[] = $v['order'];
            $over[] = $v['over'];
            $unover[] = $v['unover'];
        }
        return compact('order','over','unover');
    }

    // $data['today_task'] = [
    //     'title' => $v['today_task_title'],
    //     'per' => $v['per'],
    // ];
    // '3302' => ['MoCode' => [],'InvCode' => [], 'expQty' => 0, 'inQty' => 0],
    private function formatLineData($data){
        return [
            'title' => '未完工订单统计',
            'per' => round($data['inQty']/$data['expQty']*100),
            'last_updated' => date('m/d H:i:s')
        ];
    }

    private function getAllFromU8($sql){
        $conn = new \PDO("sqlsrv:server=10.0.6.218;database=UFDATA_102_2021","sa","abc@123");
        $res = $conn->query($sql);
        return $res->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getLevel($v){
        if($v['qty'] < 0)
            return '<p class="cf1">负库存</p>';
        elseif ($v['qty'] == 0)
            return '<p class="cf2">0库存</p>';
        else
            return '<p class="cf3">库存不足</p>';
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
