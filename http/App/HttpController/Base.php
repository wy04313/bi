<?php
/**
 * @CreateTime:   2020/8/19 12:30 上午
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  用户控制器
 */
namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\Controller;

class Base extends Controller
{

    protected function writeJson($statusCode = 200, $msg = '',$result = [])
    {
        if (!$this->response()->isEndResponse()) {
            $data = Array(
                "code" => $statusCode,
                "data" => $result,
                "msg" => $msg
            );
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        } else {
            return false;
        }
    }

    protected function clientRealIP($headerName = 'x-real-ip')
    {
        $server = ServerManager::getInstance()->getSwooleServer();
        $client = $server->getClientInfo($this->request()->getSwooleRequest()->fd);
        $clientAddress = $client['remote_ip'];
        $xri = $this->request()->getHeader($headerName);
        $xff = $this->request()->getHeader('x-forwarded-for');
        if ($clientAddress === '127.0.0.1') {
            if (!empty($xri)) {  // 如果有xri 则判定为前端有NGINX等代理
                $clientAddress = $xri[0];
            } elseif (!empty($xff)) {  // 如果不存在xri 则继续判断xff
                $list = explode(',', $xff[0]);
                if (isset($list[0])) $clientAddress = $list[0];
            }
        }
        return $clientAddress;
    }

    public function onException(\Throwable $throwable): void
    {
        parent::onException($throwable);
    }

    public function gc()
    {
        parent::gc();
    }

    public function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    public function actionNotFound(?string $action)
    {
        parent::actionNotFound($action);
    }

    public function onRequest(?string $action): ?bool
    {
        // echo 'base_onRequest'.PHP_EOL;
        return parent::onRequest($action);
    }

}
