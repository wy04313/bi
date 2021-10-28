<?php
date_default_timezone_set('Asia/Shanghai');


$a = ['controller' => 'Index','action' => 'getOne','params' => [
    'ip' => '127.0.0.1',
]];

echo json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
