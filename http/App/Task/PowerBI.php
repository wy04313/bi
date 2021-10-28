<?php

namespace App\Task;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Config;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;

use App\Model\FQueueConfigModel;
use App\Model\FUserModel;

class PowerBI extends AbstractCronTask
{
    public static function getRule(): string
    {
        // 定义执行规则 根据Crontab来定义
        return '0 11 * * *';
    }

    public static function getTaskName(): string
    {
        return 'PowerBI';
    }

    public function run(int $taskId, int $workerIndex)
    {
        $conn = new \PDO("sqlsrv:server=10.0.6.218;database=UFDATA_102_2021","sa","abc@123");
        $sql1 = "select count(t1.cPOID) m1 from PO_Pomain t1 inner join PO_Podetails t2  on t1.POID=t2.POID
        where (t2.iQuantity-isnull(t2.iArrQTY,0))>0 and DATEDIFF(dd,t2.dArriveDate,getdate())>=0 and  t2.cbCloser is null";
        $res1 = $conn->query($sql1);
        // 超期未到货订单
        $m1 = $res1->fetch()['m1'];
        $sql2 = 'select count(t1.cCode) m2 from PU_ArrivalVouch t1 inner join PU_ArrivalVouchs t2 on t1.ID=t2.ID
            inner join Inventory t3 on t3.cInvCode=t2.cInvCode inner join Vendor t4 on t1.cVenCode=t4.cVenCode
            where t2.iQuantity<>t2.fValidInQuan and t2.iQuantity>0 and t2.cCloser is null';
        $res2 = $conn->query($sql2);
        // 超期未入库
        $m2 = $res2->fetch()['m2'];

        $this->dbPush([
            'queue_id' => 3,
            'text' => '预警: 截至目前，超期未到货的订单有'.$m1.'条，超期未入库的到货单有'.$m2.'条，请及时跟踪处理。详见采购部报表《采购到货跟踪》',
            'title' => '预警',
            'description' => '截至目前，超期未到货的订单有'.$m1.'条，超期未入库的到货单有'.$m2.'，请及时跟踪处理。详见采购部报表《采购到货跟踪》(目前仅支持内网)',
            'url' => 'http://bi.drive-inno.com/reports/powerbi/05%E9%87%87%E8%B4%AD%E9%83%A8%E6%8A%A5%E8%A1%A8/%E9%87%87%E8%B4%AD%E5%88%B0%E8%B4%A7%E8%B7%9F%E8%B8%AA',
        ]);

    }

    private function dbPush($data){
        $id = $data['queue_id'];
        $conf = FQueueConfigModel::create()
            ->field('reciever,recieve_type,effect')
            ->where('id', $id)
            ->get()
            ->toArray();
        if(!empty($conf['effect'])) {
            $recieve_type = explode(',', $conf['recieve_type']);
            $user = FUserModel::create()
                ->field('top_level,wx_id')
                ->where(explode(',', $conf['reciever']))
                ->all()
                ->toArray();
            $httpUrl = Config::getInstance()->getConf('WX_SEND_MSG');

            foreach ($recieve_type as $v) {
                if($v === 'wx_card') {
                    foreach ($user as $v1) {
                        $company = $v1['top_level'] === 17 ? 1 : 2;
                        $handler = new SwooleHandler();
                        $stack = HandlerStack::create($handler);
                        $client = new Client(['handler' => $stack]);

                        $response = $client->request('POST', $httpUrl.'/sendCard/'.$company, [
                            'form_params' => [
                                'users' => [$v1['wx_id']],
                                'depts' => [],
                                'tags' => [],
                                'title' => $data['title'],
                                'description' => $data['description'],
                                'url' => $data['url']
                            ]
                        ]);
                        // var_dump($response->getBody()->__toString(), $response->getHeaders());
                    }
                }

                if($v === 'wx_text') {
                    foreach ($user as $v1) {
                        $company = $v1['top_level'] === 17 ? 1 : 2;
                        $handler = new SwooleHandler();
                        $stack = HandlerStack::create($handler);
                        $client = new Client(['handler' => $stack]);
                        $client->request('POST', $httpUrl.'/sendText/'.$company, [
                            'form_params' => [
                                'users' => [$v1['wx_id']],
                                'depts' => [],
                                'tags' => [],
                                'text' => $data['text'],
                            ]
                        ]);
                    }
                }
            }
        }
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        echo $throwable.PHP_EOL;
    }
}
