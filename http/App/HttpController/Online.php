<?php

namespace App\HttpController;

use App\Task\OnlineUser;
use App\Model\WDataProductModel;
use App\Model\WMesRelationModel;

class Online extends Base
{
    // 返回的表信息不知为何
    public function list(){
        $online = OnlineUser::getInstance();
        $this->writeJson(200, 'OK', $online->table());
    }

    public function day(){
        // $res = WDataProductModel::day('2018');
        // $res = WDataProductModel::create()->day('2018');
        // print_r($res);
        // $group = TestUserListModel::create()->field('sum(age) as age, `name`')->group('name')->all(null);
        $relation_num = WMesRelationModel::create()
            ->field('sum(id) as tt')
            ->where("id < 1001")
            ->group('station_id')
            ->all();
print_r($relation_num);
    }

}
