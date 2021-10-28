<?php
namespace App\WebSocket;

use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Socket\Client\WebSocket;
use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;

/**
 * Class WebSocketParser
 *
 * 此类是自定义的 websocket 消息解析器
 * 此处使用的设计是使用 json string 作为消息格式
 * 当客户端消息到达服务端时，会调用 decode 方法进行消息解析
 * 会将 websocket 消息 转成具体的 Class -> Action 调用 并且将参数注入
 *
 * @package App\WebSocket
 */
class WebSocketParser implements ParserInterface
{
    /**
     * decode
     * @param  string         $raw    客户端原始消息
     * @param  WebSocket      $client WebSocket Client 对象
     * @return Caller         Socket  调用对象
     */
    public function decode($raw, $client) : ? Caller
    {
        $caller = new Caller;
        // 聊天消息 {"controller":"relation","action":"add","params":{"barcode":"553453543"}}
        if ($raw !== 'PING') {
            $payload = json_decode($raw, true);
            // 此处代码打印到控制台,返给前端
            if (!is_array($payload)) {
                echo "decode message error! \n";
                return null;
            }
            $class = isset($payload['controller']) ? $payload['controller'] : 'index';
            $action = isset($payload['action']) ? $payload['action'] : 'actionNotFound';
            $params = isset($payload['params']) ? (array)$payload['params'] : [];

            $controllerClass = "\\App\\WebSocket\\Controller\\" . ucfirst($class);
            if (!class_exists($controllerClass)) $controllerClass = "\\App\\WebSocket\\Controller\\Index";
            $caller->setClient($caller);
            $caller->setControllerClass($controllerClass);
            $caller->setAction($action);
            $caller->setArgs($params);
        } else {
            $caller->setControllerClass('\\App\\WebSocket\\Controller\\Index');
            $caller->setAction('heartbeat');
        }
        return $caller;
    }
    /**
     * encode
     * @param  Response     $response Socket Response 对象
     * @param  WebSocket    $client   WebSocket Client 对象
     * @return string             发送给客户端的消息
     */
    public function encode(Response $response, $client): ?string
    {
        $message = $response->getMessage();
        return $message instanceof ActionPayload ? $message->__toString() : $message;
    }
}
