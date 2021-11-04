<?php
date_default_timezone_set('Asia/Shanghai');

$a = '20210202000100';
$a = strtotime($a);



echo date('Gi', $a);die;
echo date('Y-m-d H:i:s', 1635578248);die;
        $now_line = [
            'title' => '1234455',
            'data_x' => ['P板','K板','Q板','绝缘耐压','下线测试'],
            'data_y1' => [500,500,500,500,500],
            'data_y2' => [400,432,234,543,130],
            'data_y3' => [22,45,45,264],
        ];
echo json_encode($now_line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);die;


$a = ['controller' => 'Index','action' => 'getOne','params' => [
    'ip' => '127.0.0.1',
]];

