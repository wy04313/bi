<?php declare(strict_types=1);

namespace App\Task;

use EasySwoole\Component\Singleton;

class Mysql
{

    use Singleton;

    /*新进页面,获取数据,可能是页面,也可能是一部分数据
        兵熊熊一个，将熊熊一窝,将帅无能,累死三军
        一次页面全部信息推送,服务器很强大,能跑就行,没事,
     */
    public function getPageData($page_name){
        switch ($page_name) {
            case 'line3302':# line数据至页面
            case 'line3305':
            // case 'line3306':
            case 'line3307':
                return $this->getLinePageData($page_name);
            case 'total':
                return $this->getTotalPageData($page_name);
        }
    }

    // total 页面数据
    public function getTotalPageData($page_name,$sub = null){
        $subData = $sub === null ? $this->definetotalSubData() : explode(',', $sub);
        $field = []; //待取字段
        if(in_array('dashboard', $subData))
            $field = array_merge($field, ['total_wd','total_sd', 'total_yield_rate','total_power']);
        if(in_array('less_material', $subData)) $field[] = 'less_material';
        if(in_array('watt_meter_weeks', $subData))
            $field = array_merge($field, ['weeks','watt_meter_weeks']);
        if(in_array('equ_used', $subData))
            $field = array_merge($field, ['total_equ_used_name','total_equ_used_value']);
        if(in_array('block_data', $subData))
            $field = array_merge($field, [
                'line_3302_block_b0','line_3302_block_b1','line_3302_block_b3',
                'line_3305_block_b0','line_3305_block_b1','line_3305_block_b3',
                // 'line_3306_block_b0','line_3306_block_b1','line_3306_block_b3',
                'line_3307_block_b0','line_3307_block_b1','line_3307_block_b3',
                'total_in_year','total_in_year_title','total_in_all'
                ]);

        if(in_array('unover', $subData))
            $field = array_merge($field, [
                    'line_3302_today_task',
                    'line_3305_today_task',
                    // 'line_3306_today_task',
                    'line_3307_today_task'
                ]); //未完工订单

        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        // $field = array_unique($field);
        $redisData = $redis->mGet($field);

        $tmp = [];
        foreach ($field as $k => $v) {
            $tmp[$v] = $redisData[$k];
        }

        if(in_array('dashboard', $subData))
            $data['dashboard'] = [
                'yield_rate' => $tmp['total_yield_rate'], //良品率
                'wd' => $tmp['total_wd'], //温度
                'sd' => $tmp['total_sd'], //湿度
                'power' => $redis->LINDEX('watt_meter_weeks', 0), //电表度数
            ];
        if(in_array('less_material', $subData))
            $data['roll'] = json_decode($tmp['less_material'], true);
        // 7日电表
        if(in_array('watt_meter_weeks', $subData)) {
            $data['watt_meter_weeks']['x'] = $this->fmtWeeks($redis->lrange('weeks', 0, 6));
            $data['watt_meter_weeks']['y'] = $this->fmtWattMeter($redis->lrange('watt_meter_weeks', 0, 7));
        }

        //今天入库记录
        if(in_array('total_in_todays', $subData)) {
            $data['total_in_todays'] = $this->fmtColumn(json_decode($redis->get('total_in_todays')));
        }

        // 应到,实到人数和3个圆形图
        if(in_array('block_data', $subData)) {
            $allUser = array_sum([
                $tmp['line_3302_block_b0'],
                $tmp['line_3305_block_b0'],
                $tmp['line_3307_block_b0']
            ]);//应到人数
            // 应到,实到
            $data['p3302'] = [
                ['name' => '总'.$tmp['line_3302_block_b0'].'人', 'value' => (int)$tmp['line_3302_block_b0']],
                ['name' => '实到'.$tmp['line_3302_block_b1'].'人', 'value' => (int)$tmp['line_3302_block_b1']],
            ];
            $data['p3305'] = [
                ['name' => '总'.$tmp['line_3305_block_b0'].'人', 'value' => (int)$tmp['line_3305_block_b0']],
                ['name' => '实到'.$tmp['line_3305_block_b1'].'人', 'value' => (int)$tmp['line_3305_block_b1']],
            ];
            $data['p3307'] = [
                ['name' => '总'.$tmp['line_3307_block_b0'].'人', 'value' => (int)$tmp['line_3307_block_b0']],
                ['name' => '实到'.$tmp['line_3307_block_b1'].'人', 'value' => (int)$tmp['line_3307_block_b1']],
            ];

            $data['block_data']['total_b0'] = $allUser;
            $data['block_data']['total_b1'] = $allUser - array_sum([
                $tmp['line_3302_block_b1'],
                $tmp['line_3305_block_b1'],
                $tmp['line_3307_block_b1']
            ]);//实到到人数

            $data['block_data']['total_b3'] = array_sum([
                $tmp['line_3302_block_b3'],
                $tmp['line_3305_block_b3'],
                $tmp['line_3307_block_b3']
            ]);//不良警报

            // $data['block_data']['total_in_today'] = $redis->LINDEX('total_in_todays', 0);
            //今日入库总计 total_in_todays
            $total_in_todays = json_decode($redis->get('total_in_todays'), true);
            $data['block_data']['total_in_today'] = $total_in_todays ? array_sum(array_column($total_in_todays, 'val')) : [];



            $data['block_data']['total_in_year'] = $tmp['total_in_year']; //今年入库总计
            $data['block_data']['total_in_year_title'] = $tmp['total_in_year_title']; //今年入库总计标题
            $data['block_data']['total_in_all'] = $tmp['total_in_all']; //入库总计
        }

        // 设备软件正常使用时间占比
        if(in_array('equ_used', $subData)) {
            $data['equ_used']['name'] = explode(',',$tmp['total_equ_used_name']);
            $data['equ_used']['value'] = explode(',', $tmp['total_equ_used_value']);
        }

        // 未完工订单
        if(in_array('unover', $subData)) {
            $t3302 = json_decode($tmp['line_3302_today_task'], true);
            $t3302 = $this->fmtOrder($t3302['MoCode']);
            $t3305 = json_decode($tmp['line_3305_today_task'], true);
            $t3305 = $this->fmtOrder($t3305['MoCode']);
            $t3307 = json_decode($tmp['line_3307_today_task'], true);
            $t3307 = $this->fmtOrder($t3307['MoCode']);
            $data['unover']['name'] = array_slice(array_merge($t3302['name'],$t3305['name'],$t3307['name']), 0, 20);
            $data['unover']['v1'] = array_slice(array_merge($t3302['v1'],$t3305['v1'],$t3307['v1']),0,20);
            $data['unover']['v2'] = array_slice(array_merge($t3302['v2'],$t3305['v2'],$t3307['v2']),0,20);

        }

        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);

