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
                break;
        }
    }

    public function getLinePageData($page_name){
        preg_match('/^line(\d{4})$/', $page_name, $cost_type);
        $cost_type = $cost_type[1];
        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        $field = [ //要取的字段
            'line_'.$cost_type.'_block_b1','line_'.$cost_type.'_block_b1_last_updated',
            'line_'.$cost_type.'_block_b2','line_'.$cost_type.'_block_b2_last_updated',
            'line_'.$cost_type.'_block_b3','line_'.$cost_type.'_block_b3_last_updated',
            'line_'.$cost_type.'_block_b4','line_'.$cost_type.'_block_b4_last_updated',
            'line_'.$cost_type.'_block_b5','line_'.$cost_type.'_block_b5_last_updated',
            'line_'.$cost_type.'_block_b6','line_'.$cost_type.'_block_b6_last_updated',

            'line_'.$cost_type.'_now_line','line_'.$cost_type.'_now_line_last_updated',
            'less_material',
            'line_'.$cost_type.'_today_task',
        ];
        $redisData = $redis->mGet($field);
        $tmp = [];
        foreach ($field as $k => $v) {
            $tmp[$v] = $redisData[$k];
        }

        $data['block_data'] = [
            'b1' => [
                'lab' => '上工人数',
                'val' => $tmp['line_'.$cost_type.'_block_b1'],
                'last_updated' => $tmp['line_'.$cost_type.'_block_b1_last_updated']],
            'b2' => [
                'lab' => '当前温度',
                'val' => $tmp['line_'.$cost_type.'_block_b2'],
                'last_updated' => $tmp['line_'.$cost_type.'_block_b2_last_updated']],
            'b3' => [
                'lab' => '不良警报(次)',
                'val' => $tmp['line_'.$cost_type.'_block_b3'],
                'last_updated' => $tmp['line_'.$cost_type.'_block_b3_last_updated']],
            'b4' => [
                'lab' => '未完工订单',
                'val' => $tmp['line_'.$cost_type.'_block_b4'],
                'last_updated' => $tmp['line_'.$cost_type.'_block_b4_last_updated']],
            'b5' => [
                'lab' => '完工数量',
                'val' => $tmp['line_'.$cost_type.'_block_b5'],
                'last_updated' => $tmp['line_'.$cost_type.'_block_b5_last_updated']],
            'b6' => [
                'lab' => '不良品(pcs)',
                'val' => $tmp['line_'.$cost_type.'_block_b6'],
                'last_updated' => $tmp['line_'.$cost_type.'_block_b6_last_updated']],
        ];
        $data['today_task'] = json_decode($tmp['line_'.$cost_type.'_today_task'], true);
        $data['over_order'] = $this->formatOverOrder($data['today_task']['MoCode']);

        $data['now_line'] = json_decode($tmp['line_'.$cost_type.'_now_line'], true);// 当前生产订单实况
        $data['roll'] = json_decode($tmp['less_material'], true);

        $today_task = json_decode($tmp['line_'.$cost_type.'_today_task'], true);
        $data['today_task'] = [
            'title' => '未完工订单统计',
            'per' => round($today_task['inQty']/$today_task['expQty']* 100),
        ];

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

}
