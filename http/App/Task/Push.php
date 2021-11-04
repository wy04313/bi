<?php

namespace App\Task;

use EasySwoole\Component\Singleton;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use App\Model\WDataModel;
use App\Task\OnlineUser;
use App\Task\U8;

use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Task\TaskManager;

class Push implements TaskInterface
{
    use Singleton;

    // 生产缺料推送
    public function run(int $taskId, int $workerIndex)
    {

        // 滚动数据最少拉取10条
        $sql = "
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
        ";
        $rollList = U8::getInstance()->getAllFromU8($sql);
        foreach($rollList as &$v) {
            $v['reqQty'] = round($v['reqQty'],2);
            $v['isQue'] = round($v['isQue'],2);
            $v['qty'] = (int)$v['isQue'];
            $v['level'] = $this->getLevel($v);
        }

        $today = (int)date('Ymd');
        $updateData = [
            'roll_list'  => json_encode($rollList,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'roll_list_updated' => time()
        ];
        if(WDataModel::create()->where('date', $today)->get() === null) {
            $updateData['date'] = $today;
            WDataModel::create()->data($updateData, false)->save();
        } else {
            WDataModel::create()->update($updateData, ['date' => $today]);
        }

        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();

        $pushData = $this->envJson([
            'roll' => [
                'list' => $rollList, 'roll_list_updated' => date('Y-m-d H:i:s', $updateData['roll_list_updated'])],
            ]);

        json_encode([

        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        foreach ($users as $v) {
            if(preg_match('/^.*?line\\.html.*?$/', $v['url'])) {
                $server->push($v['fd'], $pushData);
                break;
            }
        }
    }

    private function getLevel($v){
        if($v['qty'] < 0)
            return '<p class="cf1">负库存</p>';
        elseif ($v['qty'] == 0)
            return '<p class="cf2">0库存</p>';
        else
            return '<p class="cf3">库存不足</p>';
    }

    private function getLastData(){
        // 取最新的即可
        return WDataModel::create()
            ->field('b1,b2,b3,b4,b5,b6,today_task_title,per,roll_list,roll_list_updated,now_line,now_line_updated')
            ->order('id','desc')
            ->get()->toArray();
    }

    // 从mysql中获取数据
    public function getMysqlData($page){
         switch ($page) {
            case 'line':
                $field = 'b1,b2,b3,b4,b5,b6,today_task_title,per,roll_list,roll_list_updated,now_line,now_line_updated,roll_list,roll_list_updated';
                break;
            case 'total': //汇总页面的
                $field = '';
                break;
            default:
                // b1-b6同时存在
                $field = $page;
                break;
        }
        $block = $this->getLastData($field);

        $data = [];
        if(isset($block['b1'])) {
            $data['block_data'] = [
                'b1' => ['lab' => '上工人数', 'val' => $block['b1']],
                'b2' => ['lab' => '当前温度', 'val' => $block['b2']],
                'b3' => ['lab' => '不良警报(次)', 'val' => $block['b3']],
                'b4' => ['lab' => '未完工订单', 'val' => $block['b4']],
                'b5' => ['lab' => '完工数量', 'val' => $block['b5']],
                'b6' => ['lab' => '不良品(pcs)', 'val' => $block['b6']],
            ];
        }
        if(isset($block['today_task_title'])) {
            $data['today_task'] = [
            'title' => $block['today_task_title'],
            'per' => $block['per'],
            ];
        }

        if(isset($block['now_line'])) {
            $data['now_line'] = json_decode($block['now_line'], true);// 当前生产订单实况
        }

        if(isset($block['roll_list'])) {
            $data['roll']['list'] = json_decode($block['roll_list'], true);
            $data['roll']['roll_list_updated'] = date('m/d H:i:s', $block['roll_list_updated']);
        }
        return $data;
    }

    public function pushData($field, $page){
        $data = $this->getMysqlData($field);
        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        // switch ($page) {
        //     case 'line':
        //         foreach ($users as $v) {
        //             if($v['created'])) {
        //                 $server->push($v['fd'], $this->envJson($data));
        //                 break;
        //             }
        //         }
        //         break;
        //     case 'total':
        //         foreach ($users as $v) {
        //             if(preg_match('/^.*?total\\.html.*?$/', $v['url'])) {
        //                 $server->push($v['fd'], $this->envJson($data));
        //                 break;
        //             }
        //         }
        //         break;
        // }
    }

    public function pushUrl($ip, $url){
        $users = OnlineUser::getInstance()->table();
        $server = ServerManager::getInstance()->getSwooleServer();
        foreach ($users as $v) {
            if($v['ip'] === $ip) {
                $server->push($v['fd'], $this->envJson(['url' => $url], 'jump'));
                break;
            }
        }
    }

    private function envJson($data,$case = 'ok'){
        return json_encode(compact('case','data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // 异常处理,进入队列
        echo $throwable.PHP_EOL;
    }
}
