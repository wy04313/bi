<?php

namespace App\WebSocket\Controller;

use App\Model\WMesTaskModel;
// use App\Model\WMesProductModel;
use EasySwoole\Mysqli\QueryBuilder;

class Data extends Base
{
    public function getUnOverTask(){
        // $params = $this->caller()->getArgs();
        // $this->response()->setMessage();
        $name = [];
        $over = [];
        $number = [];

        // 无法跑出join,原因未知
        // $task = WMesTaskModel::create()
        //     ->alias('t')
        //     ->field('pd.inv_name,pd.version,t.number,t.relation_over,t.over_num')
        //     ->join('w_mes_product pd', 't.pro_id = pd.id', 'LEFT')
        //     // ->where('t.effect = 1 and t.relation_over < t.number')
        //     ->where('t.effect = 1')
        //     ->order('t.id','desc')
        //     ->all()->toArray();
        $task = WMesTaskModel::create()->func(function ($builder){
            $builder->raw('SELECT  t.number,t.relation_over,t.over_num,p.inv_name,p.version FROM w_mes_task AS t LEFT JOIN w_mes_product p on t.pro_id = p.id WHERE  t.effect = 1 and t.relation_over < t.number ORDER BY t.id DESC');
            return true;
        });
        foreach ($task as $v) {
            // $name[] = $v['inv_name'].'-'.$v['version'];
            $name[] = $v['inv_name'];
            // $relation[] = $v['over_num'];
            $over[] = $v['relation_over'];
            $number[] = $v['number'];
        }
        $this->response()->setMessage($this->toJson(10108,'OK',compact('name','number','over')));
    }

    public function getGoodPer(){
        $good = WMesTaskModel::create()->func(function ($builder){
            $builder->raw('SELECT (sum(number)  - sum(bad_num))/sum(number) as good_per from w_mes_task where effect = 1 and relation_over < number');
            return true;
        });
        $good_per = $good[0]['good_per'] === 1 ? 100 : sprintf('%.4f', $good[0]['good_per'] * 100);
        $this->response()->setMessage($this->toJson(10112,'OK',['good_per' => $good_per]));
    }

    // 例子
    public function getAll(int $page = 1, int $pageSize = 10, string $field = '*'): array
    {
        $list = $this
            ->withTotalCount()
            ->order($this->schemaInfo()->getPkFiledName(), 'DESC')
            ->field($field)
            ->limit($pageSize * ($page - 1), $pageSize)
            ->all();
        $total = $this->lastQueryResult()->getTotalCount();;
        return ['total' => $total, 'list' => $list];
    }


}
