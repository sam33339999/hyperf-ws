<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

class LineWebHookController extends AbstractController
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        return $response->raw('Hello Hyperf!');
    }

    /**
     * @param \Hyperf\HttpServer\Request $request
     * @param \Hyperf\HttpServer\Response $response
     * 
     */
    public function hook(
        RequestInterface $request,
        ResponseInterface $response,
        int $channel
    ) {
        $destination = $request->all();
        $events = $request->input('events');

    }
}
