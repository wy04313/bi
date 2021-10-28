<?php

namespace App\Task;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Mysqli\QueryBuilder;
use App\Model\WDataProductModel;
use App\Model\WMesRelationModel;

class PullProduct extends AbstractCronTask
{
    public static function getRule(): string
    {
        return '0 0 4 * *';
    }

    public static function getTaskName(): string
    {
        return 'PullProduct';
    }

    public function run(int $taskId, int $workerIndex)
    {
        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(14);
        $redis->LTRIM('wy_baba', 0, 14); //清除没用的动态
        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
        $start = strtotime('yesterday');
        $this->dayData($start);
    }

    // 日数据
    public function dayData($start){
        file_put_contents("wy_test_log.txt", print_r(date('Y-m-d H:i:s', $start), true), FILE_APPEND);
        TaskManager::getInstance()->async(function () use($start){
            $end = $start + 86399;
            $year = date('Y', $start);
            $month = date('n', $start);
            $day = date('j', $start);

            $builder = new QueryBuilder();
            $relation_num = WMesRelationModel::create()->func(function ($builder) use($start, $end){
                $builder->raw('SELECT b.pro_sum,b.pro_id,p.pid FROM(select count(*) as pro_sum, pro_id from(SELECT pro_id,count(*) from w_mes_relation WHERE created BETWEEN '.$start.' and '.$end.' and effect = 1 GROUP BY barcode_id,pro_id) a GROUP BY a.pro_id) b join w_mes_product p on b.pro_id = p.id');
                return true;
            });

            $over_num = WMesRelationModel::create()->func(function ($builder) use($start, $end){
                $builder->raw('SELECT b.pro_sum,b.pro_id,p.pid from (SELECT count(*) pro_sum,pro_id from w_mes_barcode WHERE pass_time BETWEEN '.$start.' and '.$end.' and pass_test = 3 GROUP BY pro_id) b join w_mes_product p on b.pro_id = p.id GROUP BY b.pro_id');
                return true;
            });

            $station_over = WMesRelationModel::create()->func(function ($builder) use($start, $end){
                $builder->raw('SELECT b.pro_sum,b.pro_id,p.pid from (SELECT count(*) pro_sum,pro_id from w_mes_barcode WHERE station_time BETWEEN '.$start.' and '.$end.' and station_over = 3 GROUP BY pro_id) b join w_mes_product p on b.pro_id = p.id GROUP BY b.pro_id');
                return true;
            });

            $pros_tmp = []; // 一共有多少中产品
            foreach ($relation_num as $v){
                $pros_tmp[] = $v['pro_id'];
            }
            foreach ($over_num as $v) $pros_tmp[] = $v['pro_id'];
            foreach ($station_over as $v) $pros_tmp[] = $v['pro_id'];
            $pros = array_unique($pros_tmp);

            $data = []; //插入的数据

            foreach ($pros as $v) {
                foreach ($relation_num as $v1) {
                    if($v1['pro_id'] == $v) {
                        $data[$v] = [
                            'year' => $year,
                            'month' => $month,
                            'day' => $day,
                            'relation_num' => $v1['pro_sum'],
                            'pro_pid' => $v1['pid'],
                            'pro_id' => $v1['pro_id'],
                            'created' => date('Y-m-d H:i:s')
                        ];
                    }
                }

                foreach ($over_num as $v1) {
                    if($v1['pro_id'] == $v) {
                        if(isset($data[$v])) {
                            $data[$v]['over_num'] = $v1['pro_sum'];
                        } else {
                            $data[$v] = [
                                'year' => $year,
                                'month' => $month,
                                'day' => $day,
                                'over_num' => $v1['pro_sum'],
                                'pro_pid' => $v1['pid'],
                                'pro_id' => $v1['pro_id'],
                                'created' => date('Y-m-d H:i:s')
                            ];
                        }
                    }
                }

                foreach ($station_over as $v1) {
                    if($v1['pro_id'] == $v) {
                        if(isset($data[$v])) {
                            $data[$v]['station_over'] = $v1['pro_sum'];
                        } else {
                            $data[$v] = [
                                'year' => $year,
                                'month' => $month,
                                'day' => $day,
                                'station_over' => $v1['pro_sum'],
                                'pro_pid' => $v1['pid'],
                                'pro_id' => $v1['pro_id'],
                                'created' => date('Y-m-d H:i:s')
                            ];
                        }

                    }
                }
            }
            foreach ($data as $v) {
                if($did = WDataProductModel::create()->where(['year' => $year, 'month' => $month, 'day' => $day, 'pro_id' => $v['pro_id']])->val('id')) {
                    unset($v['created']);
                    WDataProductModel::create()->where('id', $did)->update($v);
                } else {
                    WDataProductModel::create()->data($v,false)->save();
                }
            }
        });
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
