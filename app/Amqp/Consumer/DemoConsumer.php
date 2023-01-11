<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Controller\WebSocketController;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use PhpAmqpLib\Message\AMQPMessage;
use Hyperf\Server\Server;
use Hyperf\Server\ServerFactory;
use Hyperf\Server\ServerInterface;
use Swoole\Coroutine\Server as SwooleCoServer;
use Swoole\Server as SwooleServer;

#[Consumer(exchange: 'hyperf', routingKey: 'hyperf', queue: 'hyperf', name: "DemoConsumer", nums: 1)]
class DemoConsumer extends ConsumerMessage
{
    public function consume($data): string
    {
        print_r($data);

        // 获取集合中所有的value
        /** @var \Redis */
        $redis = $this->container->get(\Redis::class);
        $fdList=$redis->sMembers(WebSocketController::RDB_ONLINE_LIST);

        /** @var SwooleCoServer|SwooleServer */
        $server = $this->container->get(ServerFactory::class)->getServer()->getServer();
        
        foreach($fdList as $key=>$v){
            if(!empty($v)){
                $server->push((int)$v, $data);
            }
        }

        return Result::ACK;
    }


    public function consumeMessage($data, AMQPMessage $message): string
    {
        return Result::ACK;
    }
}
