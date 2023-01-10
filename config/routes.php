<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;

Router::get('/favicon.ico', fn () => '');

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

// Router::addRoute(['POST', 'HEAD', 'GET'], '/hook/', 'App\Controller\LineWebHookController@index');
Router::addRoute(['POST', 'HEAD', 'GET'], '/hook/{channel:\d+}', [\App\Controller\LineWebHookController::class, 'hook']);




