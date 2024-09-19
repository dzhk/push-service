<?php

use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use App\Middleware\StartSession;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $app->add(ErrorMiddleware::class);
//    $app->add(TwigMiddleware::createFromContainer($app));

};