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
            case 'line3306':
            case 'line3307':
                return $this->getLinePageData($page_name);
            case 'total':
                return '3';
        }
    }

    private function defineSubData(){
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
        $subData = $sub === null ? $this->defineSubData() : explode(',', $sub);
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
