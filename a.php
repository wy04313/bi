<?php
date_default_timezone_set('Asia/Shanghai');



print_r(json_decode('[]', true));die;

$a = [
    // ['name' => 'wang','age' => 20],
    // ['name' => 'lou','age' => 30],
];

print_r(json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));die;
print_r(array_sum(array_column($a, 'age')));






die;
$a = 'J00044,J00045,J00035,J00086,J00090,J00024,J00025,J00021,J00022,J00062,J00085,J00089,J00091,J00070,J00069,J00068,J00067,J00071,J00026,J00027,J00053,J00055,J00051,J00054,J00052';

$b = array_map(function($v){
    return "'{$v}'";
}, explode(',',$a));

print_r($b);die;
echo (int)(.0000000000);die;

print_r(get_weeks('Ymd'));

function get_weeks($format='Y-m-d'){
    $time = time();
    $date = [];
    for ($i=0; $i<7; $i++){
    $date[$i] = date($format ,strtotime( '+' . $i-7 .' days', $time));

    }
    return $date;
}
die;
$a = [];

$a = array_merge($a, ['1','2',3]);

print_r($a);die;





die;
throw new Exception("抛出异常");die;

echo date('Y-m-d 05:00:00');die;








print_r(json_decode('{"code":0,"msg":"OK","data":{"block_data":{"b1":{"lab":"上工人数","val":0},"b2":{"lab":"当前温度","val":"27.80"},"b3":{"lab":"不良警报(次)","val":11},"b4":{"lab":"未完工订单","val":0},"b5":{"lab":"完工数量","val":0},"b6":{"lab":"不良品(pcs)","val":0}},"today_task":{"title":"封志文(3pcs),冯珂(2pcs)","per":22},"now_line":{"title":"非常2+75","data_x":["P板","K板","Q板","绝缘耐压","下线测试"],"data_y1":[500,500,500,500,500],"data_y2":[400,432,234,543,130],"data_y3":[22,45,45,264]},"roll":{"list":[{"InvCode":"M01C021005","reqQty":26.77,"qty":-20,"isQue":-20.77,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01C011078","reqQty":3300,"qty":-4098,"isQue":-4098,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"L01S100000","reqQty":290,"qty":-390,"isQue":-390,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"G01C010000","reqQty":339,"qty":-202,"isQue":-202,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"H041420001","reqQty":0,"qty":-30,"isQue":-30,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M01C093005","reqQty":699,"qty":-657,"isQue":-657,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K090000112","reqQty":1,"qty":-1,"isQue":-1,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"H020320003","reqQty":258,"qty":-4,"isQue":-4,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"E05SK00023","reqQty":84,"qty":-81,"isQue":-81,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K040000103","reqQty":1,"qty":-1,"isQue":-1,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M01C011017","reqQty":40,"qty":-40,"isQue":-40,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P00C000052","reqQty":2.3,"qty":-2,"isQue":-2.3,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"H021130002","reqQty":258,"qty":-183,"isQue":-183,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P02S030000","reqQty":82,"qty":-80,"isQue":-80,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01C011079","reqQty":2472,"qty":-2472,"isQue":-2472,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01S022021","reqQty":40,"qty":-88,"isQue":-88,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M01C021007","reqQty":5.03,"qty":-4,"isQue":-4.69,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P00C000033","reqQty":298.34,"qty":-34,"isQue":-34.34,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"A01L2AC002","reqQty":70,"qty":-70,"isQue":-70,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"B01S022000","reqQty":134,"qty":-134,"isQue":-134,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"B01S011000","reqQty":188,"qty":-114,"isQue":-114,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P00C000028","reqQty":0.17,"qty":0,"isQue":-0.17,"level":"<p class=\"cf2\">0库存</p>"},{"InvCode":"S004701121","reqQty":84,"qty":-19,"isQue":-19,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01C011082","reqQty":755,"qty":-1055,"isQue":-1055,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K090000103","reqQty":360,"qty":-189,"isQue":-189,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K060000101","reqQty":48,"qty":-48,"isQue":-48,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K020000119","reqQty":1,"qty":-1,"isQue":-1,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P00C000053","reqQty":0.04,"qty":0,"isQue":-0.04,"level":"<p class=\"cf2\">0库存</p>"},{"InvCode":"S003200891","reqQty":54,"qty":-54,"isQue":-54,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K010000105","reqQty":360,"qty":-119,"isQue":-119,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K030200126","reqQty":131,"qty":-161,"isQue":-161,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"A01E6BC001","reqQty":100,"qty":-100,"isQue":-100,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01S060000","reqQty":13,"qty":-13,"isQue":-13,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01C011040","reqQty":40,"qty":-40,"isQue":-40,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M01C011002","reqQty":40,"qty":-27,"isQue":-27,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"B01S052000","reqQty":434,"qty":-434,"isQue":-434,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K130000122","reqQty":131,"qty":-161,"isQue":-161,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01S021300","reqQty":30,"qty":-486,"isQue":-486,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P02S020000","reqQty":241,"qty":-229,"isQue":-229,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K010000109","reqQty":290,"qty":-196,"isQue":-196,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M01C023003","reqQty":812,"qty":-308,"isQue":-308,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"K090000101","reqQty":290,"qty":-35,"isQue":-35,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01C012012","reqQty":8,"qty":-8,"isQue":-8,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"D01C010000","reqQty":25,"qty":-25,"isQue":-25,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"H010400001","reqQty":360,"qty":-168,"isQue":-168,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"A02000S014","reqQty":290,"qty":-179,"isQue":-179,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P01C011016","reqQty":104,"qty":-104,"isQue":-104,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P05S011002","reqQty":255,"qty":-308,"isQue":-308,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"B01S012000","reqQty":188,"qty":-188,"isQue":-188,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"B01S020007","reqQty":188,"qty":-1,"isQue":-1,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M02C061003","reqQty":5,"qty":-5,"isQue":-5,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"D202300161","reqQty":702,"qty":-100,"isQue":-100,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M903300002","reqQty":84,"qty":-56,"isQue":-56,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P00C000031","reqQty":0.93,"qty":0,"isQue":-0.01,"level":"<p class=\"cf2\">0库存</p>"},{"InvCode":"A01E3FC001","reqQty":134,"qty":-133,"isQue":-133,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"B01S010000","reqQty":161,"qty":-159,"isQue":-159,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"B01S021000","reqQty":234,"qty":-234,"isQue":-234,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"H050100004","reqQty":1,"qty":-1,"isQue":-1,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M02C061004","reqQty":5,"qty":-5,"isQue":-5,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"P00C000051","reqQty":2.3,"qty":-2,"isQue":-2.3,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M01C021006","reqQty":7.33,"qty":-7,"isQue":-7.05,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M909800001","reqQty":188,"qty":-255,"isQue":-255,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"H021120003","reqQty":103,"qty":-100,"isQue":-100,"level":"<p class=\"cf1\">负库存</p>"},{"InvCode":"M01C011004","reqQty":124692,"qty":-81988,"isQue":-81988,"level":"<p class=\"cf1\">负库存</p>"}],"roll_list_updated":"11/05 16:12:11"},"title":"浙江创区电控车间CDU产线实况","fd":5},"case":"ok"}', ture));die;


echo date('Y-m-d H:i:s', 1636076402);die;
$a = '20210202000100';
$a = strtotime($a);



echo date('Gi', $a);die;
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

