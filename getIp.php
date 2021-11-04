<?php

function envJson($data,$case = 'ok'){
    return json_encode(compact('case','data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
//客户端IP 或 (最后一个)代理服务器 IP
echo envJson($_SERVER["REMOTE_ADDR"]);