        return $data;
    }

    // 整理柱形图数据
    private function fmtColumn($data){
        $arr = [];
        foreach ($data as $v) {
            $arr[]['x'] = $v['name'];
            $arr[]['y'] = $v['val'];
        }
        return $arr;
    }

    /* 整理各部门订单汇总
        [0] => Array
            (
                [order] => 102SC2111068
                [over] => 0
                [unover] => 65
            )

        [1] => Array
            (
                [order] => 102SC2111063
                [over] => 0
                [unover] => 52
            )

     */
    private function fmtOrder($data){
        $name = [];
        $v1 = []; //预计
        $v2 = []; // 未完工
        foreach ($data as $v) {
            $name[] = $v['order'];
            $v1[] = $v['over'] + $v['unover'];
            $v2[] = $v['over'];
        }
        return compact('name','v1','v2');
    }

    private function definetotalSubData(){
        return [
            'dashboard', // 仪表盘数据
            'less_material', // 物料缺失的滚动数据
            'watt_meter_weeks', // 近七日用电量
            'block_data', // 卡片数据
            'total_in_todays', // 今日入库统计
            'equ_used', // 设备使用率
            'unover', // 未完工订单
        ];
    }

    // 格式换年月日
    private function fmtWeeks($data){
        foreach ($data as &$v) {
            $v = date('m/d', strtotime($v));
        }
        return $data;
    }

    // 作差,7天的值,不足补全
    private function fmtWattMeter($data){
        $tmp = [];
        foreach ($data as $k => $v) {
            if($k < 7) {
                $tmp[] = round($v - $data[$k + 1], 2);
            }
        }
        return $tmp;
    }

    private function defineLineSubData(){
        return [
            'block_data', //顶部6个块元素
            'today_task', //今日任务
            'now_line', //当前订单实况组星图
            'less_material', //物料缺失的滚动数据
            'over_order', //未完工订单柱形图
        ];
    }
    /*
        page_name
        sub 推送的块,逗号分割,默认all为整页面推送

            block_data 顶部6个块元素

     */
    public function getLinePageData($page_name,$sub = null){
        preg_match('/^line(\d{4})$/', $page_name, $cost_type);
        $cost_type = $cost_type[1];
        $subData = $sub === null ? $this->defineLineSubData() : explode(',', $sub);
        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        $field = []; //待取字段
        if(in_array('block_data', $subData)) {
            $field = array_merge($field, [
                'line_'.$cost_type.'_block_b0','line_'.$cost_type.'_block_b0_last_updated',
                'line_'.$cost_type.'_block_b1','line_'.$cost_type.'_block_b1_last_updated',
                'line_'.$cost_type.'_block_b2','line_'.$cost_type.'_block_b2_last_updated',
                'line_'.$cost_type.'_block_b3','line_'.$cost_type.'_block_b3_last_updated',
                'line_'.$cost_type.'_block_b4','line_'.$cost_type.'_block_b4_last_updated',
                'line_'.$cost_type.'_block_b5','line_'.$cost_type.'_block_b5_last_updated',
                'line_'.$cost_type.'_block_b6','line_'.$cost_type.'_block_b6_last_updated',
            ]);
        }

        if(in_array('today_task', $subData) || in_array('over_order', $subData))
            $field = array_merge($field, ['line_'.$cost_type.'_today_task']);
        if(in_array('less_material', $subData))
            $field = array_merge($field, ['less_material']);
        if(in_array('now_line', $subData))
            $field = array_merge($field, ['line_'.$cost_type.'_now_line', 'line_'.$cost_type.'_now_line_last_updated']);
        $redisData = $redis->mGet($field);
        $tmp = [];
        foreach ($field as $k => $v) {
            $tmp[$v] = $redisData[$k];
        }
        // 此时为key => value

        if(in_array('block_data', $subData)) {
            $data['block_data'] = [
                'b0' => [
                    'lab' => '应到人数',
                    'val' => $tmp['line_'.$cost_type.'_block_b0'],
                    'last_updated' => $tmp['line_'.$cost_type.'_block_b0_last_updated']],
                'b1' => [
                    'lab' => '上工人数',
                    'val' => $tmp['line_'.$cost_type.'_block_b1'],
                    'last_updated' => $tmp['line_'.$cost_type.'_block_b1_last_updated']],
                'b2' => [
                    'lab' => '当前温度',
                    'val' => $tmp['line_'.$cost_type.'_block_b2'],
                    'last_updated' => $tmp['line_'.$cost_type.'_block_b2_last_updated']],
                'b3' => [
                    'lab' => '今日不良警报(次)',
                    'val' => $tmp['line_'.$cost_type.'_block_b3'],
                    'last_updated' => $tmp['line_'.$cost_type.'_block_b3_last_updated']],
                'b4' => [
                    'lab' => '完工数量',
                    'val' => $tmp['line_'.$cost_type.'_block_b4'],
                    'last_updated' => $tmp['line_'.$cost_type.'_block_b4_last_updated']],
                'b5' => [
                    'lab' => '未完工订单数',
                    'val' => $tmp['line_'.$cost_type.'_block_b5'],
                    'last_updated' => $tmp['line_'.$cost_type.'_block_b5_last_updated']],
                'b6' => [
                    'lab' => '今日不良品(pcs)',
                    'val' => $tmp['line_'.$cost_type.'_block_b6'],
                    'last_updated' => $tmp['line_'.$cost_type.'_block_b6_last_updated']],
            ];
        }
        if(in_array('today_task', $subData) || in_array('over_order', $subData)) {
            $today_task = json_decode($tmp['line_'.$cost_type.'_today_task'], true);
            $data['today_task'] = [
                'title' => "未完工订单累计: {$today_task['expQty']} pcs",
                'per' => round($today_task['inQty']/$today_task['expQty']* 100),
            ];

            if(in_array('over_order', $subData))
                $data['over_order'] = $this->formatOverOrder($today_task['MoCode']);
        }
        if(in_array('less_material', $subData))
            $data['roll'] = json_decode($tmp['less_material'], true);
        if(in_array('now_line', $subData))
            $data['now_line'] = json_decode($tmp['line_'.$cost_type.'_now_line'], true);// 当前生产订单实况

        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
        return $data;
    }

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

    // 获取考勤机实到人数
    public function getArriveUsers(){

    }

}
