<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Swoole\Http\Response;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * @property \Hyperf\HttpServer\Request $request
 * @property \Hyperf\Di\Container $container
 */
class WebSocketController extends Controller implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    const RDB_ONLINE_LIST = 'SOCK_ONLINE_LIST';

    /**
     * 在雙方進行通信時，會觸發這個事件
     * @param Response|Server $server
     * @param Frame $frame
     * @return void
     */
    public function onMessage($server, $frame): void
    {
        // 心跳刷新缓存
        /** @var \Redis $redis */
        $redis = $this->container->get(\Redis::class);
        
        // 取得所有客戶端的 id
        $fdList = $redis->sMembers(self::RDB_ONLINE_LIST);
        
        // 如果當前客戶在客戶端集合中，就刷新過期時間
        if (in_array($frame->fd, $fdList)) {
            $redis->sAdd(self::RDB_ONLINE_LIST, $frame->fd);
            $redis->expire(self::RDB_ONLINE_LIST, 7200);
        }
        $server->push($frame->fd, 'Recv: ' . $frame->data);
    }

    /**
     * 客戶端關閉連接
     * @param Response|Server $server
     * @return void
     */
    public function onClose($server, int $fd, int $reactorId): void
    {
        /** @var \Redis $redis */
        $redis = $this->container->get(\Redis::class);
        
        // 移除集合中指定的value
        $redis->sRem(self::RDB_ONLINE_LIST, $fd);
        var_dump('closed');
    }

    /**
     * 當連接建立的時候，我要去產生一個客戶端的id，並且把這個id存到redis中
     * @param Response|Server $server
     * @param Request $request
     * @return void
     */
    public function onOpen($server, $request): void
    {
        // 保存客户端id
        /** @var \Redis $redis */
        $redis = $this->container->get(\Redis::class);

        $res1 = $redis->sAdd(self::RDB_ONLINE_LIST, $request->fd);
        var_dump($res1);

        $res = $redis->expire(self::RDB_ONLINE_LIST, 7200);
        var_dump($res);

        $server->push($request->fd, 'Opened');
    }
}
