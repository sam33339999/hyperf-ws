<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\HttpServer\Annotation\Controller;

/**
 * @property \Hyperf\HttpServer\Request $request
 * @property \Hyperf\Di\Container $container
 */
class WebSocketController extends Controller implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    /**
     * @param Response|Server $server
     * @param Frame $frame
     * @return void
     */
    public function onMessage($server, $frame): void
    {
        //心跳刷新缓存
        $redis = $this->container->get(\Redis::class);
        //获取所有的客户端id
        $fdList = $redis->sMembers('websocket_sjd_1');
        //如果当前客户端在客户端集合中,就刷新
        if (in_array($frame->fd, $fdList)) {
            $redis->sAdd('websocket_sjd_1', $frame->fd);
            $redis->expire('websocket_sjd_1', 7200);
        }
        $server->push($frame->fd, 'Recv: ' . $frame->data);
    }

    /**
     * @param Response|Server $server
     * @return void
     */
    public function onClose($server, int $fd, int $reactorId): void
    {
        //删掉客户端id
        $redis = $this->container->get(\Redis::class);
        //移除集合中指定的value
        $redis->sRem('websocket_sjd_1', $fd);
        var_dump('closed');
    }

    /**
     * @param Response|Server $server
     * @param Request $request
     * @return void
     */
    public function onOpen($server, $request): void
    {
        //保存客户端id
        $redis = $this->container->get(\Redis::class);

        $res1 = $redis->sAdd('websocket_sjd_1', $request->fd);
        var_dump($res1);

        $res = $redis->expire('websocket_sjd_1', 7200);
        var_dump($res);

        $server->push($request->fd, 'Opened');
    }
}
