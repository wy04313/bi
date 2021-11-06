<?php

namespace App\WebSocket\Controller;

use EasySwoole\Socket\AbstractInterface\Controller;
use EasySwoole\EasySwoole\Config;

class Base extends Controller
{
    function cfgValue($name, $default = null)
    {
        $value = Config::getInstance()->getConf($name);
        return is_null($value) ? $default : $value;
    }

    protected function writeToJson($code = 0, $msg = '操作成功!', $data = [],$case = ''){
        return json_encode(compact('code','msg','data','case'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // 获取request数据,如 "orderId", 这里只能获取一个
    protected function input($name, $default = null) {
        $value = $this->request()->getRequestParam($name);
        return $value ?? $default;
    }
}
